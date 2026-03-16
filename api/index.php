<?php
// api/index.php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/helpers/Response.php';

$db = (new Database())->getConnection();

require_once __DIR__ . '/models/Author.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Quote.php';

$authorModel = new Author($db);
$categoryModel = new Category($db);
$quoteModel = new Quote($db);

// parse path and method
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// If your project is served from a subfolder this trims it out.
// Example: if SCRIPT_NAME is "/api/index.php" it will remove "/api" from the start of the URI.
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$path = substr($uri, strlen($base));
$path = trim($path, '/');
$segments = explode('/', $path);
$resource = $segments[0] ?? '';

parse_str($_SERVER['QUERY_STRING'] ?? '', $query);

// read JSON body for POST/PUT/DELETE (may be empty)
$body = json_decode(file_get_contents('php://input'), true) ?: [];

// Helper functions to check existence
function authorExists($db, $id) {
    $stmt = $db->prepare("SELECT id FROM authors WHERE id = ?");
    $stmt->execute([$id]);
    return (bool)$stmt->fetch();
}
function categoryExists($db, $id) {
    $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return (bool)$stmt->fetch();
}

// --- ROOT LANDING PAGE WITH RANDOM QUOTE AND INTERACTIVE BUTTON ---
if ($resource === '' || $resource === 'api') {
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
    $quoteEsc = htmlspecialchars($quoteText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $authorEsc = htmlspecialchars($authorName, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1'>
    <title>Quotes API</title>
    <style>
        :root { --accent: #0077cc; --bg: #f4f4f9; --card: #ffffff; }
        body { font-family: Arial, sans-serif; text-align: center; padding: 40px; background: var(--bg); margin:0; }
        .container { max-width: 960px; margin: 0 auto; padding: 20px; }
        h1 { color: #222; margin-bottom: 6px; }
        p.lead { color: #555; margin-top:0; }
        .quote-card { background: var(--card); border-radius: 10px; padding: 28px; box-shadow: 0 6px 18px rgba(17,17,17,0.06); margin: 28px auto; width: 90%; max-width: 760px; position: relative; }
        blockquote { font-style: italic; color: #111; margin:0; font-size:1.15rem; line-height:1.5; }
        .author { display:block; margin-top: 14px; font-weight: 600; color: var(--accent); font-size: 0.98rem; }
        button { padding: 10px 18px; font-size: 1rem; background: var(--accent); color: white; border: none; border-radius: 8px; cursor: pointer; transition: background .15s ease; }
        button:hover { background: #005fa3; }
        .meta-links { margin-top: 18px; }
        a { color: var(--accent); text-decoration: none; margin: 0 8px; }
        .small { font-size: .9rem; color: #666; margin-top: 10px; }
        @media (max-width:600px) { blockquote { font-size:1rem } .quote-card { padding: 18px } }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Quotes API</h1>
        <p class='lead'>A tiny quotes service — browse endpoints or get a fresh random quote.</p>

        <div class='quote-card' id='quote-card'>
            <blockquote id='quote-text'>{$quoteEsc}</blockquote>
            <span class='author' id='quote-author'>- {$authorEsc}</span>
        </div>

        <button id='nextQuote'>Next Random Quote</button>

        <div class='meta-links'>
            <p class='small'>API resources: 
                <a href='/quotes'>/quotes</a> |
                <a href='/authors'>/authors</a> |
                <a href='/categories'>/categories</a>
            </p>
        </div>
    </div>

    <script>
    // Fetch a new random quote and update the page without reload
    document.getElementById('nextQuote').addEventListener('click', async () => {
        const quoteBox = document.getElementById('quote-text');
        const authorBox = document.getElementById('quote-author');

        quoteBox.textContent = 'Loading...';
        authorBox.textContent = '';

        try {
            const res = await fetch('/quotes?random=true', { cache: 'no-store' });
            if (!res.ok) throw new Error('Network response not ok');
            const data = await res.json();
            if (!Array.isArray(data) || data.length === 0) {
                quoteBox.textContent = 'No quotes available';
                authorBox.textContent = '';
                return;
            }
            const q = data[0];
            // show quote text
            quoteBox.textContent = q.quote ?? 'No quote';
            // fetch author name if author_id is present
            if (q.author_id) {
                try {
                    const aRes = await fetch('/authors?id=' + encodeURIComponent(q.author_id));
                    if (aRes.ok) {
                        const aData = await aRes.json();
                        authorBox.textContent = '- ' + (aData.author ?? '');
                    } else {
                        authorBox.textContent = '';
                    }
                } catch (err) {
                    authorBox.textContent = '';
                }
            } else {
                authorBox.textContent = '';
            }
        } catch (err) {
            quoteBox.textContent = 'Error fetching quote';
            authorBox.textContent = '';
        }
    });
    </script>
</body>
</html>";
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
            if (!authorExists($db, $body['author_id'])) {
                Response::json(['message' => 'author_id Not Found'], 400);
            }
            if (!categoryExists($db, $body['category_id'])) {
                Response::json(['message' => 'category_id Not Found'], 400);
            }
            $created = $quoteModel->create($body['quote'], $body['author_id'], $body['category_id']);
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
            if (!authorExists($db, $body['author_id'])) Response::json(['message' => 'author_id Not Found'], 400);
            if (!categoryExists($db, $body['category_id'])) Response::json(['message' => 'category_id Not Found'], 400);

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
            // id must be provided (we accept JSON body or query param)
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