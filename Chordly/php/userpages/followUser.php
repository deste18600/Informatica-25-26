<?php
header('Content-Type: application/json');
require_once('../include/menuchoice.php');
if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'error' => 'Non autenticato']);
    exit;
}

$userId    = $_SESSION['userId'];
$idSeguito = isset($_POST['idSeguito']) ? (int)$_POST['idSeguito'] : 0;
$action    = $_POST['action'] ?? '';

if ($idSeguito <= 0 || $idSeguito === $userId) {
    echo json_encode(['success' => false, 'error' => 'ID non valido']);
    exit;
}

try {
    if ($action === 'follow') {
        $sql = "INSERT IGNORE INTO Segue (idFollower, idSeguito) VALUES (:me, :lui)";
    } elseif ($action === 'unfollow') {
        $sql = "DELETE FROM Segue WHERE idFollower = :me AND idSeguito = :lui";
    } else {
        echo json_encode(['success' => false, 'error' => 'Azione non valida']);
        exit;
    }

    $sth = DBHandler::getPDO()->prepare($sql);
    $sth->execute([':me' => $userId, ':lui' => $idSeguito]);

    echo json_encode(['success' => true, 'action' => $action]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}