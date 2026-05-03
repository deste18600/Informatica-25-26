<?php
// 1. Richiamiamo il controllore
require_once('../include/menuchoice.php');

// 2. Diciamo al browser: "Attenzione, sto per parlarti nella lingua dei computer (JSON), non in HTML!"
header('Content-Type: application/json');

// Controllo sicurezza: sei loggato?
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
    echo json_encode(['success' => false, 'error' => 'Azione non valida.']);
    exit;
}

try {
    // Scegliamo quale comando SQL usare in base all'azione richiesta
    if ($azioneRichiesta === 'follow') {
        // "INSERT IGNORE" è un trucco fantastico: se cerchi di seguire una persona che segui GIÀ, 
        // non va in errore, semplicemente ignora l'inserimento ed evita doppioni nel database!
        $sql = "INSERT IGNORE INTO Segue (idFollower, idSeguito) VALUES (:me, :lui)";
    } 
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

    // Rispondiamo al JavaScript confermando che è andato tutto a buon fine
    echo json_encode(['success' => true, 'action' => $azioneRichiesta]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>