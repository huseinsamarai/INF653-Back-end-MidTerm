<?php
// api/models/Category.php
class Category {
    private $conn;
    private $table = 'categories';

    public function __construct($db){
        $this->conn = $db;
    }

    public function getAll(){
        $stmt = $this->conn->query("SELECT id, category FROM {$this->table} ORDER BY id");
        return $stmt->fetchAll();
    }

    public function getById($id){
        $stmt = $this->conn->prepare("SELECT id, category FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($category){
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (category) VALUES (?)");
        $stmt->execute([$category]);
        return ['id' => (int)$this->conn->lastInsertId(), 'category' => $category];
    }

    public function update($id, $category){
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET category = ? WHERE id = ?");
        $stmt->execute([$category, $id]);
        return ['id' => (int)$id, 'category' => $category];
    }

    public function delete($id){
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return ($stmt->rowCount() > 0);
    }
}