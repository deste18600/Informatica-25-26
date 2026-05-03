<?php
require_once('../include/menuchoice.php');

// Diciamo che la nostra risposta sarà in formato JSON (perfetto per farla leggere al JavaScript)
header('Content-Type: application/json');

// Se l'utente non è loggato, blocchiamo tutto
if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'error' => 'Non autenticato. Effettua il login per acquistare.']);
    exit;
}

// Se qualcuno prova ad accedere a questa pagina direttamente digitando l'url, blocchiamo tutto
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'error' => 'Metodo di richiesta non valido.']);
    exit;
}

$idArticolo = isset($_POST['articleId']) ? (int)$_POST['articleId'] : 0;
$idCompratore = $_SESSION['userId'];

if ($idArticolo <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID articolo non valido.']);
    exit;
}

// Prendiamo la connessione per poterla usare più volte in basso
$pdo = DBHandler::getPDO();

try {
    // 1. VERIFICA: l'articolo esiste? È ancora disponibile?
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
    // INIZIO TRANSAZIONE (Concetto fondamentale!)
    // ==========================================
    // Quando facciamo un acquisto, dobbiamo fare due cose nel database:
    // 1. Togliere l'articolo dalla vetrina (disponibilita = FALSE)
    // 2. Registrare la ricevuta (inserirlo nella tabella Acquisti)
    // Cosa succede se il database crasha tra il passo 1 e il passo 2?
    // L'articolo sparisce ma l'utente non lo riceve! 
    // "beginTransaction()" dice al database: "Prepara queste operazioni. O le fai TUTTE E DUE con successo, o annulli tutto e non fai niente."
    
    $pdo->beginTransaction();

    // Operazione A: Segniamo l'articolo come venduto (non più disponibile)
    $sqlA = "UPDATE ArticoloInVendita SET disponibilita = FALSE WHERE idArticolo = :articleId";
    $istruzioneA = $pdo->prepare($sqlA);
    $istruzioneA->bindParam(':articleId', $idArticolo, PDO::PARAM_INT);
    $istruzioneA->execute();

    // Operazione B: Creiamo la riga nella tabella Acquisti
    $sqlB = "INSERT INTO Acquisti (fkAcquirenteId, fkArticoloId) VALUES (:buyerId, :articleId)";
    $istruzioneB = $pdo->prepare($sqlB);
    $istruzioneB->execute([':buyerId' => $idCompratore, ':articleId' => $idArticolo]);

    // FINE TRANSAZIONE: Se siamo arrivati fin qui, non ci sono stati errori!
    // "commit()" dice al database: "Okay, salva tutto definitivamente!"
    $pdo->commit();

    // Rispondiamo al JavaScript che tutto è andato bene
    echo json_encode(['success' => true, 'message' => 'Complimenti! Hai acquistato l\'articolo con successo!']);

} catch (PDOException $e) {
    // ERRORE TRANSAZIONE: Se si è verificato un errore in Operazione A o B...
    // "rollBack()" dice al database: "Fermo! Annulla le ultime operazioni e torna come prima."
    $pdo->rollBack();
    
    echo json_encode(['success' => false, 'error' => 'Errore tecnico durante l\'acquisto: ' . $e->getMessage()]);
}
?>