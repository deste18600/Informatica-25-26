<?php
// 1. Ricolleghiamoci alla sessione dell'utente
session_start();

// 2. Svuotiamo tutte le variabili che avevamo salvato per lui
$_SESSION = array();

// 3. Distruggiamo definitivamente la sessione
session_destroy();

// 4. Lo riportiamo alla pagina iniziale di benvenuto
header('Location: /CHORDLY/php/userpages/welcomePage.php');

// 5. Fermiamo l'esecuzione della pagina
exit;
?>