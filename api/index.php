<?php
// api/index.php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/helpers/Response.php';

$db = (new Database())->getConnection();

require_once __DIR__ . '/models/Author.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Quote.php';

$authorModel = new Author($db);
$categoryModel = new Category($db);
$quoteModel = new Quote($db);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

/*
 * Support both:
 *   /quotes
 *   /api/quotes
 * by removing only the leading "/api" segment when present.
 */
if ($uriPath === '/api') {
    $uriPath = '/';
} elseif (str_starts_with($uriPath, '/api/')) {
    $uriPath = substr($uriPath, 4); // remove "/api"
}

$path = trim($uriPath, '/');
$segments = $path === '' ? [] : explode('/', $path);
$resource = $segments[0] ?? '';

// parse query safely
parse_str($_SERVER['QUERY_STRING'] ?? '', $query);

// read JSON body for POST/PUT/DELETE (may be empty)
$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput ?: '{}', true);
if (!is_array($body)) $body = [];

// Helper functions to check existence
function authorExists(PDO $db, int $id): bool {
    $stmt = $db->prepare("SELECT id FROM authors WHERE id = ?");
    $stmt->execute([$id]);
    return (bool)$stmt->fetchColumn();
}
function categoryExists(PDO $db, int $id): bool {
    $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return (bool)$stmt->fetchColumn();
}

