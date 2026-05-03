<?php
// 1. Richiamiamo il controllore
require_once('../include/menuchoice.php');

// Se non sei loggato, via!
if (!isset($_SESSION['userId'])) {
    header('Location: userLoginpage.php');
    exit;
}

// Se cerchi di caricare questa pagina digitando l'url invece di premere il bottone "Invia" nel form
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: mainPage.php');
    exit;
}

// Raccogliamo i dati inviati dal modulo (id dell'utente recensito, le stelle e il testo)
$idUtenteRecensito = isset($_POST['fkRecensitoId']) ? (int)$_POST['fkRecensitoId'] : 0;
$valutazione       = isset($_POST['valutazione'])   ? (int)$_POST['valutazione']   : 0;
$commento          = trim($_POST['commento'] ?? '');
$idRecensore       = $_SESSION['userId'];

// === CONTROLLI DI SICUREZZA (VALIDAZIONE) ===

// A. I dati hanno senso? L'ID deve essere > 0, e il voto tra 1 e 5 stelle.
if ($idUtenteRecensito <= 0 || $valutazione < 1 || $valutazione > 5) {
    // Lo rimandiamo indietro con un errore
    header('Location: publicProfile.php?id=' . $idUtenteRecensito . '&review_error=dati_non_validi');
    exit;
}

// B. Stai cercando di recensire te stesso?
if ($idRecensore === $idUtenteRecensito) {
    header('Location: publicProfile.php?id=' . $idUtenteRecensito . '&review_error=self_review');
    exit;
}

try {
    // C. Hai GIÀ recensito questa persona? (Le regole dicono una recensione a testa!)
    $sqlControllo = "SELECT 1 FROM RecensioneUtente WHERE fkRecensoreId = :recensoreId AND fkRecensitoId = :recensitoId";
    $istruzioneControllo = DBHandler::getPDO()->prepare($sqlControllo);
    $istruzioneControllo->execute([':recensoreId' => $idRecensore, ':recensitoId' => $idUtenteRecensito]);

    if ($istruzioneControllo->fetch()) {
        header('Location: publicProfile.php?id=' . $idUtenteRecensito . '&review_error=gia_recensito');
        exit;
    }

    // === SALVATAGGIO DELLA RECENSIONE ===
    // Se ha superato tutti i controlli (A, B, e C), salviamo la recensione nel database.
    $sql = "INSERT INTO RecensioneUtente (fkRecensoreId, fkRecensitoId, valutazione, commento)
            VALUES (:recensoreId, :recensitoId, :valutazione, :commento)";
    
    $istruzione = DBHandler::getPDO()->prepare($sql);
    $istruzione->execute([
        ':recensoreId' => $idRecensore,
        ':recensitoId' => $idUtenteRecensito,
        ':valutazione' => $valutazione,
        ':commento'    => $commento
    ]);

    // Riportiamo l'utente sul profilo che ha appena recensito, attivando il messaggio "success=1" verde
    header('Location: publicProfile.php?id=' . $idUtenteRecensito . '&review_success=1');
    exit;

} catch (PDOException $e) {
    die("Errore del database durante l'inserimento della recensione: " . $e->getMessage());
}
?>