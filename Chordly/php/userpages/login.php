<?php
// Richiamiamo il controllore centrale
require_once('../include/menuchoice.php');

// Verifichiamo che il form sia stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Raccogliamo i dati inseriti dall'utente
    $emailInserita = trim($_POST['email']);
    $passwordInserita = $_POST['password'];

    // Prepariamo la query: ci serve solo sapere se esiste l'ID e qual è la sua password criptata
    $sql = "SELECT idUtente, password FROM Utente WHERE email = :email";
    
    try {
        $istruzione = DBHandler::getPDO()->prepare($sql);
        $istruzione->bindParam(':email', $emailInserita, PDO::PARAM_STR);
        $istruzione->execute();

        // fetch() estrae la prima riga trovata nel database (se esiste)
        $utenteTrovato = $istruzione->fetch();

        // 1. Controllo: L'utente esiste?
        if ($utenteTrovato) {
            
            // 2. Controllo: La password scritta corrisponde a quella criptata che abbiamo salvato?
            if (password_verify($passwordInserita, $utenteTrovato['password'])) {
                
                // LOGIN RIUSCITO! 
                // Salviamo la sua "carta d'identità" nella sessione per ricordarci di lui
                $_SESSION['userId'] = $utenteTrovato['idUtente'];
                
                // Entriamo nell'app
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
        // Se il database cade o c'è un errore tecnico, fermiamo tutto
        die("Errore nel login: " . $e->getMessage());
    }
}
?>