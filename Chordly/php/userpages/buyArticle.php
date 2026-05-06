<?php
require_once('../include/menuchoice.php');

// Diciamo che la nostra risposta sarà in formato JSON per far andare il javascript di articledetail.js
header('Content-Type: application/json');

// Se l'utente non è loggato, blocchiamo tutto
if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'error' => 'Non autenticato. Effettua il login per acquistare.']);
    exit;
}

$idArticolo = isset($_POST['articleId']) ? (int)$_POST['articleId'] : 0;

$idCompratore = $_SESSION['userId'];

if ($idArticolo <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID articolo non valido.']);
    exit;
}

//mi salvo la connessione
$pdo = DBHandler::getPDO();

try {
    // verifico che l'articolo sia ancora disponibile e che non sia dell'utente loggato

    $sqlControllo = "SELECT fkUtenteId, disponibilita FROM ArticoloInVendita WHERE idArticolo = :articleId";

    $istruzioneControllo = $pdo->prepare($sqlControllo);

    $istruzioneControllo->bindParam(':articleId', $idArticolo, PDO::PARAM_INT);

    $istruzioneControllo->execute();

    $articolo = $istruzioneControllo->fetch();








    if (!$articolo) {
        echo json_encode(['success' => false, 'error' => 'Articolo non trovato.']);
        exit;
    }

    if (!$articolo['disponibilita']) {
        echo json_encode(['success' => false, 'error' => 'Questo articolo è già stato venduto a qualcun altro.']);
        exit;
    }

    if ((int)$articolo['fkUtenteId'] === $idCompratore) {
        echo json_encode(['success' => false, 'error' => 'Non puoi acquistare un tuo stesso articolo.']);
        exit;
    }

    // ==========================================
    // INIZIO TRANSAZIONE 
    // ==========================================

    $pdo->beginTransaction();





    // Operazione 1: segno articolo come venduto
    $sqlA = "UPDATE ArticoloInVendita SET disponibilita = FALSE WHERE idArticolo = :articleId";
    $istruzioneA = $pdo->prepare($sqlA);
    $istruzioneA->bindParam(':articleId', $idArticolo, PDO::PARAM_INT);
    $istruzioneA->execute();



    // Operazione 2: Creazione riga nella tabella Acquisti
    $sqlB = "INSERT INTO Acquisti (fkAcquirenteId, fkArticoloId) VALUES (:buyerId, :articleId)";
    $istruzioneB = $pdo->prepare($sqlB);
    $istruzioneB->execute([':buyerId' => $idCompratore, ':articleId' => $idArticolo]);

    // FINE TRANSAZIONE
    $pdo->commit();

    // Rispondiamo al JavaScript che tutto è andato bene
    echo json_encode(['success' => true, 'message' => 'Hai acquistato l\'articolo']);


} catch (PDOException $e) {
    // ERRORE TRANSAZIONE: Se si è verificato un errore in OperazAione A o B...
    // "rollBack()" dice al database: "Fermo! Annulla le ultime operazioni e torna come prima."
    $pdo->rollBack();
    
    echo json_encode(['success' => false, 'error' => 'Errore tecnico durante l\'acquisto: ' . $e->getMessage()]);
}
?>

