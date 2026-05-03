<?php
// 1. Accendiamo il "motore" delle sessioni (serve per ricordare chi è loggato).
// Lo accendiamo solo se non è già stato attivato altrove.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Diciamo al programma dove trovare la lista delle pagine (il nostro JSON).
$percorsoJson = __DIR__ . '/pages.json';

// Creiamo una lista vuota di base per evitare errori se il file JSON non si trova.
$regolePagine = (object)[
    'richiedeLogin' => [],
    'richiedeDatabase' => []
];

// 3. Se il file JSON esiste, lo leggiamo e memorizziamo le regole.
if (file_exists($percorsoJson)) {
    $testoJson = file_get_contents($percorsoJson); // Leggiamo il testo dal file
    $datiDecodificati = json_decode($testoJson);   // Lo trasformiamo in dati che PHP può capire
    
    // Se la lettura è andata a buon fine, salviamo le regole
    if ($datiDecodificati) {
        $regolePagine = $datiDecodificati; 
    }
}

// 4. Scopriamo su quale pagina si trova l'utente in questo momento (es: "profilePage.php").
$paginaAttuale = basename($_SERVER['PHP_SELF']);

// 5. Controlliamo se questa pagina ha bisogno del Database.
// in_array cerca il nome della pagina nella nostra lista "richiedeDatabase".
if (in_array($paginaAttuale, $regolePagine->richiedeDatabase)) {
    require_once __DIR__ . '/dbHandler.php'; // Carichiamo il file del database
}

// 6. Controlliamo se questa pagina richiede che l'utente sia connesso (Login).
if (in_array($paginaAttuale, $regolePagine->richiedeLogin)) {
    require_once __DIR__ . '/loggedin.php'; // Carichiamo il file che controlla il login
}
?>