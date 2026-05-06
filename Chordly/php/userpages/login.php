<?php
require_once('../include/menuchoice.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $emailInserita = trim($_POST['email']);

    $passwordInserita = $_POST['password'];


    $sql = "SELECT idUtente, password FROM Utente WHERE email = :email";
    

    try {

        // Chiediamo al database di "preparare" la query (Prepared Statement).

        $istruzione = DBHandler::getPDO()->prepare($sql);

        // Colleghiamo (bind) il segnaposto :email alla variabile PHP $emailInserita.
        // PDO::PARAM_STR avvisa il database che il dato in arrivo deve essere trattato strettamente come una stringa di testo
        
        $istruzione->bindParam(':email', $emailInserita, PDO::PARAM_STR);

        //esecuzione della query con i dati al posto dei segnaposto
        $istruzione->execute();

        // controllo se è stato trovato un utente con quell'email e lo salviamo in una variabile
        $utenteTrovato = $istruzione->fetch();

        // Controllo: L'utente esiste?
        if ($utenteTrovato) {
            
            // Controllo: La password scritta corrisponde a quella criptata che abbiamo salvato?
            if (password_verify($passwordInserita, $utenteTrovato['password'])) {
                
                // LOGIN RIUSCITO
                // Salva [userid] nella sessione 
                $_SESSION['userId'] = $utenteTrovato['idUtente'];
                
                header('Location: mainPage.php');
                exit;
                
            } else {
                // Errore: la password è sbagliata
                header('Location: userLoginpage.php?error=password_errata');
                exit;
            }
            
        } else {
            // Errore: l'email non è registrata
            header('Location: userLoginpage.php?error=utente_non_trovato');
            exit;
        }
        
    } catch (PDOException $e) {
        //in caso di errore 
        die("Errore nel login: " . $e->getMessage());
    }
}
?>