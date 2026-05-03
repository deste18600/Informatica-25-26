<?php
require_once('../include/menuchoice.php');

$userId = $_SESSION['userId'];

// VALIDAZIONE: controlla che l'ID esista e sia un numero
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: profilePage.php');
    exit;
}

$articleId = (int)$_GET['id'];

try {
    // Elimina l'articolo solo se appartiene all'utente loggato
    $sql = "DELETE FROM ArticoloInVendita WHERE idArticolo = :articleId AND fkUtenteId = :userId";
    $sth = DBHandler::getPDO()->prepare($sql);
    $sth->bindParam(':articleId', $articleId, PDO::PARAM_INT);
    $sth->bindParam(':userId', $userId, PDO::PARAM_INT);
    $sth->execute();

    header('Location: profilePage.php?status=success');
    exit;
} catch (PDOException $e) {
    die("Errore: " . $e->getMessage());
}
