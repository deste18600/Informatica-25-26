<?php
require_once('../include/menuchoice.php');

$idUtenteLoggato = $_SESSION['userId'];

// VALIDAZIONE: controlliamo che ci sia un ID e che sia un numero valido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: profilePage.php');
    exit;
}

$idArticoloDaEliminare = (int)$_GET['id'];

try {
    
    // Eliminiamo l'articolo SOLAMENTE se il suo idArticolo corrisponde E se l'fkUtenteId è uguale al nostro!

    $sql = "DELETE FROM ArticoloInVendita WHERE idArticolo = :idArticolo AND fkUtenteId = :idUtente";
    
    $istruzione = DBHandler::getPDO()->prepare($sql);
    $istruzione->execute([
        ':idArticolo' => $idArticoloDaEliminare,
        ':idUtente'   => $idUtenteLoggato
    ]);

    // Riportiamo l'utente al suo profilo con un segnale di successo
    header('Location: profilePage.php?status=success');
    exit;

} catch (PDOException $e) {
    //in caso di errore
    die("Errore durante l'eliminazione: " . $e->getMessage());
}

?>