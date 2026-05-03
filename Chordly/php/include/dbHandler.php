<?php
// Questa classe gestisce la connessione al database in modo sicuro ed efficiente.
class DBHandler {
    // Questa variabile terrà in memoria la nostra connessione per non doverla ricreare.
    private static $pdo;

    // Dati per collegarsi al database.
    private static $host = 'localhost';
    private static $db = 'ChordlyDatabase';
    private static $user = 'root';
    private static $password = '';

    // Questo blocco vuoto impedisce di creare per errore copie inutili di questa classe.
    private function __construct() {}

    // Questa è la funzione che userai nelle tue pagine per ottenere il database.
    public static function getPDO() {
        // Se non ci siamo ancora collegati per questa pagina...
        if (self::$pdo === null) {
            self::connectDatabase(); // ...allora ci colleghiamo!
        }
        // Restituiamo la connessione pronta all'uso.
        return self::$pdo;
    }

    // Funzione interna che fa il lavoro sporco di collegarsi al database.
    private static function connectDatabase() {
        // Prepariamo l'indirizzo e le coordinate del database.
        $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=utf8";
        
        try {
            // Diamo delle istruzioni a PDO: vogliamo vedere bene gli errori e vogliamo i dati in modo facile da leggere.
            $opzioni = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];
            // Creiamo la connessione vera e propria.
            self::$pdo = new PDO($dsn, self::$user, self::$password, $opzioni);
            
        } catch (PDOException $e) {
            // Se la connessione fallisce, fermiamo il sito e mostriamo un messaggio di errore.
            die("Ops! Impossibile collegarsi al database: " . $e->getMessage());
        }
    }
}
?>