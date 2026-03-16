<?php
// api/index.php
// Interactive landing page + OpenAPI + REST routing for Quotes API
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/helpers/Response.php';

$db = (new Database())->getConnection();

require_once __DIR__ . '/models/Author.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Quote.php';

$authorModel = new Author($db);
$categoryModel = new Category($db);
$quoteModel = new Quote($db);

// Basic CORS + common headers for all responses
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');

// Handle preflight
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') {
    // Short-circuit preflight requests
    http_response_code(204);
    exit;
}

// parse request path
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// If your project is served from a subfolder this trims it out.
// Example: if SCRIPT_NAME is "/api/index.php" it removes "/api" from start.
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$path = substr($uri, strlen($base));
$path = trim($path, '/');
$segments = explode('/', $path);
$resource = $segments[0] ?? '';

// Query and JSON body
parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
$body = json_decode(file_get_contents('php://input'), true) ?: [];

// Helper existence checks
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

// --- Serve OpenAPI JSON ---
if ($resource === 'openapi.json' || $resource === 'openapi') {
    // Build server URL dynamically
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // base path (where index.php sits)
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $serverUrl = rtrim($scheme . '://' . $host . $basePath, '/');

    $openapi = [
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'Quotes API',
            'version' => '1.0.0',
            'description' => 'Simple Quotes API (quotes, authors, categories)'
        ],
        'servers' => [
            ['url' => $serverUrl]
        ],
        'paths' => [
            '/quotes' => [
                'get' => [
                    'summary' => 'List or filter quotes',
                    'parameters' => [
                        ['name'=>'id','in'=>'query','schema'=>['type'=>'integer']],
                        ['name'=>'author_id','in'=>'query','schema'=>['type'=>'integer']],
                        ['name'=>'category_id','in'=>'query','schema'=>['type'=>'integer']],
                        ['name'=>'random','in'=>'query','schema'=>['type'=>'boolean']]
                    ],
                    'responses' => ['200'=>['description'=>'Array of quotes']]
                ],
                'post' => [
                    'summary' => 'Create a quote',
                    'requestBody' => ['content'=>['application/json'=>['schema'=>['type'=>'object','properties'=>['quote'=>['type'=>'string'],'author_id'=>['type'=>'integer'],'category_id'=>['type'=>'integer']],'required'=>['quote','author_id','category_id']]]]],
                    'responses' => ['201'=>['description'=>'Created']]
                ],
                'put' => [
                    'summary' => 'Update a quote',
                    'requestBody' => ['content'=>['application/json'=>['schema'=>['type'=>'object','properties'=>['id'=>['type'=>'integer'],'quote'=>['type'=>'string'],'author_id'=>['type'=>'integer'],'category_id'=>['type'=>'integer']],'required'=>['id','quote','author_id','category_id']]]]],
                    'responses' => ['200'=>['description'=>'Updated']]
                ],
                'delete' => [
                    'summary' => 'Delete a quote',
                    'requestBody' => ['content'=>['application/json'=>['schema'=>['type'=>'object','properties'=>['id'=>['type'=>'integer']],'required'=>['id']]]]],
                    'responses' => ['200'=>['description'=>'Deleted']]
                ]
            ],
            '/authors' => [
                'get' => [
                    'summary' => 'List authors or get by id',
                    'parameters' => [['name'=>'id','in'=>'query','schema'=>['type'=>'integer']]],
                    'responses' => ['200'=>['description'=>'Array or single author']]
                ],
                'post' => [
                    'summary' => 'Create author',
                    'requestBody' => ['content'=>['application/json'=>['schema'=>['type'=>'object','properties'=>['author'=>['type'=>'string']],'required'=>['author']]]]],
                    'responses' => ['201'=>['description'=>'Created']]
                ],
                'put' => [
                    'summary' => 'Update author',
                    'requestBody' => ['content'=>['application/json'=>['schema'=>['type'=>'object','properties'=>['id'=>['type'=>'integer'],'author'=>['type'=>'string']],'required'=>['id','author']]]]],
                    'responses' => ['200'=>['description'=>'Updated']]
                ],
                'delete' => [
                    'summary' => 'Delete author',
                    'requestBody' => ['content'=>['application/json'=>['schema'=>['type'=>'object','properties'=>['id'=>['type'=>'integer']],'required'=>['id']]]]],
                    'responses' => ['200'=>['description'=>'Deleted']]
                ]
            ],
            '/categories' => [
                'get' => [
                    'summary' => 'List categories or get by id',
                    'parameters' => [['name'=>'id','in'=>'query','schema'=>['type'=>'integer']]],
                    'responses' => ['200'=>['description'=>'Array or single category']]
                ],
                'post' => [
                    'summary' => 'Create category',
                    'requestBody' => ['content'=>['application/json'=>['schema'=>['type'=>'object','properties'=>['category'=>['type'=>'string']],'required'=>['category']]]]],
                    'responses' => ['201'=>['description'=>'Created']]
                ],
                'put' => [
                    'summary' => 'Update category',
                    'requestBody' => ['content'=>['application/json'=>['schema'=>['type'=>'object','properties'=>['id'=>['type'=>'integer'],'category'=>['type'=>'string']],'required'=>['id','category']]]]],
                    'responses' => ['200'=>['description'=>'Updated']]
                ],
                'delete' => [
                    'summary' => 'Delete category',
                    'requestBody' => ['content'=>['application/json'=>['schema'=>['type'=>'object','properties'=>['id'=>['type'=>'integer']],'required'=>['id']]]]],
                    'responses' => ['200'=>['description'=>'Deleted']]
                ]
            ]
        ],
        'components' => new stdClass()
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($openapi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// --- ROOT LANDING PAGE: random quote + Swagger UI embedded ---
if ($resource === '' || $resource === 'api') {
    header('Content-Type: text/html; charset=utf-8');

    // random quote
    $randomQuote = $quoteModel->get([], true);
    $quoteText = $randomQuote[0]['quote'] ?? 'No quotes available';
    $authorName = '';
    if (isset($randomQuote[0]['author_id'])) {
        $author = $authorModel->getById((int)$randomQuote[0]['author_id']);
        $authorName = $author['author'] ?? '';
    }
    $quoteEsc = htmlspecialchars($quoteText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $authorEsc = htmlspecialchars($authorName, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // swagger-ui assets from CDN (no install required)
    // using swagger-ui-dist (unpkg)
    echo "<!doctype html>
<html lang='en'>
<head>
  <meta charset='utf-8'/>
  <meta name='viewport' content='width=device-width, initial-scale=1'/>
  <title>Quotes API — Explorer</title>
  <link rel='stylesheet' href='https://unpkg.com/swagger-ui-dist@4.18.3/swagger-ui.css'>
  <style>
    body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f6f8fb; }
    .hero { padding: 28px; text-align:center; background: linear-gradient(180deg,#ffffff,#f7fbff); box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
    .quote-card{ max-width:900px; margin: 16px auto; background:white; border-radius:8px; padding:18px 22px; box-shadow:0 6px 18px rgba(17,17,17,0.06); }
    blockquote{ margin:0; font-style:italic; font-size:1.1rem; color:#222; }
    .author{ display:block; margin-top:8px; color:#0077cc; font-weight:600; }
    #swagger { margin: 24px auto; max-width: 1100px; }
    .top-links { margin-top:10px; font-size:0.95rem; color:#444; }
    .top-links a{ color:#0077cc; text-decoration:none; margin:0 8px; }
  </style>
</head>
<body>
  <div class='hero'>
    <h1>Quotes API Explorer</h1>
    <p class='top-links'>Try the API from this page (OpenAPI / Swagger UI). Quick links:
      <a href='/quotes'>/quotes</a> • <a href='/authors'>/authors</a> • <a href='/categories'>/categories</a>
    </p>
    <div class='quote-card'>
      <blockquote id='quote-text'>{$quoteEsc}</blockquote>
      <span class='author' id='quote-author'>- {$authorEsc}</span>
    </div>
  </div>

  <div id='swagger'></div>

  <script src='https://unpkg.com/swagger-ui-dist@4.18.3/swagger-ui-bundle.js'></script>
  <script src='https://unpkg.com/swagger-ui-dist@4.18.3/swagger-ui-standalone-preset.js'></script>
  <script>
    // Initialize Swagger UI pointing at our generated OpenAPI JSON
    window.ui = SwaggerUIBundle({
      url: '" . htmlspecialchars((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/openapi.json', ENT_QUOTES | ENT_HTML5) . "',
      dom_id: '#swagger',
      presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
      layout: 'StandaloneLayout',
      tryItOutEnabled: true,
      docExpansion: 'none'
    });

    // Small enhancement: fetch fresh random quote when page loads
    async function refreshQuote() {
      try {
        const res = await fetch('/quotes?random=true', {cache:'no-store'});
        if (!res.ok) return;
        const data = await res.json();
        if (Array.isArray(data) && data.length > 0) {
          const q = data[0];
          document.getElementById('quote-text').textContent = q.quote || 'No quote';
          if (q.author_id) {
            const aRes = await fetch('/authors?id=' + encodeURIComponent(q.author_id));
            if (aRes.ok) {
              const aData = await aRes.json();
              document.getElementById('quote-author').textContent = '- ' + (aData.author || '');
            }
          }
        }
      } catch (e) {
        // ignore
      }
    }
    refreshQuote();
  </script>
</body>
</html>";
    exit;
}

// --- REST API ROUTING (unchanged behaviour) ---
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