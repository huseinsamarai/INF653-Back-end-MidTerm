<?php
// api/config/Database.php
class Database {
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $db = parse_url(getenv('DATABASE_URL'));

            $host = $db['host'];
            $port = $db['port'] ?? 5432;
            $db_name = ltrim($db['path'], '/');
            $username = $db['user'];
            $password = $db['pass'];

            $dsn = "pgsql:host={$host};port={$port};dbname={$db_name}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];

            $this->conn = new PDO($dsn, $username, $password, $options);

        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode([
                "message" => "Database Connection Error",
                "error" => $e->getMessage()
            ]);
            exit;
        }
        return $this->conn;
    }
}