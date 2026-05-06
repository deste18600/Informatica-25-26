<?php
// ricollegamento pe rcapire quale sessione è quella da distruggere
session_start();

// distruggiamo la sessione rendendola uguale ad un array vuoto di fatto svuotandola
$_SESSION = array();

// metodo vero e proprio per distruggere la sessione
session_destroy();

// una volta che la sessione è distrutta, portiamo l'utente alla pagina di benvenuto
header('Location: /CHORDLY/php/userpages/welcomePage.php');

// Fermiamo l'esecuzione della pagina come per loggedin.php per sicurezza in modo da evitare che vengono usate istruzioni dopo il logout
?>