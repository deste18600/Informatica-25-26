<?php
// Prima di tutto: avvia la sessione
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$jsonPath = __DIR__ . '/pages.json';

if (file_exists($jsonPath)) {
    $json = file_get_contents($jsonPath);
    $obj = json_decode($json) ?: (object)[
        'loggedInPages' => [],
        'DBPages' => [],
        'userpages' => [],
        'adminpages' => []
    ];
} else {
    $obj = (object)[
        'loggedInPages' => [],
        'DBPages' => [],
        'userpages' => [],
        'adminpages' => []
    ];
}

$pageName = basename($_SERVER['PHP_SELF']);

if(in_array($pageName, $obj->DBPages)){
    require_once __DIR__ . '/dbHandler.php';
}

if(in_array($pageName, $obj->userpages)){
    require_once __DIR__ . '/loggedin.php';
}
