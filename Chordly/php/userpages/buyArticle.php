<?php
require_once('../include/menuchoice.php');

header('Content-Type: application/json'); // Risposta JSON

if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'error' => 'Non autenticato. Effettua il login per acquistare.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $articleId = isset($_POST['articleId']) ? (int)$_POST['articleId'] : 0;
    $buyerId = $_SESSION['userId'];

    if ($articleId <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID articolo non valido.']);
        exit;
    }

    try {
        // Verifica che l'articolo esista, sia disponibile e non sia del compratore
        $sqlCheck = "SELECT fkUtenteId, disponibilita FROM ArticoloInVendita WHERE idArticolo = :articleId";
        $sthCheck = DBHandler::getPDO()->prepare($sqlCheck);
        $sthCheck->bindParam(':articleId', $articleId, PDO::PARAM_INT);
        $sthCheck->execute();
        $article = $sthCheck->fetch();

        if (!$article) {
            echo json_encode(['success' => false, 'error' => 'Articolo non trovato.']);
            exit;
        }
        if (!$article['disponibilita']) {
            echo json_encode(['success' => false, 'error' => 'Articolo già venduto o non disponibile.']);
            exit;
        }
        if ($article['fkUtenteId'] === $buyerId) {
            echo json_encode(['success' => false, 'error' => 'Non puoi acquistare il tuo stesso articolo.']);
            exit;
        }

        // Aggiorna la disponibilità dell'articolo a FALSE
        $sql = "UPDATE ArticoloInVendita SET disponibilita = FALSE WHERE idArticolo = :articleId";
        $sth = DBHandler::getPDO()->prepare($sql);
        $sth->bindParam(':articleId', $articleId, PDO::PARAM_INT);
        $sth->execute();

        echo json_encode(['success' => true, 'message' => 'Articolo acquistato con successo!']);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Errore del database durante l\'acquisto: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Metodo di richiesta non valido.']);
}
?>