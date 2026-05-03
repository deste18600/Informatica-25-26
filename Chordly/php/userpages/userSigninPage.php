<?php
// Richiamiamo il file centrale
require_once('../include/menuchoice.php');
?>
<!DOCTYPE html>
<html lang="it">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/userSigninPage.css">
    <title>Registrazione - Chordly</title>
</head>
<body>

    <div class="signin-container">
        <h2>Crea il tuo Account</h2>
        
        <?php
        // EXTRA: Mostriamo un messaggio di errore in rosso se l'email risulta già registrata nel database
        if (isset($_GET['error']) && $_GET['error'] == 'email_esistente') {
            echo '<p style="color: #ff6b6b; text-align: center; margin-bottom: 15px;">Questa email è già registrata. Prova ad accedere.</p>';
        }
        ?>

        <!-- Inviamo i dati al file signIn.php che abbiamo preparato prima -->
        <form action="signIn.php" id="userSigninForm" method="POST"> 
            
            <!-- Raggruppiamo Nome e Cognome sulla stessa riga -->
            <div class="input-row">
                <div class="input-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" placeholder="Nome" required>
                </div>
                <div class="input-group">
                    <label for="cognome">Cognome</label>
                    <input type="text" id="cognome" name="cognome" placeholder="Cognome" required>
                </div>
            </div>

            <div class="input-group">
                <label for="email">Email</label>
                <!-- Attenzione al name="mail": corrisponde esattamente a $_POST["mail"] che usi in signIn.php -->
                <input type="email" id="email" name="mail" placeholder="email@esempio.it" required>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Scegli una password" required>
            </div>
            
            <button type="submit" class="btn">Conferma e Registrati</button>

            <div class="footer-link">
                <p>Hai già un account? <a href="userLoginPage.php">Accedi qui</a></p>
            </div>
        </form>
    </div>

</body>
</html>