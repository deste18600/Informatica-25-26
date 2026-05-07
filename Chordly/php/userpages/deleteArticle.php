<?php
require_once('../include/menuchoice.php');

$idUtenteLoggato = $_SESSION['userId'];

// validazione dell'id della sessione
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: profilePage.php');
    exit;
}

$idArticoloDaEliminare = (int)$_GET['id'];

try {
    
    // Eliminazione dell'articolo se il suo idArticolo corrisponde E se l'fkUtenteId è uguale a quello della sessione
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