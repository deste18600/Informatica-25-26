<?php
// 1. Usa __DIR__ per trovare pages.json nella stessa cartella di menuchoice
$jsonPath = __DIR__ . '/pages.json';

if (file_exists($jsonPath)) {
    $json = file_get_contents($jsonPath);
    // Se il JSON è corrotto, json_decode restituisce null. 
    // Usiamo l'operatore ?: per assegnare un oggetto di default in caso di errore.
    $obj = json_decode($json) ?: (object)[
        'loggedInPages' => [],
        'DBPages' => [],
        'userpages' => [],
        'adminpages' => []
    ];
} else {
    // Se il file non esiste, crea un oggetto vuoto per evitare il Fatal Error
    $obj = (object)[
        'loggedInPages' => [],
        'DBPages' => [],
        'userpages' => [],
        'adminpages' => []
    ];
}

// 2. Prendi il nome della pagina attuale
$pageName = basename($_SERVER['PHP_SELF']);

// 3. Esegui i controlli (Senza cambiare nulla qui, ma ora $obj non è più null)
if(in_array($pageName, $obj->loggedInPages)){
    require_once __DIR__ . '/header.php';
}

if(in_array($pageName, $obj->DBPages)){
    require_once __DIR__ . '/dbHandler.php';
}

if(in_array($pageName, $obj->userpages)){
    // Solo le pagine in userpages richiedono che l'utente sia autenticato
    require_once __DIR__ . '/loggedin.php';
} elseif(in_array($pageName, $obj->adminpages)){
    include __DIR__ . '/adminMenu.php';
}
?>