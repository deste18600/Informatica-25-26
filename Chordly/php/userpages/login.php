<?php
require_once('../include/menuchoice.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $emailInserita = trim($_POST['email']);

    $passwordInserita = $_POST['password'];


    $sql = "SELECT idUtente, password FROM Utente WHERE email = :email";
    

    try {

        $istruzione = DBHandler::getPDO()->prepare($sql);
        
        $istruzione->bindParam(':email', $emailInserita, PDO::PARAM_STR);

        $istruzione->execute();


    // controllo se è stato trovato un utente con quell'email e lo salviamo in una variabile
        $utenteTrovato = $istruzione->fetch();

        if ($utenteTrovato) {

            if (password_verify($passwordInserita, $utenteTrovato['password'])) {
                
                // Salva [userid] nella sessione 
                $_SESSION['userId'] = $utenteTrovato['idUtente'];
                
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
?>