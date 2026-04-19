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
        
        <form action="signIn.php" id="userSigninForm" method="POST"> 
            
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
                <input type="email" id="email" name="mail" placeholder="email@esempio.it" required>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Scegli una password" required>
            </div>
            
            <button type="submit" class="btn">Conferma e Registrati </button>

            <div class="footer-link">
                <p>Hai già un account? <a href="userLoginPage.php">Accedi qui</a></p>
            </div>
        </form>
    </div>

</body>
</html>