<?php
// Questa classe gestisce la connessione al database in modo sicuro ed efficiente.
class DBHandler {
    
    //connessione col database in modo da non doverla ripetere ogni volta che serve.
    private static $pdo;

    // Dati per collegarsi al database.
    private static $host = 'localhost';
    private static $db = 'ChordlyDatabase';
    private static $user = 'root';
    private static $password = '';

    //metto privato il costruttore per far si che solo qui vengano create istanze di questa classe, e non in altre parti del codice.
    private function __construct() {}

    // Questa è la funzione che userai nelle tue pagine per ottenere il database.
    public static function getPDO() {
        // controlla se è già connesso
        if (self::$pdo === null) { // se si trova l'istanza PDO 
            self::connectDatabase(); // se no ci colleghiamo al database
        }
        // return del pdo in modo che possa essere usato nelle altre pagine.
        return self::$pdo;
    }

    //metodo privato per la connessione al database
    private static function connectDatabase() {
        // Prepariamo l'indirizzo e le coordinate del database.
        $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=utf8";
        
        //proviamo la connessione
        //prima le opzioni 
        // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION: questo fa sì che se c'è un errore nella query, venga lanciata un'eccezione invece di restituire false. 
        // PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC: questo imposta il modo predefinito di recuperare i dati dalle query, cioè con array associativi


        try {
            $opzioni = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // in questo modo restituisce i dati in array di tipo associativo
            ];
            // Creiamo la connessione 
            self::$pdo = new PDO($dsn, self::$user, self::$password, $opzioni);
            
        } catch (PDOException $e) {
            // messaggio in caso di errore
            die("Ops! Impossibile collegarsi al database: " . $e->getMessage());
        }
    }
}
?>