<?php
// Richiamiamo il nostro controllore centrale che decide cosa caricare e avvia la sessione
require_once('../include/menuchoice.php');

// Controlliamo se la pagina è stata attivata premendo il pulsante "Registrati" (metodo POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Raccogliamo i dati inseriti nel modulo. 
    // Uso trim() per tagliare via eventuali spazi vuoti messi per sbaglio all'inizio o alla fine.
    $nome = trim($_POST["nome"]); 
    $cognome = trim($_POST["cognome"]);
    $email = trim($_POST["mail"]);
    
    // Sicurezza: non salviamo MAI la password in chiaro. La trasformiamo in un codice illeggibile.
    $passwordCriptata = password_hash($_POST["password"], PASSWORD_DEFAULT); 

    try {
        // Prepariamo la richiesta per il database con dei "segnaposto" (es: :nome)
        $sql = "INSERT INTO Utente (nome, cognome, email, password) 
                VALUES (:nome, :cognome, :email, :password)";
        
        $istruzione = DBHandler::getPDO()->prepare($sql);
        
        // Sostituiamo i segnaposto con i dati veri e inviamo il comando
        $istruzione->execute([
            ':nome' => $nome,
            ':cognome' => $cognome,
            ':email' => $email,
            ':password' => $passwordCriptata
        ]);

        // Se l'inserimento va a buon fine, rimandiamo l'utente alla pagina di login per farlo accedere
        header("Location: userLoginpage.php?reg=success");
        
        // IMPORTANTISSIMO: usiamo sempre exit dopo un header() per fermare l'esecuzione!
        exit(); 

    } catch (PDOException $e) {
        // Se si verifica un errore qui, nel 99% dei casi significa che l'email 
        // è già presente nel database (avendo tu probabilmente impostato l'email come UNIQUE in SQL)
        // Lo rimandiamo indietro con un avviso.
        header("Location: userSigninPage.php?error=email_esistente");
        exit();
    }
}
?>