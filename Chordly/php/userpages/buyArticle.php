<?php
require_once('../include/menuchoice.php');

header('Content-Type: application/json');

if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'error' => 'Non autenticato. Effettua il login per acquistare.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'error' => 'Metodo di richiesta non valido.']);
    exit;
}

$articleId = isset($_POST['articleId']) ? (int)$_POST['articleId'] : 0;
$buyerId   = $_SESSION['userId'];

if ($articleId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID articolo non valido.']);
    exit;
}

$pdo = DBHandler::getPDO();

try {
    // Verifica che l'articolo esista, sia disponibile e non sia del compratore
    $sqlCheck = "SELECT fkUtenteId, disponibilita FROM ArticoloInVendita WHERE idArticolo = :articleId";
    $sthCheck = $pdo->prepare($sqlCheck);
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
    if ((int)$article['fkUtenteId'] === $buyerId) {
        echo json_encode(['success' => false, 'error' => 'Non puoi acquistare il tuo stesso articolo.']);
        exit;
    }

    // TRANSAZIONE: le due operazioni devono avere successo insieme
    // o entrambe vengono annullate (rollback)
    $pdo->beginTransaction();

    // 1. Segna l'articolo come non disponibile
    $sql1 = "UPDATE ArticoloInVendita SET disponibilita = FALSE WHERE idArticolo = :articleId";
    $sth1 = $pdo->prepare($sql1);
    $sth1->bindParam(':articleId', $articleId, PDO::PARAM_INT);
    $sth1->execute();

    // 2. Registra l'acquisto nella tabella Acquisti
    $sql2 = "INSERT INTO Acquisti (fkAcquirenteId, fkArticoloId) VALUES (:buyerId, :articleId)";
    $sth2 = $pdo->prepare($sql2);
    $sth2->execute([':buyerId' => $buyerId, ':articleId' => $articleId]);

    // Tutto ok: conferma entrambe le operazioni
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Articolo acquistato con successo!']);

} catch (PDOException $e) {
    // Qualcosa è andato storto: annulla tutto
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Errore durante l\'acquisto: ' . $e->getMessage()]);
}
