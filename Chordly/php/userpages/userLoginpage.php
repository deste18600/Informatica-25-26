<?php
require_once('../include/menuchoice.php');
?>






<!DOCTYPE html>
<html lang="it">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/userLoginPage.css">
    <title>Login - Chordly</title>
</head>
<body>

    <div class="login-container">
        <h2>Accedi a Chordly</h2>
        
        <?php

        // la funzione isset() restituisce "error" stampiamo l'avviso di errore in rosso.
        if (isset($_GET['error']))
        if (isset($_GET['error'])) {
            echo '<p style="color: #ff6b6b; text-align: center; margin-bottom: 15px;">Errore: controlla email e password e riprova.</p>';
        }

        // Se arriva da una registrazione andata a buon fine, mostriamo un avviso verde.
        if (isset($_GET['reg']) && $_GET['reg'] == 'success') {
            echo '<p style="color: #5cc877; text-align: center; margin-bottom: 15px;">Registrazione completata! Ora puoi accedere.</p>';
        }
        ?>
                              
        <form action="login.php" id="userLoginForm" method="POST"> 
            
            <div class="input-group">
                <label for="email">Email</label>
               
                <input type="email" id="email" name="email" placeholder="latua@email.it" required>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <!-- type="password" nasconde automaticamente i caratteri con dei pallini  -->
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            




            <div class="buttons">
              
                <button type="submit" class="btn">Accedi</button>
            </div>

            <div class="footer-link">
                <p>Non hai un account? <a href="userSigninPage.php" style="color: white; font-weight: bold;">Registrati</a></p>
            </div>
        </form>
    </div>

</body>
</html>