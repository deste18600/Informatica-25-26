<?php
require_once('../include/menuchoice.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nome = trim($_POST["nome"]); 
    $cognome = trim($_POST["cognome"]);
    $email = trim($_POST["mail"]);
    

    // password_hash() genera una stringa illeggibile e sicura. 
    // PASSWORD_DEFAULT dice a PHP di usare l'algoritmo di crittografia

    $passwordCriptata = password_hash($_POST["password"], PASSWORD_DEFAULT); 



    try {
        //  comando SQL inserimento utente.
        // uso "segnaposto" per attacchi "SQL Injection".
        $sql = "INSERT INTO Utente (nome, cognome, email, password) 
                VALUES (:nome, :cognome, :email, :password)";
        

        $istruzione = DBHandler::getPDO()->prepare($sql);
        
        // Sostituiamo i segnaposto con i dati 
        $istruzione->execute([
            ':nome' => $nome,
            ':cognome' => $cognome,
            ':email' => $email,
            ':password' => $passwordCriptata
        ]);


        header("Location: userLoginpage.php?reg=success");
        
        exit(); 

    } catch (PDOException $e) {
        header("Location: userSigninPage.php?error=email_esistente");
        exit();
    }
}
?>