// --- ROOT LANDING PAGE WITH RANDOM QUOTE AND INTERACTIVE BUTTON ---
if ($resource === '') {
    header('Content-Type: text/html; charset=utf-8');

    // Fetch a random quote (quoteModel->get([], true) expected to return array of quotes)
    $randomQuote = $quoteModel->get([], true);
    $quoteText = $randomQuote[0]['quote'] ?? 'No quotes available';
    $authorName = '';
    if (isset($randomQuote[0]['author_id'])) {
        $author = $authorModel->getById((int)$randomQuote[0]['author_id']);
        $authorName = $author['author'] ?? '';
    }

    // Escape for HTML output
    $quoteEsc = htmlspecialchars((string)$quoteText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $authorEsc = htmlspecialchars((string)$authorName, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // safe defaults to avoid "undefined variable" warnings
$time = date('c');                          // ISO 8601 timestamp
$status = http_response_code() ?: 200;      // ensure numeric status exists

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Quotes API — Interactive</title>
    <style>
        :root { --accent: #0077cc; --bg: #f4f4f9; --card: #ffffff; --muted:#666; }
        body { font-family: Arial, sans-serif; text-align: center; padding: 28px; background: var(--bg); margin:0; color:#222; }
        .container { max-width: 1100px; margin: 0 auto; padding: 20px; }
        h1 { color: #222; margin-bottom: 6px; }
        p.lead { color: var(--muted); margin-top:0; }
        .quote-card { background: var(--card); border-radius: 10px; padding: 22px; box-shadow: 0 6px 18px rgba(17,17,17,0.06); margin: 18px auto; width: 90%; max-width: 760px; position: relative; }
        blockquote { font-style: italic; color: #111; margin:0; font-size:1.15rem; line-height:1.5; }
        .author { display:block; margin-top: 10px; font-weight: 600; color: var(--accent); font-size: 0.98rem; }
        button { padding: 8px 14px; font-size: .95rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; transition: background .15s ease; }
        button.secondary { background: transparent; color: var(--accent); border: 1px solid rgba(0,0,0,0.06); }
        button:hover { background: #005fa3; }
        .meta-links { margin-top: 12px; }
        a { color: var(--accent); text-decoration: none; margin: 0 8px; }
        .small { font-size: .9rem; color: #666; margin-top: 10px; }

        /* Endpoint grid */
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 22px; }
        @media (max-width:920px) { .grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width:640px) { .grid { grid-template-columns: 1fr; } }

        .card { background: var(--card); padding: 14px; border-radius: 10px; box-shadow: 0 6px 18px rgba(17,17,17,0.04); text-align:left; }
        .card h3 { margin:0 0 6px 0; font-size:1rem; color:#111; }
        .muted { color:var(--muted); font-size: .92rem; margin-bottom:10px; }
        .endpoint { font-family: monospace; background:#f1f5f9; padding:6px 8px; border-radius:6px; display:inline-block; margin-bottom:8px; font-size:.95rem; }

        /* small inline forms */
        .inline-form { display:flex; gap:6px; align-items:center; margin-top:8px; }
        .inline-form input[type="text"], .inline-form input[type="number"] { padding:6px 8px; border-radius:6px; border:1px solid #ddd; font-size:.95rem; width:100%; }
        .inline-form .mini { padding:6px 8px; font-size:.88rem; }

        /* results area */
        #api-result { margin-top: 18px; text-align:left; max-height:360px; overflow:auto; background:#0f1724; color:#e6eef6; padding:12px; border-radius:8px; font-family:monospace; font-size:0.92rem; white-space:pre-wrap; }
        .controls { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin-top:10px; }
        .note { font-size:.9rem; color:var(--muted); margin-top:6px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Quotes API</h1>
        <p class="lead">A tiny quotes service browse endpoints or get a fresh random quote. Try endpoints directly from this page.</p>

        <div class="quote-card" id="quote-card">
            <blockquote id="quote-text">{$quoteEsc}</blockquote>
            <span class="author" id="quote-author">- {$authorEsc}</span>
        </div>

        <div class="controls">
            <button id="nextQuote">Next Random Quote</button>
            <button class="secondary" id="listQuotes">List All Quotes</button>
            <button class="secondary" id="listAuthors">List Authors</button>
            <button class="secondary" id="listCats">List Categories</button>
        </div>

        <!-- Endpoint hint grid -->
        <div class="grid" style="margin-top:20px;">
            <!-- Quotes -->
            <div class="card">
                <h3>/quotes</h3>
                <div class="muted">GET all | filter by <code>id</code>, <code>author_id</code>, <code>category_id</code> | <code>?random=true</code></div>
                <div class="endpoint">GET /quotes</div>

                <div style="margin-top:8px;">
                    <div class="inline-form">
                        <input id="q-id" type="number" placeholder="id (optional)">
                        <button class="mini" onclick="callQuotes()">Try GET</button>
                    </div>
                    <div class="inline-form" style="margin-top:8px;">
                        <input id="q-random" type="checkbox"><label for="q-random" style="margin-left:6px;">random</label>
                    </div>
                </div>

                <hr style="margin:12px 0;">
                <div class="muted">POST create</div>
                <div class="inline-form">
                    <input id="p-quote" type="text" placeholder='Quote text' />
                </div>
                <div class="inline-form" style="margin-top:8px;">
                    <input id="p-author" type="number" placeholder="author_id" />
                    <input id="p-cat" type="number" placeholder="category_id" />
                    <button class="mini" onclick="postQuote()">Create</button>
                </div>

                <div style="margin-top:10px;">
                    <div class="muted">PUT update</div>
                    <div class="inline-form">
                        <input id="u-id" type="number" placeholder="id" />
                        <input id="u-quote" type="text" placeholder="new quote" />
                    </div>
                    <div class="inline-form" style="margin-top:8px;">
                        <input id="u-author" type="number" placeholder="author_id" />
                        <input id="u-cat" type="number" placeholder="category_id" />
                        <button class="mini" onclick="putQuote()">Update</button>
                        <button class="mini" onclick="delQuote()" style="background:#b91c1c;">Delete</button>
                    </div>
                </div>
            </div>

            <!-- Authors -->
            <div class="card">
                <h3>/authors</h3>
                <div class="muted">GET all or single by <code>?id=</code></div>
                <div class="endpoint">GET /authors</div>
                <div class="inline-form" style="margin-top:8px;">
                    <input id="a-id" type="number" placeholder="id (optional)">
                    <button class="mini" onclick="callAuthors()">Try GET</button>
                </div>

                <hr style="margin:12px 0;">
                <div class="muted">POST create</div>
                <div class="inline-form">
                    <input id="p-author-name" type="text" placeholder="author name">
                    <button class="mini" onclick="postAuthor()">Create</button>
                </div>

                <div style="margin-top:10px;">
                    <div class="muted">PUT update</div>
                    <div class="inline-form">
                        <input id="u-author-id" type="number" placeholder="id">
                        <input id="u-author-name" type="text" placeholder="new name">
                        <button class="mini" onclick="putAuthor()">Update</button>
                    </div>
                    <div class="inline-form" style="margin-top:8px;">
                        <input id="d-author-id" type="number" placeholder="id to delete">
                        <button class="mini" onclick="delAuthor()" style="background:#b91c1c;">Delete</button>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div class="card">
                <h3>/categories</h3>
                <div class="muted">GET all or single by <code>?id=</code></div>
                <div class="endpoint">GET /categories</div>
                <div class="inline-form" style="margin-top:8px;">
                    <input id="c-id" type="number" placeholder="id (optional)">
                    <button class="mini" onclick="callCategories()">Try GET</button>
                </div>

                <hr style="margin:12px 0;">
                <div class="muted">POST create</div>
                <div class="inline-form">
                    <input id="p-category" type="text" placeholder="category name">
                    <button class="mini" onclick="postCategory()">Create</button>
                </div>

                <div style="margin-top:10px;">
                    <div class="muted">PUT update</div>
                    <div class="inline-form">
                        <input id="u-cat-id" type="number" placeholder="id">
                        <input id="u-cat-name" type="text" placeholder="new name">
                        <button class="mini" onclick="putCategory()">Update</button>
                    </div>
                    <div class="inline-form" style="margin-top:8px;">
                        <input id="d-cat-id" type="number" placeholder="id to delete">
                        <button class="mini" onclick="delCategory()" style="background:#b91c1c;">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="meta-links">
            <p class="small">API resources: 
                <a href="/quotes">/quotes</a> |
                <a href="/authors">/authors</a> |
                <a href="/categories">/categories</a>
            </p>
            <p class="note">Responses are shown below. For requests that alter data (POST/PUT/DELETE) this page sends JSON bodies.</p>
        </div>

        <div id="api-result">Click an action to see raw JSON / status here...</div>

    </div>

<script>
const resultEl = document.getElementById('api-result');

function showResult(status, data) {
    const time = new Date().toISOString();
    let out = `// ${time} — HTTP ${status}\n`;
    out += JSON.stringify(data, null, 2);
    resultEl.textContent = out;
}

async function fetchJson(path, opts = {}) {
    try {
        const res = await fetch(path, opts);
        let json;
        try { json = await res.json(); } catch(e) { json = { message: 'No JSON body' }; }
        showResult(res.status, json);
        return { ok: res.ok, status: res.status, json };
    } catch (err) {
        showResult('ERR', { error: err.message });
        return { ok:false };
    }
}

// Quick controls wired to top buttons
document.getElementById('nextQuote').addEventListener('click', async () => {
    const q = document.getElementById('quote-text');
    const a = document.getElementById('quote-author');
    q.textContent = 'Loading...';
    a.textContent = '';
    const resp = await fetchJson('/quotes?random=true', { cache: 'no-store' });
    if (resp.ok && Array.isArray(resp.json) && resp.json.length) {
        const item = resp.json[0];
        q.textContent = item.quote || 'No quote';
        a.textContent = item.author ? ('- ' + item.author) : '';
    } else {
        q.textContent = 'No quotes available';
    }
});

document.getElementById('listQuotes').addEventListener('click', () => callQuotes());
document.getElementById('listAuthors').addEventListener('click', () => callAuthors());
document.getElementById('listCats').addEventListener('click', () => callCategories());

// ... (rest of client-side functions unchanged; same as your original) ...

async function callQuotes() {
    const id = document.getElementById('q-id').value;
    const random = document.getElementById('q-random').checked;
    let url = '/quotes';
    const params = new URLSearchParams();
    if (id) params.set('id', id);
    if (random) params.set('random', 'true');
    if ([...params].length) url += '?' + params.toString();
    await fetchJson(url);
}

async function postQuote() {
    const quote = document.getElementById('p-quote').value.trim();
    const author_id = document.getElementById('p-author').value;
    const category_id = document.getElementById('p-cat').value;
    if (!quote || !author_id || !category_id) return showResult('ERR', { message: 'quote, author_id and category_id are required' });
    await fetchJson('/quotes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ quote, author_id: Number(author_id), category_id: Number(category_id) })
    });
}

async function putQuote() {
    const id = document.getElementById('u-id').value;
    const quote = document.getElementById('u-quote').value.trim();
    const author_id = document.getElementById('u-author').value;
    const category_id = document.getElementById('u-cat').value;
    if (!id || !quote || !author_id || !category_id) return showResult('ERR', { message: 'id, quote, author_id and category_id required' });
    await fetchJson('/quotes', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(id), quote, author_id: Number(author_id), category_id: Number(category_id) })
    });
}

async function delQuote() {
    const id = document.getElementById('u-id').value;
    if (!id) return showResult('ERR', { message: 'id required to delete' });
    await fetchJson('/quotes', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(id) })
    });
}

// Authors functions
async function callAuthors() {
    const id = document.getElementById('a-id').value;
    let url = '/authors';
    if (id) url += '?id=' + encodeURIComponent(id);
    await fetchJson(url);
}

async function postAuthor() {
    const name = document.getElementById('p-author-name').value.trim();
    if (!name) return showResult('ERR', { message: 'author name required' });
    await fetchJson('/authors', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ author: name })
    });
}

async function putAuthor() {
    const id = document.getElementById('u-author-id').value;
    const name = document.getElementById('u-author-name').value.trim();
    if (!id || !name) return showResult('ERR', { message: 'id and new name required' });
    await fetchJson('/authors', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(id), author: name })
    });
}

