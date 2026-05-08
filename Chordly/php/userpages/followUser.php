<?php
header('Content-Type: application/json');
require_once('../include/menuchoice.php');


$me  = $_SESSION['userId'] ?? 0;
$lui = (int)($_POST['idSeguito'] ?? 0);
$azione = $_POST['action'] ?? '';


// Se non sono loggato, se l'ID è sbagliato o se provo a seguire me stesso
if ($me <= 0 || $lui <= 0 || $me === $lui) {
    echo json_encode(['success' => false, 'error' => 'Operazione non consentita.']);
    exit;
}

try {
 // SCELTA DELLA QUERY 
$sql = null; 

if ($azione === 'follow') {
    $sql = "INSERT IGNORE INTO Segue (idFollower, idSeguito) VALUES (:me, :lui)";
} 
elseif ($azione === 'unfollow') {
    $sql = "DELETE FROM Segue WHERE idFollower = :me AND idSeguito = :lui";
}

//  $sql è ancora null, azione non corretta
if ($sql === null) {
    echo json_encode(['success' => false, 'error' => 'Azione non valida.']);
    exit;
}

    $stmt = DBHandler::getPDO()->prepare($sql);
    $stmt->execute([':me' => $me, ':lui' => $lui]);

    echo json_encode(['success' => true, 'azione' => $azione]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Errore database: ' . $e->getMessage()]);
}