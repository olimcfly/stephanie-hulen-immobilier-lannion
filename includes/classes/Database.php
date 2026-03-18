<?php
/**
 * Database Singleton Class
 * /includes/classes/Database.php
 * 
 * Gère la connexion PDO à MySQL
 * Utilise les constantes du config.php
 */

class Database {

    private static $instance = null;
    private $connection = null;

    /**
     * Private constructor
     */
    private function __construct() {

        try {

            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

            $this->connection = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

        } catch (PDOException $e) {

            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection error: " . $e->getMessage());

        }
    }

    /**
     * Get PDO instance
     */
    public static function getInstance() {

        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->connection;
    }

    /**
     * Execute query
     */
    public function query($sql, $params = []) {

        try {

            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);

            return $stmt;

        } catch (PDOException $e) {

            error_log("Query error: " . $e->getMessage());
            throw new Exception("Query failed: " . $e->getMessage());

        }
    }

    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {

        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();

    }

    /**
     * Fetch one row
     */
    public function fetchOne($sql, $params = []) {

        $stmt = $this->query($sql, $params);
        return $stmt->fetch();

    }

    /**
     * Insert record
     */
    public function insert($table, $data) {

        $columns = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $this->query($sql, array_values($data));

        return $this->connection->lastInsertId();
    }

    /**
     * Update records
     */
    public function update($table, $data, $where = '', $whereParams = []) {

        $sets = [];

        foreach ($data as $key => $value) {
            $sets[] = "{$key} = ?";
        }

        $setString = implode(',', $sets);

        $whereString = '';
        if (!empty($where)) {
            $whereString = " WHERE " . $where;
        }

        $sql = "UPDATE {$table} SET {$setString}{$whereString}";

        $params = array_merge(array_values($data), $whereParams);

        $stmt = $this->query($sql, $params);

        return $stmt->rowCount();
    }

    /**
     * Delete records
     */
    public function delete($table, $where = '', $params = []) {

        $whereString = '';

        if (!empty($where)) {
            $whereString = " WHERE " . $where;
        }

        $sql = "DELETE FROM {$table}{$whereString}";

        $stmt = $this->query($sql, $params);

        return $stmt->rowCount();
    }

    /**
     * Last insert id
     */
    public function lastInsertId() {

        return $this->connection->lastInsertId();

    }

    /**
     * Prevent clone
     */
    private function __clone(){}

    /**
     * Prevent unserialize
     */
public function __wakeup(){}
}