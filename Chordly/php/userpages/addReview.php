<?php
require_once('../include/menuchoice.php');

if (!isset($_SESSION['userId'])) {
    header('Location: userLoginpage.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: mainPage.php');
    exit;
}

$fkRecensitoId = isset($_POST['fkRecensitoId']) ? (int)$_POST['fkRecensitoId'] : 0;
$valutazione   = isset($_POST['valutazione'])   ? (int)$_POST['valutazione']   : 0;
$commento      = trim($_POST['commento'] ?? '');
$fkRecensoreId = $_SESSION['userId'];

// Validazione base
if ($fkRecensitoId <= 0 || $valutazione < 1 || $valutazione > 5) {
    header('Location: publicProfile.php?id=' . $fkRecensitoId . '&review_error=dati_non_validi');
    exit;
}

if ($fkRecensoreId === $fkRecensitoId) {
    header('Location: publicProfile.php?id=' . $fkRecensitoId . '&review_error=self_review');
    exit;
}

try {
    // Controlla se l'utente ha già recensito questo profilo
    $sqlCheck = "SELECT 1 FROM RecensioneUtente WHERE fkRecensoreId = :recensoreId AND fkRecensitoId = :recensitoId";
    $sthCheck = DBHandler::getPDO()->prepare($sqlCheck);
    $sthCheck->execute([':recensoreId' => $fkRecensoreId, ':recensitoId' => $fkRecensitoId]);

    if ($sthCheck->fetch()) {
        header('Location: publicProfile.php?id=' . $fkRecensitoId . '&review_error=gia_recensito');
        exit;
    }

    // Inserisci la recensione
    $sql = "INSERT INTO RecensioneUtente (fkRecensoreId, fkRecensitoId, valutazione, commento)
            VALUES (:recensoreId, :recensitoId, :valutazione, :commento)";
    $sth = DBHandler::getPDO()->prepare($sql);
    $sth->execute([
        ':recensoreId' => $fkRecensoreId,
        ':recensitoId' => $fkRecensitoId,
        ':valutazione' => $valutazione,
        ':commento'    => $commento
    ]);

    header('Location: publicProfile.php?id=' . $fkRecensitoId . '&review_success=1');
    exit;

} catch (PDOException $e) {
    die("Errore del database: " . $e->getMessage());
}
