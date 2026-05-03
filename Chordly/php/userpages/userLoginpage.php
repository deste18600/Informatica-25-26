<?php
// Richiamiamo il file centrale che gestisce sessioni e database
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
        // EXTRA: Questo piccolo blocco intercetta gli errori o i successi in arrivo!
        // Se c'è un errore, mostriamo un avviso in rosso.
        if (isset($_GET['error'])) {
            echo '<p style="color: #ff6b6b; text-align: center; margin-bottom: 15px;">Errore: controlla email e password e riprova.</p>';
        }
        // Se arriva da una registrazione andata a buon fine, mostriamo un avviso verde.
        if (isset($_GET['reg']) && $_GET['reg'] == 'success') {
            echo '<p style="color: #5cc877; text-align: center; margin-bottom: 15px;">Registrazione completata! Ora puoi accedere.</p>';
        }
        ?>

        <!-- 
          Il tag <form> raccoglie i dati inseriti. 
          action="login.php": dice al browser di mandare i dati a quel file quando si preme il bottone.
          method="POST": impacchetta i dati in modo "nascosto", fondamentale per non mostrare le password nell'indirizzo web.
        -->
        <form action="login.php" id="userLoginForm" method="POST"> 
            
            <div class="input-group">
                <label for="email">Email</label>
                <!-- 
                  L'attributo "name" è vitale: è il nome con cui PHP riconoscerà questo dato ($_POST['email']).
                  L'attributo "required" impedisce di inviare il modulo se il campo è vuoto. 
                -->
                <input type="email" id="email" name="email" placeholder="latua@email.it" required>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <!-- type="password" nasconde automaticamente i caratteri con dei pallini mentre scrivi -->
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            
            <div class="buttons">
                <!-- type="submit" è il pulsante speciale che fa partire l'invio del form -->
                <button type="submit" class="btn">Accedi</button>
            </div>

            <div class="footer-link">
                <p>Non hai un account? <a href="userSigninPage.php" style="color: white; font-weight: bold;">Registrati</a></p>
            </div>
        </form>
    </div>

</body>
</html>