<?php
require_once('../include/menuchoice.php');

// avvisa che la risposta sarà in formato JSON per far comprendere javascript di articledetail.js
header('Content-Type: application/json');

// Se l'utente non è loggato, blocco
if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'error' => 'Non autenticato. Effettua il login per acquistare.']);
    exit;
}

// verifica se nel post è arrivato un articleId
if (isset($_POST['articleId'])) {
    
    // se esite = int
    $idArticolo = (int)$_POST['articleId'];

} else {
    
    $idArticolo = 0;

}

$idCompratore = $_SESSION['userId'];

if ($idArticolo == 0) {
    echo json_encode(['success' => false, 'error' => 'ID articolo non valido.']);
    exit;
}

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



    // INIZIO TRANSAZIONE 
   
    $pdo->beginTransaction();


    // segno articolo come venduto
    $sqlA = "UPDATE ArticoloInVendita SET disponibilita = FALSE WHERE idArticolo = :articleId";
    $istruzioneA = $pdo->prepare($sqlA);
    $istruzioneA->bindParam(':articleId', $idArticolo, PDO::PARAM_INT);
    $istruzioneA->execute();



    // Creazione riga nella tabella Acquisti
    $sqlB = "INSERT INTO Acquisti (fkAcquirenteId, fkArticoloId) VALUES (:buyerId, :articleId)";
    $istruzioneB = $pdo->prepare($sqlB);
    $istruzioneB->execute([':buyerId' => $idCompratore, ':articleId' => $idArticolo]);

    // FINE TRANSAZIONE
    $pdo->commit();

    // Risposta al JavaScript che tutto è andato bene
    echo json_encode(['success' => true, 'message' => 'Hai acquistato l\'articolo']);


} catch (PDOException $e) {
    $pdo->rollBack();
    
    echo json_encode(['success' => false, 'error' => 'Errore tecnico durante l\'acquisto: ' . $e->getMessage()]);
}
?>

