<?php
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

        if (isset($_GET['error']) && $_GET['error'] == 'email_esistente') {
            echo '<p style="color: #ff6b6b; text-align: center; margin-bottom: 15px;">Questa email è già registrata. Prova ad accedere.</p>';
        }
        ?>

        <!-- Invia i dati al file signIn.php -->
        <form action="signIn.php" id="userSigninForm" method="POST"> 
            
             <!-- imput-row per mettere nome e cognome sulla stessa linea-->
    
             <!-- input group per gestire la logica del singolo componente  -->
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

                <!-- imput group per mettere email e password su linee separate,  -->
                 
               

            <div class="input-group"> 
                <label for="email">Email</label>

                <!-- L'attributo name="mail" corrisponde a $_POST["mail"] in signIn.php  metto sul placeholder un essempio di mail-->
                <input type="email" id="email" name="mail" placeholder="email@esempio.it" required>
            </div>
            
             <!-- imput group per mettere email e password su linee separate -->
            <div class="input-group">
                <label for="password">Password</label>

                 <!-- L'attributo name="password" è importante: corrisponde a $_POST["password"] in signIn.php e metto placeholder -->
                <input type="password" id="password" name="password" placeholder="Scegli una password" required>
            </div>
            
             <!-- bottone di submit per inviare a signIn.php -->
            <button type="submit" class="btn">Conferma e Registrati</button>

            <div class="footer-link">
                <p>Hai già un account? <a href="userLoginPage.php">Accedi qui</a></p>
            </div>
        </form>
    </div>

</body>
</html>