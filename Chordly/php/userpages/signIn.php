<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $cognome = $_POST["cognome"];
    $email = $_POST["mail"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT); 

    try {
        $sql = "INSERT INTO Utente (nome, cognome, email, password) VALUES (:nome, :cognome, :email, :password)";
        $sth = DBHandler::getPDO()->prepare($sql);
        
        $sth->execute([
            ':nome' => $nome,
            ':cognome' => $cognome,
            ':email' => $email,
            ':password' => $password
        ]);

        // Se la registrazione ha successo, vai al login
        header("Location: userLoginpage.php?reg=success");
        exit();
    } catch (PDOException $e) {

        // Gestione errore (email già esistente)
        header("Location: userSigninPage.php?error=email_esistente");
        exit();
    }
}
?>