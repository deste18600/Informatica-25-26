<?php

//controlla se l'user id esiste nella sessione e quindi se è loggato
if (!isset($_SESSION['userId'])) {
    
    // appunto è vero e l'utente non è loggato lo porta alla pag di login
    header('Location: /CHORDLY/php/userpages/userLoginpage.php');

    //ferma l'esecuzione del codice dopo aver portato al login per sicurezza
    exit; 
}
?>