<?php
// controllo e avvio della sessione se non presente e in modo di recuperare tutti i dati presenti nella sessione
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$percorsoJson = __DIR__ . '/pages.json';

// Test per correzione senza JSON(rende la pagina funzionale anche se non trova il file JSON, ma ovviamente senza regole.
$regolePagine = (object)[
    'richiedeLogin' => [],
    'richiedeDatabase' => []
];

// Se il file JSON non esiste,le regole vengono regole salvate in regolepagine.
if (file_exists($percorsoJson)) {
    $testoJson = file_get_contents($percorsoJson); 
    $datiDecodificati = json_decode($testoJson);   


    if ($datiDecodificati) {
        $regolePagine = $datiDecodificati; 
    }
}

$paginaAttuale = basename($_SERVER['PHP_SELF']);

if (in_array($paginaAttuale, $regolePagine->richiedeDatabase)) {
    require_once __DIR__ . '/dbHandler.php'; 
}


if (in_array($paginaAttuale, $regolePagine->richiedeLogin)) {
    require_once __DIR__ . '/loggedin.php'; 
}
?>