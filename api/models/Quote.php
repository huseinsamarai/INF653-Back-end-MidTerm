<?php
// api/models/Quote.php
class Quote {
    private $conn;
    private $table = 'quotes';
    private $driver; // 'mysql' or 'pgsql'

    public function __construct($db){
        $this->conn = $db;
        try {
            $this->driver = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        } catch (Exception $e) {
            // default to mysql-ish behaviour if attribute not available
            $this->driver = 'mysql';
        }
    }

    // Get quotes. If $random === true returns an array with at most one quote.
    public function get($filters = [], $random = false){
        // Support both drivers for random ordering
        $randomFunc = ($this->driver === 'pgsql') ? 'RANDOM()' : 'RAND()';

        $sql = "SELECT q.id, q.quote, q.author_id, q.category_id, a.author AS author, c.category AS category
                FROM {$this->table} q
                JOIN authors a ON q.author_id = a.id
                JOIN categories c ON q.category_id = c.id";
        $where = [];
        $params = [];

        if (!empty($filters['id'])) {
            $where[] = "q.id = ?";
            $params[] = (int)$filters['id'];
        }
        if (!empty($filters['author_id'])) {
            $where[] = "q.author_id = ?";
            $params[] = (int)$filters['author_id'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = "q.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        if ($random) {
            // both MySQL and Postgres accept LIMIT, but Postgres uses RANDOM()
            $sql .= " ORDER BY {$randomFunc} LIMIT 1";
        } else {
            $sql .= " ORDER BY q.id";
        }

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            // Re-throw with a friendlier message for debugging / deployment
            throw new Exception("Database query error in Quote::get(): " . $e->getMessage());
        }

        if ($random) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? [$row] : [];
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Return single row assoc or false
    public function getById($id){
        $sql = "SELECT q.id, q.quote, q.author_id, q.category_id, a.author AS author, c.category AS category
                FROM {$this->table} q
                JOIN authors a ON q.author_id = a.id
                JOIN categories c ON q.category_id = c.id
                WHERE q.id = ? LIMIT 1";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([(int)$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: false;
        } catch (PDOException $e) {
            throw new Exception("Database query error in Quote::getById(): " . $e->getMessage());
        }
    }

    // Create and return created record (with id). Works with both MySQL and Postgres.
    public function create($quoteText, $author_id, $category_id){
        try {
            if ($this->driver === 'pgsql') {
                // Postgres: use RETURNING id
                $sql = "INSERT INTO {$this->table} (quote, author_id, category_id) VALUES (?, ?, ?) RETURNING id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$quoteText, (int)$author_id, (int)$category_id]);
                $id = (int)$stmt->fetchColumn();
            } else {
                // MySQL / others
                $sql = "INSERT INTO {$this->table} (quote, author_id, category_id) VALUES (?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$quoteText, (int)$author_id, (int)$category_id]);
                $id = (int)$this->conn->lastInsertId();
            }
        } catch (PDOException $e) {
            throw new Exception("Database insert error in Quote::create(): " . $e->getMessage());
        }

        return [
            'id' => $id,
            'quote' => $quoteText,
            'author_id' => (int)$author_id,
            'category_id' => (int)$category_id
        ];
    }

    // Update and return representation
    public function update($id, $quoteText, $author_id, $category_id){
        $sql = "UPDATE {$this->table} SET quote = ?, author_id = ?, category_id = ? WHERE id = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$quoteText, (int)$author_id, (int)$category_id, (int)$id]);
        } catch (PDOException $e) {
            throw new Exception("Database update error in Quote::update(): " . $e->getMessage());
        }

        return [
            'id' => (int)$id,
            'quote' => $quoteText,
            'author_id' => (int)$author_id,
            'category_id' => (int)$category_id
        ];
    }

    public function delete($id){
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([(int)$id]);
            return ($stmt->rowCount() > 0);
        } catch (PDOException $e) {
            throw new Exception("Database delete error in Quote::delete(): " . $e->getMessage());
        }
    }
}