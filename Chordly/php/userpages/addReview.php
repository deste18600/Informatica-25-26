<?php
require_once('../include/menuchoice.php');

if (!isset($_SESSION['userId'])) {
    header('Location: userLoginpage.php');
    exit;
}

// Raccogliamo i dati inviati dal modulo
$idUtenteRecensito = isset($_POST['fkRecensitoId']) ? (int)$_POST['fkRecensitoId'] : 0;
$valutazione       = isset($_POST['valutazione'])   ? (int)$_POST['valutazione']   : 0;
$commento          = trim($_POST['commento'] ?? '');
$idRecensore       = $_SESSION['userId'];

try {
    // Inserimento diretto nel database
    $sql = "INSERT INTO RecensioneUtente (fkRecensoreId, fkRecensitoId, valutazione, commento)
            VALUES (:recensoreId, :recensitoId, :valutazione, :commento)";
    
    $istruzione = DBHandler::getPDO()->prepare($sql);
    $istruzione->execute([
        ':recensoreId' => $idRecensore,
        ':recensitoId' => $idUtenteRecensito,
        ':valutazione' => $valutazione,
        ':commento'    => $commento
    ]);

    // Torna al profilo dell'utente recensito
    header('Location: publicProfile.php?id=' . $idUtenteRecensito . '&review_success=1');
    exit;

} catch (PDOException $e) {
    die("Errore durante l'inserimento della recensione: " . $e->getMessage());
}

?>