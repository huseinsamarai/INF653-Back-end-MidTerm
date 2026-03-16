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

// trim to /api/... if your project is in a subfolder ensure this resolves correctly
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$path = substr($uri, strlen($base));
$path = trim($path, '/');
$segments = explode('/', $path);

// expected patterns: quotes, authors, categories
$resource = $segments[0] ?? '';

parse_str($_SERVER['QUERY_STRING'] ?? '', $query);

// read JSON body for POST/PUT
$body = json_decode(file_get_contents('php://input'), true) ?: [];

// Helper to check existences
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

// ROUTING
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
                if (!$all) Response::json(['message' => 'author_id Not Found'], 200);
                Response::json($all);
            }
        } elseif ($resource === 'categories') {
            if (isset($query['id'])) {
                $c = $categoryModel->getById((int)$query['id']);
                if (!$c) Response::json(['message' => 'category_id Not Found'], 200);
                Response::json($c);
            } else {
                $all = $categoryModel->getAll();
                if (!$all) Response::json(['message' => 'category_id Not Found'], 200);
                Response::json($all);
            }
        } else {
            Response::json(['message' => 'Not Found'], 404);
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

            $updated = $quoteModel->update($body['id'], $body['quote'], $body['author_id'], $body['category_id']);
            Response::json($updated);
        } elseif ($resource === 'authors') {
            if (empty($body['id']) || empty($body['author'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }
            $existing = $authorModel->getById((int)$body['id']);
            if (!$existing) Response::json(['message' => 'author_id Not Found'], 200);
            $updated = $authorModel->update($body['id'], $body['author']);
            Response::json($updated);
        } elseif ($resource === 'categories') {
            if (empty($body['id']) || empty($body['category'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }
            $existing = $categoryModel->getById((int)$body['id']);
            if (!$existing) Response::json(['message' => 'category_id Not Found'], 200);
            $updated = $categoryModel->update($body['id'], $body['category']);
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