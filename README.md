# INF653 Midterm - Quotes REST API

## Description
PHP OOP REST API for quotes (quotes, authors, categories). Built for local testing with XAMPP (MySQL).

## Local setup (XAMPP)
1. Create database: run `sql/create_quotesdb_mysql.sql` in phpMyAdmin.
2. Seed data (at least 5 categories, 5 authors, 25 quotes). Ensure:
   - quote id = 10 exists
   - author id = 5 exists
   - category id = 4 exists
   - author id 5 has two quotes in category id 4
3. Put `api` folder under your web root (e.g., `C:\xampp\htdocs\yourproject\api`)
4. Update DB credentials in `api/config/Database.php` if necessary.
5. Use Postman to test endpoints.

## Endpoints
- GET /api/quotes/
- GET /api/quotes/?id=4
- GET /api/quotes/?author_id=10
- GET /api/quotes/?category_id=8
- GET /api/quotes/?author_id=3&category_id=4
- Add `&random=true` to get a single random quote (optional extra challenge).
- GET /api/authors/ and /api/authors/?id=5
- GET /api/categories/ and /api/categories/?id=7

- POST /api/quotes/  (body: { "quote": "...", "author_id": 1, "category_id": 2 })
- POST /api/authors/ (body: { "author": "Name" })
- POST /api/categories/ (body: { "category": "Topic" })

- PUT /api/quotes/ (body: { "id": 1, "quote": "...", "author_id": 1, "category_id": 2 })
- PUT /api/authors/ (body: { "id": 1, "author": "New Name" })
- PUT /api/categories/ (body: { "id": 1, "category": "New Topic" })

- DELETE /api/quotes/ (body or query: "id")
- DELETE /api/authors/ (body or query: "id")
- DELETE /api/categories/ (body or query: "id")

## Testing
Use Postman or curl. See examples in the project root.

## Author
Husein