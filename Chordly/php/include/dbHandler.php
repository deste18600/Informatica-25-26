<?php

class DBHandler {
    private static $pdo; // Oggetto PDO
    private static $host = 'localhost';
    private static $db = 'ChordlyDatabase'; // Nome del database
    private static $user = 'root';
    private static $password = '';

    // Impedisce l'uso del costruttore
    private function __construct() {}

    // Metodo per ottenere l'istanza PDO
    public static function getPDO() {
        if (self::$pdo === null) {
            self::connectDatabase();
        }
        return self::$pdo;
    }

    // Metodo per connettersi al database
    private static function connectDatabase() {
        $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=utf8";

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];
            self::$pdo = new PDO($dsn, self::$user, self::$password, $options);
        } catch (PDOException $e) {
            die("Errore di connessione al database: " . $e->getMessage());
        }
    }
}