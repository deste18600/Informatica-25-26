<?php
class DBHandler {
    //Connessione statica con il database

    private static $pdo;
    // Dati per collegarsi al database.
    private static $host = 'localhost';
    private static $db = 'ChordlyDatabase';
    private static $user = 'root';
    private static $password = '';

    private function __construct() {}

    // funzione per ottenere la connessione al database
    public static function getPDO() {
     
        if (self::$pdo === null) { 
            self::connectDatabase(); 
        }
        
        return self::$pdo;
    }

   
    private static function connectDatabase() {
        // Prepariamo l'indirizzo e posizione database
        $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=utf8";
        
     
        // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION: questo fa sì che se c'è un errore nella query, venga lanciata un'eccezione invece di restituire false. 
        // PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC: questo imposta il modo predefinito di recuperare i dati dalle query, cioè con array associativi


        try {
            $opzioni = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC 
            ];
            // creazione connessione
            self::$pdo = new PDO($dsn, self::$user, self::$password, $opzioni);
            
        } catch (PDOException $e) {
            die("Ops! Impossibile collegarsi al database: " . $e->getMessage());
        }
    }
}
?>