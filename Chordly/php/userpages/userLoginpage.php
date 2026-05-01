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
        
        <form action="login.php" id="userLoginForm" method="POST"> 
            
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="latua@email.it" required>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
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