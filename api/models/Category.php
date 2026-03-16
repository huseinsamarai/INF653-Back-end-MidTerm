<?php
// api/models/Category.php
class Category {
    private $conn;
    private $table = 'categories';
    private $driver;

    public function __construct($db){
        $this->conn = $db;
        try {
            $this->driver = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        } catch (Exception $e) {
            $this->driver = 'mysql';
        }
    }

    /**
     * Return array of categories (possibly empty)
     * @return array
     */
    public function getAll(){
        $sql = "SELECT id, category FROM {$this->table} ORDER BY id";
        try {
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database query error in Category::getAll(): " . $e->getMessage());
        }
    }

    /**
     * Return single category assoc array or false
     * @param int $id
     * @return array|false
     */
    public function getById($id){
        $sql = "SELECT id, category FROM {$this->table} WHERE id = ? LIMIT 1";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([(int)$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: false;
        } catch (PDOException $e) {
            throw new Exception("Database query error in Category::getById(): " . $e->getMessage());
        }
    }

    /**
     * Create category and return created record
     * Works with both MySQL and Postgres
     * @param string $category
     * @return array
     */
    public function create($category){
        try {
            if ($this->driver === 'pgsql') {
                $sql = "INSERT INTO {$this->table} (category) VALUES (?) RETURNING id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$category]);
                $id = (int)$stmt->fetchColumn();
            } else {
                $sql = "INSERT INTO {$this->table} (category) VALUES (?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$category]);
                $id = (int)$this->conn->lastInsertId();
            }
        } catch (PDOException $e) {
            throw new Exception("Database insert error in Category::create(): " . $e->getMessage());
        }

        return [
            'id' => $id,
            'category' => $category
        ];
    }

    /**
     * Update category and return updated representation
     * @param int $id
     * @param string $category
     * @return array
     */
    public function update($id, $category){
        $sql = "UPDATE {$this->table} SET category = ? WHERE id = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$category, (int)$id]);
        } catch (PDOException $e) {
            throw new Exception("Database update error in Category::update(): " . $e->getMessage());
        }

        return [
            'id' => (int)$id,
            'category' => $category
        ];
    }

    /**
     * Delete category by id. Returns true if a row was deleted.
     * @param int $id
     * @return bool
     */
    public function delete($id){
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([(int)$id]);
            return ($stmt->rowCount() > 0);
        } catch (PDOException $e) {
            throw new Exception("Database delete error in Category::delete(): " . $e->getMessage());
        }
    }
}