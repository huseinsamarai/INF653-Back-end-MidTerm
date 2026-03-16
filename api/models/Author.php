<?php
// api/models/Author.php
class Author {
    private $conn;
    private $table = 'authors';
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
     * Return array of authors (possibly empty)
     * @return array
     */
    public function getAll(){
        $sql = "SELECT id, author FROM {$this->table} ORDER BY id";
        try {
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database query error in Author::getAll(): " . $e->getMessage());
        }
    }

    /**
     * Return single author associative array or false
     * @param int $id
     * @return array|false
     */
    public function getById($id){
        $sql = "SELECT id, author FROM {$this->table} WHERE id = ? LIMIT 1";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([(int)$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: false;
        } catch (PDOException $e) {
            throw new Exception("Database query error in Author::getById(): " . $e->getMessage());
        }
    }

    /**
     * Create an author and return created record with id
     * Works with both MySQL and Postgres
     * @param string $author
     * @return array
     */
    public function create($author){
        try {
            if ($this->driver === 'pgsql') {
                $sql = "INSERT INTO {$this->table} (author) VALUES (?) RETURNING id";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$author]);
                $id = (int)$stmt->fetchColumn();
            } else {
                $sql = "INSERT INTO {$this->table} (author) VALUES (?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$author]);
                $id = (int)$this->conn->lastInsertId();
            }
        } catch (PDOException $e) {
            throw new Exception("Database insert error in Author::create(): " . $e->getMessage());
        }

        return [
            'id' => $id,
            'author' => $author
        ];
    }

    /**
     * Update author and return updated representation
     * @param int $id
     * @param string $author
     * @return array
     */
    public function update($id, $author){
        $sql = "UPDATE {$this->table} SET author = ? WHERE id = ?";
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$author, (int)$id]);
        } catch (PDOException $e) {
            throw new Exception("Database update error in Author::update(): " . $e->getMessage());
        }

        return [
            'id' => (int)$id,
            'author' => $author
        ];
    }

    /**
     * Delete author by id. Returns true if a row was deleted.
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
            throw new Exception("Database delete error in Author::delete(): " . $e->getMessage());
        }
    }
}