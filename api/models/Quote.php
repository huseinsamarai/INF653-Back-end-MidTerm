<?php
// api/models/Quote.php
class Quote {
    private $conn;
    private $table = 'quotes';

    public function __construct($db){
        $this->conn = $db;
    }

    // get quotes with optional filters and random
    public function get($filters = [], $random = false){
        $sql = "SELECT q.id, q.quote, a.author AS author, c.category AS category
                FROM {$this->table} q
                JOIN authors a ON q.author_id = a.id
                JOIN categories c ON q.category_id = c.id";
        $where = [];
        $params = [];

        if (!empty($filters['id'])) {
            $where[] = "q.id = ?";
            $params[] = $filters['id'];
        }
        if (!empty($filters['author_id'])) {
            $where[] = "q.author_id = ?";
            $params[] = $filters['author_id'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = "q.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if ($where) $sql .= " WHERE " . implode(" AND ", $where);

        if ($random) {
            $sql .= " ORDER BY RAND() LIMIT 1";
        } else {
            $sql .= " ORDER BY q.id";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        if ($random) {
            $row = $stmt->fetch();
            return $row ? [$row] : [];
        }
        return $stmt->fetchAll();
    }

    public function getById($id){
        return $this->get(['id' => $id]);
    }

    public function create($quoteText, $author_id, $category_id){
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (quote, author_id, category_id) VALUES (?, ?, ?)");
        $stmt->execute([$quoteText, $author_id, $category_id]);
        return [
            'id' => (int)$this->conn->lastInsertId(),
            'quote' => $quoteText,
            'author_id' => (int)$author_id,
            'category_id' => (int)$category_id
        ];
    }

    public function update($id, $quoteText, $author_id, $category_id){
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET quote = ?, author_id = ?, category_id = ? WHERE id = ?");
        $stmt->execute([$quoteText, $author_id, $category_id, $id]);
        return [
            'id' => (int)$id,
            'quote' => $quoteText,
            'author_id' => (int)$author_id,
            'category_id' => (int)$category_id
        ];
    }

    public function delete($id){
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return ($stmt->rowCount() > 0);
    }
}