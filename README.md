# INF653 Midterm — Quotes REST API

**Simple, exact README you can paste into your repository.**

---

## Description

PHP OOP REST API for quotations with three models: `quotes`, `authors`, and `categories`.  
Designed for local development with XAMPP (MySQL) and tested with Postman. Edit code in VS Code and manage the database with phpMyAdmin. Repository hosting: GitHub.

---

## Requirements (Automated Tests)

The instructor test harness expects these specific rows to exist in your seeded data:

- A quote with `id = 10`
- An author with `id = 5`
- A category with `id = 4`
- Author `id = 5` must have **two quotes** in category `id = 4`

Make sure your seed SQL enforces those IDs. A seed script is included:

```
sql/seed_quotesdb_mysql.sql
```

---

## Local Setup (XAMPP / Windows + WSL Notes)

### Start Services

Open **XAMPP Control Panel** and start:

- Apache
- MySQL (or MariaDB)

### Place Project in Webroot

Recommended Windows path:

```
C:\xampp\htdocs\INF653-midterm\
```

The API will be accessible at:

```
http://localhost/INF653-midterm/api/
```

### Copy Project from WSL

If your working copy is in WSL (`~/projects/INF653-Back-end-MidTerm`):

```bash
# from WSL
cp -r ~/projects/INF653-Back-end-MidTerm /mnt/c/xampp/htdocs/INF653-midterm
```

```bash
# from Windows PowerShell
wsl cp -r ~/projects/INF653-Back-end-MidTerm /mnt/c/xampp/htdocs/INF653-midterm
```

Open the folder in VS Code:

```
C:\xampp\htdocs\INF653-midterm
```

---

## Import Database Schema + Seed Data

1. Open phpMyAdmin

```
http://localhost/phpmyadmin/
```

2. Create database:

```
quotesdb
```

3. Import the seed SQL file:

```
sql/seed_quotesdb_mysql.sql
```

4. Update database credentials in:

```
api/config/Database.php
```

Default XAMPP credentials:

```
user: root
password: ''
```

5. Enable `.htaccess` in Apache.

Edit:

```
C:\xampp\apache\conf\httpd.conf
```

Ensure:

```
AllowOverride All
```

Restart Apache after saving.

---

# API Endpoints

All responses return **JSON**.

---

# Quotes

```
GET /api/quotes/
```

Returns all quotes.

```
GET /api/quotes/?id=4
```

Returns a single quote by ID.

```
GET /api/quotes/?author_id=10
```

Returns quotes by author.

```
GET /api/quotes/?category_id=8
```

Returns quotes by category.

```
GET /api/quotes/?author_id=3&category_id=4
```

Returns quotes matching both filters.

Random quote option:

```
GET /api/quotes/?author_id=3&random=true
```

---

# Authors

```
GET /api/authors/
```

Return all authors.

```
GET /api/authors/?id=5
```

Return a specific author.

---

# Categories

```
GET /api/categories/
```

Return all categories.

```
GET /api/categories/?id=7
```

Return a specific category.

---

# Create (POST)

Create Quote

```
POST /api/quotes/
```

Body:

```json
{
  "quote": "text",
  "author_id": 1,
  "category_id": 2
}
```

Create Author

```
POST /api/authors/
```

Body:

```json
{
  "author": "Name"
}
```

Create Category

```
POST /api/categories/
```

Body:

```json
{
  "category": "Topic"
}
```

---

# Update (PUT)

Update Quote

```
PUT /api/quotes/
```

Body:

```json
{
  "id": 1,
  "quote": "text",
  "author_id": 1,
  "category_id": 2
}
```

Update Author

```
PUT /api/authors/
```

Body:

```json
{
  "id": 1,
  "author": "New Name"
}
```

Update Category

```
PUT /api/categories/
```

Body:

```json
{
  "id": 1,
  "category": "New Topic"
}
```

---

# Delete (DELETE)

Delete Quote

```
DELETE /api/quotes/
```

Body:

```json
{
  "id": 10
}
```

Delete Author

```
DELETE /api/authors/
```

Delete Category

```
DELETE /api/categories/
```

---

# Postman / Curl Examples

Get all quotes:

```
GET http://localhost/INF653-midterm/api/quotes/
```

Create author:

```
POST http://localhost/INF653-midterm/api/authors/
```

Headers:

```
Content-Type: application/json
```

Body:

```json
{
  "author": "New Author"
}
```

Create quote:

```
POST http://localhost/INF653-midterm/api/quotes/
```

Body:

```json
{
  "quote": "A new quote",
  "author_id": 5,
  "category_id": 4
}
```

Update quote:

```
PUT http://localhost/INF653-midterm/api/quotes/
```

Body:

```json
{
  "id": 10,
  "quote": "Updated quote 10",
  "author_id": 5,
  "category_id": 4
}
```

Delete quote:

```
DELETE http://localhost/INF653-midterm/api/quotes/
```

Body:

```json
{
  "id": 10
}
```

---

# Error Messages (Exact Responses Required)

No quotes found:

```json
{ "message": "No Quotes Found" }
```

Author ID not found:

```json
{ "message": "author_id Not Found" }
```

Category ID not found:

```json
{ "message": "category_id Not Found" }
```

Missing parameters:

```json
{ "message": "Missing Required Parameters" }
```

Invalid author_id:

```json
{ "message": "author_id Not Found" }
```

Invalid category_id:

```json
{ "message": "category_id Not Found" }
```

---

# Database Seed (Important)

Run the included SQL file:

```
sql/seed_quotesdb_mysql.sql
```

This script:

- Creates the database
- Creates tables
- Inserts 25 quotes
- Sets required IDs for automated testing

---

# Security & Housekeeping

Do not commit credentials.

Add to `.gitignore`:

```
api/config/.env
```

Use prepared statements (PDO) to prevent SQL injection.

Use environment variables for production deployments.

---

# Deployment Notes

Local deployment (recommended):

```
XAMPP
```

Production option:

```
Render
```

If deploying with Postgres, update:

```
api/config/Database.php
```

---

# Project Structure

```
INF653-midterm/
├─ api/
│  ├─ index.php
│  ├─ .htaccess
│  ├─ config/Database.php
│  ├─ models/Author.php
│  ├─ models/Category.php
│  ├─ models/Quote.php
│  └─ helpers/Response.php
├─ sql/
│  └─ create_quotesdb_mysql.sql
└─ README.md
```

---

# Troubleshooting

Database connection error:

Check:

```
api/config/Database.php
```

Ensure MySQL is running in XAMPP.

---

404 error on API endpoint:

Confirm file exists:

```
C:\xampp\htdocs\INF653-midterm\api\index.php
```

Correct URL:

```
http://localhost/INF653-midterm/api/quotes/
```

---

.htaccess ignored:

Edit:

```
C:\xampp\apache\conf\httpd.conf
```

Ensure:

```
AllowOverride All
```

Restart Apache.

Test direct file access:

```
http://localhost/INF653-midterm/api/index.php
```

---

CORS issues:

Add to `api/index.php`:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

---

# Tests & Submission Checklist

- [ ] GitHub repository created
- [ ] API runs locally
- [ ] Database seed executed
- [ ] Required IDs exist
- [ ] One-page PDF challenges document included
- [ ] Postman requests tested

---

# Author

Husein Samarai

GitHub:

```
https://github.com/huseinsamarai/INF653-Back-end-MidTerm.git
```