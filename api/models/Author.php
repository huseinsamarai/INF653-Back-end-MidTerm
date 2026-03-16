<?php
// api/models/Author.php
class Author {
    private $conn;
    private $table = 'authors';

    public function __construct($db){
        $this->conn = $db;
    }

    public function getAll(){
        $stmt = $this->conn->query("SELECT id, author FROM {$this->table} ORDER BY id");
        return $stmt->fetchAll();
    }

    public function getById($id){
        $stmt = $this->conn->prepare("SELECT id, author FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($author){
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (author) VALUES (?)");
        $stmt->execute([$author]);
        return ['id' => (int)$this->conn->lastInsertId(), 'author' => $author];
    }

    public function update($id, $author){
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET author = ? WHERE id = ?");
        $stmt->execute([$author, $id]);
        return ['id' => (int)$id, 'author' => $author];
    }

    public function delete($id){
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return ($stmt->rowCount() > 0);
    }
}