<?php
// api/config/Database.php
class Database {
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $databaseUrl = getenv('DATABASE_URL');

            if ($databaseUrl) {
                $db = parse_url($databaseUrl);

                if ($db === false) {
                    throw new Exception('Invalid DATABASE_URL format.');
                }

                $host = $db['host'] ?? 'localhost';
                $port = $db['port'] ?? 5432;
                $db_name = ltrim($db['path'] ?? '', '/');
                $username = $db['user'] ?? 'postgres';
                $password = $db['pass'] ?? '';
            } else {
                // Fallback for local dev
                $host = 'localhost';
                $port = 5432;
                $db_name = 'quotesdb_ptm8';
                $username = 'postgres';
                $password = '';
            }

            $dsn = "pgsql:host={$host};port={$port};dbname={$db_name}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];

            $this->conn = new PDO($dsn, $username, $password, $options);

        } catch (Exception $e) {
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