<?php
require_once('../include/menuchoice.php');

header('Content-Type: application/json'); // Risposta JSON

if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'error' => 'Non autenticato. Effettua il login per lasciare una recensione.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fkRecensitoId = isset($_POST['fkRecensitoId']) ? (int)$_POST['fkRecensitoId'] : 0;
    $valutazione = isset($_POST['valutazione']) ? (int)$_POST['valutazione'] : 0;
    $commento = trim($_POST['commento'] ?? '');
    $fkRecensoreId = $_SESSION['userId'];

    // Validazione
    if ($fkRecensitoId <= 0 || $valutazione < 1 || $valutazione > 5) {
        echo json_encode(['success' => false, 'error' => 'Dati della recensione non validi.']);
        exit;
    }

    if ($fkRecensoreId === $fkRecensitoId) {
        echo json_encode(['success' => false, 'error' => 'Non puoi recensire te stesso.']);
        exit;
    }

    try {
        // Controlla se l'utente ha già recensito questo profilo
        $sqlCheck = "SELECT 1 FROM RecensioneUtente WHERE fkRecensoreId = :recensoreId AND fkRecensitoId = :recensitoId";
        $sthCheck = DBHandler::getPDO()->prepare($sqlCheck);
        $sthCheck->execute([':recensoreId' => $fkRecensoreId, ':recensitoId' => $fkRecensitoId]);
        if ($sthCheck->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Hai già lasciato una recensione per questo utente.']);
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

        // Reindirizza alla pagina del profilo pubblico dopo l'invio
        header('Location: publicProfile.php?id=' . $fkRecensitoId . '&review_success=true');
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Errore del database: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Metodo di richiesta non valido.']);
}
?>