async function delAuthor() {
    const id = document.getElementById('d-author-id').value;
    if (!id) return showResult('ERR', { message: 'id required to delete' });
    await fetchJson('/authors', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(id) })
    });
}

// Categories functions
async function callCategories() {
    const id = document.getElementById('c-id').value;
    let url = '/categories';
    if (id) url += '?id=' + encodeURIComponent(id);
    await fetchJson(url);
}

async function postCategory() {
    const name = document.getElementById('p-category').value.trim();
    if (!name) return showResult('ERR', { message: 'category name required' });
    await fetchJson('/categories', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category: name })
    });
}

async function putCategory() {
    const id = document.getElementById('u-cat-id').value;
    const name = document.getElementById('u-cat-name').value.trim();
    if (!id || !name) return showResult('ERR', { message: 'id and new name required' });
    await fetchJson('/categories', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(id), category: name })
    });
}

async function delCategory() {
    const id = document.getElementById('d-cat-id').value;
    if (!id) return showResult('ERR', { message: 'id required to delete' });
    await fetchJson('/categories', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(id) })
    });
}
</script>
</body>
</html>
HTML;
    exit;
}

// --- REST API ROUTING ---
switch ($method) {
    case 'GET':
        if ($resource === 'quotes') {
            $filters = [];
            if (isset($query['id'])) $filters['id'] = (int)$query['id'];
            if (isset($query['author_id'])) $filters['author_id'] = (int)$query['author_id'];
            if (isset($query['category_id'])) $filters['category_id'] = (int)$query['category_id'];
            $random = (isset($query['random']) && $query['random'] === 'true');

            $results = $quoteModel->get($filters, $random);

            if (!$results) {
                Response::json(['message' => 'No Quotes Found'], 200);
            } else {
                Response::json($results);
            }
        } elseif ($resource === 'authors') {
            if (isset($query['id'])) {
                $a = $authorModel->getById((int)$query['id']);
                if (!$a) Response::json(['message' => 'author_id Not Found'], 200);
                Response::json($a);
            } else {
                $all = $authorModel->getAll();
                if (!$all) Response::json(['message' => 'No Authors Found'], 200);
                Response::json($all);
            }
        } elseif ($resource === 'categories') {
            if (isset($query['id'])) {
                $c = $categoryModel->getById((int)$query['id']);
                if (!$c) Response::json(['message' => 'category_id Not Found'], 200);
                Response::json($c);
            } else {
                $all = $categoryModel->getAll();
                if (!$all) Response::json(['message' => 'No Categories Found'], 200);
                Response::json($all);
            }
        } else {
            Response::json(['message' => 'Resource Not Found'], 404);
        }
        break;

    case 'POST':
        if ($resource === 'quotes') {
            // require: quote, author_id, category_id
            if (empty($body['quote']) || empty($body['author_id']) || empty($body['category_id'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }
            if (!authorExists($db, (int)$body['author_id'])) {
                Response::json(['message' => 'author_id Not Found'], 400);
            }
            if (!categoryExists($db, (int)$body['category_id'])) {
                Response::json(['message' => 'category_id Not Found'], 400);
            }
            $created = $quoteModel->create($body['quote'], (int)$body['author_id'], (int)$body['category_id']);
            Response::json($created, 201);
        } elseif ($resource === 'authors') {
            if (empty($body['author'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }
            $created = $authorModel->create($body['author']);
            Response::json($created, 201);
        } elseif ($resource === 'categories') {
            if (empty($body['category'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }
            $created = $categoryModel->create($body['category']);
            Response::json($created, 201);
        } else {
            Response::json(['message' => 'Not Found'], 404);
        }
        break;

    case 'PUT':
        if ($resource === 'quotes') {
            // require id, quote, author_id, category_id
            if (empty($body['id']) || empty($body['quote']) || empty($body['author_id']) || empty($body['category_id'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }
            $existing = $quoteModel->get(['id' => (int)$body['id']]);
            if (!$existing) Response::json(['message' => 'No Quotes Found'], 200);
            if (!authorExists($db, (int)$body['author_id'])) Response::json(['message' => 'author_id Not Found'], 400);
            if (!categoryExists($db, (int)$body['category_id'])) Response::json(['message' => 'category_id Not Found'], 400);

            $updated = $quoteModel->update((int)$body['id'], $body['quote'], (int)$body['author_id'], (int)$body['category_id']);
            Response::json($updated);
        } elseif ($resource === 'authors') {
            if (empty($body['id']) || empty($body['author'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }
            $existing = $authorModel->getById((int)$body['id']);
            if (!$existing) Response::json(['message' => 'author_id Not Found'], 200);
            $updated = $authorModel->update((int)$body['id'], $body['author']);
            Response::json($updated);
        } elseif ($resource === 'categories') {
            if (empty($body['id']) || empty($body['category'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }
            $existing = $categoryModel->getById((int)$body['id']);
            if (!$existing) Response::json(['message' => 'category_id Not Found'], 200);
            $updated = $categoryModel->update((int)$body['id'], $body['category']);
            Response::json($updated);
        } else {
            Response::json(['message' => 'Not Found'], 404);
        }
        break;

    case 'DELETE':
        if ($resource === 'quotes') {
            $id = $body['id'] ?? $query['id'] ?? null;
            if (!$id) Response::json(['message' => 'Missing Required Parameters'], 400);
            $exists = $quoteModel->get(['id' => (int)$id]);
            if (!$exists) Response::json(['message' => 'No Quotes Found'], 200);
            $ok = $quoteModel->delete((int)$id);
            if ($ok) Response::json(['id' => (int)$id]);
            Response::json(['message' => 'No Quotes Found'], 200);
        } elseif ($resource === 'authors') {
            $id = $body['id'] ?? $query['id'] ?? null;
            if (!$id) Response::json(['message' => 'Missing Required Parameters'], 400);
            $exists = $authorModel->getById((int)$id);
            if (!$exists) Response::json(['message' => 'author_id Not Found'], 200);
            $ok = $authorModel->delete((int)$id);
            if ($ok) Response::json(['id' => (int)$id]);
            Response::json(['message' => 'author_id Not Found'], 200);
        } elseif ($resource === 'categories') {
            $id = $body['id'] ?? $query['id'] ?? null;
            if (!$id) Response::json(['message' => 'Missing Required Parameters'], 400);
            $exists = $categoryModel->getById((int)$id);
            if (!$exists) Response::json(['message' => 'category_id Not Found'], 200);
            $ok = $categoryModel->delete((int)$id);
            if ($ok) Response::json(['id' => (int)$id]);
            Response::json(['message' => 'category_id Not Found'], 200);
        } else {
            Response::json(['message' => 'Not Found'], 404);
        }
        break;

    default:
        Response::json(['message' => 'Method Not Allowed'], 405);
        break;
}