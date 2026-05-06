<?php
require_once('../include/menuchoice.php');

// Diciamo che la nostra risposta sarà in formato JSON per far andare il javascript di articledetail.js
header('Content-Type: application/json');

//controllo user id loggato al momento
if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'error' => 'Non autenticato.']);
    exit;
}

$idUtenteLoggato = $_SESSION['userId'];
$idDaSeguire     = isset($_POST['idSeguito']) ? (int)$_POST['idSeguito'] : 0;
// Leggiamo se l'azione richiesta è "follow" (segui) o "unfollow" (smetti di seguire)
$azioneRichiesta = $_POST['action'] ?? '';

// Controllo sicurezza: Non puoi seguire te stesso (sarebbe l'ID uguale) o un ID inesistente
if ($idDaSeguire <= 0 || $idDaSeguire === $idUtenteLoggato) {

//echo json_encode traduce da array a stringa JSON, che è un formato che il JavaScript può capire 
    echo json_encode(['success' => false, 'error' => 'Azione non valida.']);
    exit;
}

try {
    
    //se devo seguire
    if ($azioneRichiesta === 'follow') {

        //inserisco una riga nella tabella segue mausando ignore se esiste già non faccio niente
        $sql = "INSERT IGNORE INTO Segue (idFollower, idSeguito) VALUES (:me, :lui)";
    } 
    //se devo smettere di seguire
    elseif ($azioneRichiesta === 'unfollow') {
        $sql = "DELETE FROM Segue WHERE idFollower = :me AND idSeguito = :lui";
    } 
    else {
        // Se qualcuno prova a mandare un'azione strana (es. action=distruggi_tutto)
        echo json_encode(['success' => false, 'error' => 'Comando sconosciuto.']);
        exit;
    }

    $istruzione = DBHandler::getPDO()->prepare($sql);
    $istruzione->execute([':me' => $idUtenteLoggato, ':lui' => $idDaSeguire]);

    // Risposta al JavaScript confermando che è andato tutto a buon fine
    echo json_encode(['success' => true, 'action' => $azioneRichiesta]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>