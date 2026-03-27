<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/*
|--------------------------------------------------------------------------
| CORS + JSON headers
|--------------------------------------------------------------------------
| These must be sent before any output.
| Also handle OPTIONS preflight before touching the database.
*/
header('Access-Control-Allow-Origin: *');
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/models/Author.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Quote.php';

$db = (new Database())->getConnection();

$authorModel = new Author($db);
$categoryModel = new Category($db);
$quoteModel   = new Quote($db);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

/*
|--------------------------------------------------------------------------
| Support both:
|   /api/quotes/
|   /INF653-midterm/api/quotes/
|   /quotes/
|--------------------------------------------------------------------------
*/
if ($uriPath === '/api') {
    $uriPath = '/';
} else {
    $apiPos = strpos($uriPath, '/api/');
    if ($apiPos !== false) {
        $uriPath = substr($uriPath, $apiPos + 4); // leaves "/quotes/"
    } elseif (str_starts_with($uriPath, '/api')) {
        $uriPath = substr($uriPath, 4);
    }
}

$path = trim($uriPath, '/');
$segments = $path === '' ? [] : explode('/', $path);
$resource = $segments[0] ?? '';

parse_str($_SERVER['QUERY_STRING'] ?? '', $query);

$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput ?: '{}', true);
if (!is_array($body)) {
    $body = [];
}

function authorExists(PDO $db, int $id): bool
{
    $stmt = $db->prepare("SELECT id FROM authors WHERE id = ?");
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}

function categoryExists(PDO $db, int $id): bool
{
    $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}

/*
|--------------------------------------------------------------------------
| Root endpoint
|--------------------------------------------------------------------------
| The assignment says responses should be JSON
*/
if ($resource === '') {
    Response::json([
        'message' => 'Quotes API'
    ]);
}

switch ($method) {
    case 'GET':
        if ($resource === 'quotes') {
            $filters = [];

            if (isset($query['id'])) {
                $filters['id'] = (int) $query['id'];
            }
            if (isset($query['author_id'])) {
                $filters['author_id'] = (int) $query['author_id'];
            }
            if (isset($query['category_id'])) {
                $filters['category_id'] = (int) $query['category_id'];
            }

            $random = isset($query['random']) && $query['random'] === 'true';

            $results = $quoteModel->get($filters, $random);

            if (!$results) {
                Response::json(['message' => 'No Quotes Found'], 200);
            }

            Response::json($results);
        }

        if ($resource === 'authors') {
            if (isset($query['id'])) {
                $author = $authorModel->getById((int) $query['id']);
                if (!$author) {
                    Response::json(['message' => 'author_id Not Found'], 200);
                }
                Response::json($author);
            }

            $all = $authorModel->getAll();
            if (!$all) {
                Response::json(['message' => 'No Authors Found'], 200);
            }
            Response::json($all);
        }

        if ($resource === 'categories') {
            if (isset($query['id'])) {
                $category = $categoryModel->getById((int) $query['id']);
                if (!$category) {
                    Response::json(['message' => 'category_id Not Found'], 200);
                }
                Response::json($category);
            }

            $all = $categoryModel->getAll();
            if (!$all) {
                Response::json(['message' => 'No Categories Found'], 200);
            }
            Response::json($all);
        }

        Response::json(['message' => 'Resource Not Found'], 404);

    case 'POST':
        if ($resource === 'quotes') {
            if (empty($body['quote']) || empty($body['author_id']) || empty($body['category_id'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }

            $authorId = (int) $body['author_id'];
            $categoryId = (int) $body['category_id'];

            if (!authorExists($db, $authorId)) {
                Response::json(['message' => 'author_id Not Found'], 400);
            }

            if (!categoryExists($db, $categoryId)) {
                Response::json(['message' => 'category_id Not Found'], 400);
            }

            $created = $quoteModel->create((string) $body['quote'], $authorId, $categoryId);
            Response::json($created, 201);
        }

        if ($resource === 'authors') {
            if (empty($body['author'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }

            $created = $authorModel->create((string) $body['author']);
            Response::json($created, 201);
        }

        if ($resource === 'categories') {
            if (empty($body['category'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }

            $created = $categoryModel->create((string) $body['category']);
            Response::json($created, 201);
        }

        Response::json(['message' => 'Not Found'], 404);

    case 'PUT':
        if ($resource === 'quotes') {
            if (empty($body['id']) || empty($body['quote']) || empty($body['author_id']) || empty($body['category_id'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }

            $id = (int) $body['id'];
            $authorId = (int) $body['author_id'];
            $categoryId = (int) $body['category_id'];

            $existing = $quoteModel->get(['id' => $id]);
            if (!$existing) {
                Response::json(['message' => 'No Quotes Found'], 200);
            }

            if (!authorExists($db, $authorId)) {
                Response::json(['message' => 'author_id Not Found'], 400);
            }

            if (!categoryExists($db, $categoryId)) {
                Response::json(['message' => 'category_id Not Found'], 400);
            }

            $updated = $quoteModel->update($id, (string) $body['quote'], $authorId, $categoryId);
            Response::json($updated);
        }

        if ($resource === 'authors') {
            if (empty($body['id']) || empty($body['author'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }

            $id = (int) $body['id'];

            $existing = $authorModel->getById($id);
            if (!$existing) {
                Response::json(['message' => 'author_id Not Found'], 200);
            }

            $updated = $authorModel->update($id, (string) $body['author']);
            Response::json($updated);
        }

        if ($resource === 'categories') {
            if (empty($body['id']) || empty($body['category'])) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }

            $id = (int) $body['id'];

            $existing = $categoryModel->getById($id);
            if (!$existing) {
                Response::json(['message' => 'category_id Not Found'], 200);
            }

            $updated = $categoryModel->update($id, (string) $body['category']);
            Response::json($updated);
        }

        Response::json(['message' => 'Not Found'], 404);

    case 'DELETE':
        if ($resource === 'quotes') {
            $id = $body['id'] ?? $query['id'] ?? null;

            if (!$id) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }

            $id = (int) $id;

            $existing = $quoteModel->get(['id' => $id]);
            if (!$existing) {
                Response::json(['message' => 'No Quotes Found'], 200);
            }

            $ok = $quoteModel->delete($id);
            if ($ok) {
                Response::json(['id' => $id]);
            }

            Response::json(['message' => 'No Quotes Found'], 200);
        }

        if ($resource === 'authors') {
            $id = $body['id'] ?? $query['id'] ?? null;

            if (!$id) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }

            $id = (int) $id;

            $existing = $authorModel->getById($id);
            if (!$existing) {
                Response::json(['message' => 'author_id Not Found'], 200);
            }

            $ok = $authorModel->delete($id);
            if ($ok) {
                Response::json(['id' => $id]);
            }

            Response::json(['message' => 'author_id Not Found'], 200);
        }

        if ($resource === 'categories') {
            $id = $body['id'] ?? $query['id'] ?? null;

            if (!$id) {
                Response::json(['message' => 'Missing Required Parameters'], 400);
            }

            $id = (int) $id;

            $existing = $categoryModel->getById($id);
            if (!$existing) {
                Response::json(['message' => 'category_id Not Found'], 200);
            }

            $ok = $categoryModel->delete($id);
            if ($ok) {
                Response::json(['id' => $id]);
            }

            Response::json(['message' => 'category_id Not Found'], 200);
        }

        Response::json(['message' => 'Not Found'], 404);

    default:
        Response::json(['message' => 'Method Not Allowed'], 405);
}