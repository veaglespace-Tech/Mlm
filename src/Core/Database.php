<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $envPath = __DIR__ . '/../../.env';
        if (file_exists($envPath)) {
            $envVars = parse_ini_file($envPath);
            foreach ($envVars as $key => $value) {
                $_ENV[$key] = $value;
            }
        }
        
        $host = $_ENV['DB_HOST'] ?? "localhost";
        $user = $_ENV['DB_USER'] ?? "root";
        $pass = $_ENV['DB_PASS'] ?? "";
        $db   = $_ENV['DB_NAME'] ?? "mlmp";

        try {
            $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->pdo = new PDO($dsn, $user, $pass, $options);
            $this->pdo->exec("SET sql_mode = ''");
        } catch (PDOException $e) {
            die("MVC Database Connection Failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function connection(): PDO
    {
        return $this->pdo;
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }
}
