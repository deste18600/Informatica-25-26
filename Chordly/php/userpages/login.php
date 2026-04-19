<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $insertedPassword = $_POST['password'];

    $sql = "SELECT idUtente, password FROM Utente WHERE email = :email";
    
    try {
        $sth = DBHandler::getPDO()->prepare($sql);
        $sth->bindParam(':email', $email, PDO::PARAM_STR);
        $sth->execute();

        $user = $sth->fetch();

        if ($user) {
            if (password_verify($insertedPassword, $user['password'])) {
                $_SESSION['userId'] = $user['idUtente'];
                header('Location: mainPage.php');
                exit;
            } else {
                header('Location: userLoginpage.php?error=password_errata');
                exit;
            }
        } else {
            header('Location: userLoginpage.php?error=utente_non_trovato');
            exit;
        }
    } catch (PDOException $e) {
        die("Errore nel login: " . $e->getMessage());
    }
}