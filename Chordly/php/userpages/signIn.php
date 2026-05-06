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
       // Prepariamo il comando SQL per inserire un nuovo utente.
        // Al posto di inserire le variabili direttamente nella stringa, usiamo i "segnaposto" questo blocca sul nascere gli attacchi "SQL Injection".
        $sql = "INSERT INTO Utente (nome, cognome, email, password) 
                VALUES (:nome, :cognome, :email, :password)";
        

        // Prepariamo la query
        $istruzione = DBHandler::getPDO()->prepare($sql);
        
        // Sostituiamo i segnaposto con i dati veri e inviamo il comando
        $istruzione->execute([
            ':nome' => $nome,
            ':cognome' => $cognome,
            ':email' => $email,
            ':password' => $passwordCriptata
        ]);

        // Se tutto ok lo porto alla login page
        header("Location: userLoginpage.php?reg=success");
        
        // fermiamo tutto dopo l'header per evitare che venga eseguito altro codice
        exit(); 

    } catch (PDOException $e) {
        // Se si verifica un errore qui
        // Lo rimandiamo indietro con un avviso.
        header("Location: userSigninPage.php?error=email_esistente");
        exit();
    }
}
?>