<?php
// controllo e avvio della sessione se non presente e in modo di recuperare tutti i dati presenti nella sessione
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//  diciamo al programma dove trovare la lista delle pagine (il nostro JSON).
$percorsoJson = __DIR__ . '/pages.json';

// Test per correzione senza JSON(rende la pagina funzionale anche se non trova il file JSON, ma ovviamente senza regole).
$regolePagine = (object)[
    'richiedeLogin' => [],
    'richiedeDatabase' => []
];

// Se il file JSON non esiste, vengono regole salvate in regolepagine.
if (file_exists($percorsoJson)) {
    $testoJson = file_get_contents($percorsoJson); // Leggiamo il testo dal file
    $datiDecodificati = json_decode($testoJson);   // Lo trasformiamo in dati che PHP può capire
    
    // Se il json viene letto correttamente, allora sovrascriviamo le regolePagine con il json
    if ($datiDecodificati) {
        $regolePagine = $datiDecodificati; 
    }
}

// Scopriamo su quale pagina si trova l'utente in questo momento
$paginaAttuale = basename($_SERVER['PHP_SELF']);

//  Controlliamo se questa pagina ha bisogno del Database.
// in_array cerca il nome della pagina nella nostra lista "richiedeDatabase".
if (in_array($paginaAttuale, $regolePagine->richiedeDatabase)) {
    require_once __DIR__ . '/dbHandler.php'; // Carichiamo il file del database
}

//  Controlliamo se questa pagina richiede che l'utente sia connesso (Login).
if (in_array($paginaAttuale, $regolePagine->richiedeLogin)) {
    require_once __DIR__ . '/loggedin.php'; // Carichiamo il file che controlla il login
}
?>