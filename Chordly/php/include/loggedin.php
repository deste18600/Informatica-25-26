<?php
// Controlliamo se nella memoria della sessione manca (!isset) l'ID dell'utente.
// Se manca, significa che non ha fatto l'accesso.
if (!isset($_SESSION['userId'])) {
    
    // Lo mandiamo subito alla pagina di login.
    header('Location: /CHORDLY/php/userpages/userLoginpage.php');
    
    // IMPORTANTISSIMO: usiamo 'exit' per fermare subito la lettura di questa pagina.
    // Senza questo, il server continuerebbe a leggere e mostrare il resto del codice!
    exit; 
}
?>