<?php

$userId = $_SESSION['userId'];

// Questa query prende tutti gli utenti che hai scelto di seguire
// e conta quanti articoli hanno in vendita
try {
    $sql = "SELECT u.idUtente, u.nome, u.cognome, u.email, COUNT(a.idArticolo) as numArticoli
            FROM Segue s
            JOIN Utente u ON s.idSeguito = u.idUtente
            LEFT JOIN ArticoloInVendita a ON u.idUtente = a.fkUtenteId AND a.disponibilita = TRUE
            WHERE s.idFollower = :userId
            GROUP BY u.idUtente
            ORDER BY u.nome";
    
    $sth = DBHandler::getPDO()->prepare($sql);
    $sth->bindParam(':userId', $userId, PDO::PARAM_INT);
    $sth->execute();
    // fetchAll() prende tutti i risultati
    $seguiti = $sth->fetchAll();
} catch (PDOException $e) {
    die("Errore: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/followingPage.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <title>Profili seguiti - Chordly</title>
</head>
<body>

    <!-- Barra di navigazione-->
    <nav class="navbar">
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>
        <a href="mainPage.php" class="btn-back">← Torna alla home</a>
    </nav>

    <!-- CONTENUTO PRINCIPALE -->
    <main class="container">
        <h1>Profili che stai seguendo</h1>
        
        <!--  LISTA DI SEGUITI-->
        <div class="seguiti-list">
            
            <!--  SE NON STAI SEGUENDO NESSUNO -->
            <?php if (empty($seguiti)): ?>
                <div class="empty-state">
                    <p>Non stai ancora seguendo nessuno</p>
                    <a href="mainPage.php" class="btn-primary">Scopri altri utenti</a>
                </div>
            
            <!-- SE STAI SEGUENDO QUALCUNO -->
            <?php else: ?>
                <!-- Per ogni utente che stai seguendo, mostra una card -->
                <?php foreach ($seguiti as $utente): ?>
                    <div class="seguiti-card">
                        <!-- PARTE SINISTRA: INFORMAZIONI UTENTE -->
                        <div class="user-info">
                            <!-- Avatar con le iniziali del nome e cognome -->
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($utente['nome'], 0, 1) . substr($utente['cognome'], 0, 1)); ?>
                            </div>
                            <!-- Nome, cognome e numero articoli -->
                            <div class="user-details">
                                <h3><?php echo htmlspecialchars($utente['nome'] . ' ' . $utente['cognome']); ?></h3>
                                <p><?php echo $utente['numArticoli']; ?> articoli in vendita</p>
                            </div>
                        </div>
                        
                        <!-- PARTE DESTRA: PULSANTI -->
                        <div class="user-actions">
                            <!-- Pulsante per smettere di seguire -->
                            <button class="btn-unfollow" onclick="unfollowUser(<?php echo $utente['idUtente']; ?>)">
                                Smetti di seguire
                            </button>
                            <!-- Pulsante per vedere il profilo dell'utente -->
                            <a href="#" class="btn-view">Vedi profilo</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>





    <!--  JAVASCRIPT DA PROVARE-->
    <script>
        // Questa funzione si attiva quando clicchi il pulsante "Smetti di seguire"
        function unfollowUser(userId) {
            // Chiedi conferma all'utente
            if (confirm('Smettere di seguire questo utente?')) {


                // Qui andrebbe una chiamata AJAX a un endpoint che gestisce l'unfollow
                // Per ora è solo un placeholder

                alert('Funzionalità da implementare');
            }
        }
    </script>

</body>
</html>
