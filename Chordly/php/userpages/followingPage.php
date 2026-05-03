<?php
// 1. Richiamiamo il controllore
require_once('../include/menuchoice.php');

$idUtenteLoggato = $_SESSION['userId'];

try {
    // 2. UNA QUERY "AVANZATA" MA MOLTO POTENTE
    // Questa query fa tre cose insieme:
    // a) Trova chi segui (nella tabella Segue)
    // b) Prende i loro nomi (nella tabella Utente)
    // c) Conta quanti articoli hanno in vendita (nella tabella ArticoloInVendita)
    // Usiamo "LEFT JOIN" per gli articoli perché vogliamo mostrare l'utente ANCHE SE ha 0 articoli in vendita.
    $sql = "SELECT u.idUtente, u.nome, u.cognome, u.email, COUNT(a.idArticolo) as numArticoli
            FROM Segue s
            JOIN Utente u ON s.idSeguito = u.idUtente
            LEFT JOIN ArticoloInVendita a ON u.idUtente = a.fkUtenteId AND a.disponibilita = TRUE
            WHERE s.idFollower = :userId
            GROUP BY u.idUtente
            ORDER BY u.nome";
    
    $istruzione = DBHandler::getPDO()->prepare($sql);
    $istruzione->execute([':userId' => $idUtenteLoggato]);
    
    // fetchAll() estrae tutti gli utenti trovati
    $utentiSeguiti = $istruzione->fetchAll();
    
} catch (PDOException $e) {
    die("Errore nel recupero dei seguiti: " . $e->getMessage());
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

    <nav class="navbar">
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>
        <a href="mainPage.php" class="btn-back">← Torna alla home</a>
    </nav>

    <main class="container">
        <h1>Profili che stai seguendo</h1>
        
        <div class="seguiti-list">
            
            <!-- SE NON STAI SEGUENDO NESSUNO -->
            <?php if (empty($utentiSeguiti)): ?>
                <div class="empty-state">
                    <p>Non stai ancora seguendo nessuno.</p>
                    <a href="mainPage.php" class="btn-primary">Scopri altri utenti</a>
                </div>
            
            <!-- SE STAI SEGUENDO QUALCUNO -->
            <?php else: ?>
                <!-- Creiamo un riquadro per ogni utente seguito -->
                <?php foreach ($utentiSeguiti as $utente): ?>
                    <div class="seguiti-card">
                        
                        <!-- PARTE SINISTRA: INFO UTENTE -->
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($utente['nome'], 0, 1) . substr($utente['cognome'], 0, 1)); ?>
                            </div>
                            <div class="user-details">
                                <h3><?php echo htmlspecialchars($utente['nome'] . ' ' . $utente['cognome']); ?></h3>
                                <!-- Mostriamo il conteggio calcolato dalla Query in alto -->
                                <p><?php echo $utente['numArticoli']; ?> articoli in vendita</p>
                            </div>
                        </div>
                        
                        <!-- PARTE DESTRA: BOTTONI -->
                        <div class="user-actions">
                            <button class="btn-unfollow" onclick="unfollowUser(<?php echo $utente['idUtente']; ?>)">
                                Smetti di seguire
                            </button>
                            <a href="publicProfile.php?id=<?php echo $utente['idUtente']; ?>" class="btn-view">Vedi profilo</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
        </div>
    </main>

    <!-- JS PER SMETTERE DI SEGUIRE -->
    <script>
        function unfollowUser(idDaSmettereDiSeguire) {
            if (confirm('Sei sicuro di voler smettere di seguire questo utente?')) {
                // Inviamo la richiesta al file followUser.php
                fetch('followUser.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'idSeguito=' + idDaSmettereDiSeguire + '&action=unfollow'
                })
                .then(risposta => risposta.json())
                .then(dati => {
                    if (dati.success) {
                        // Se ha avuto successo, ricarichiamo la pagina per aggiornare la lista visiva!
                        location.reload();
                    } else {
                        alert('Errore: ' + dati.error);
                    }
                })
                .catch(() => alert('Errore di rete'));
            }
        }
    </script>

</body>
</html>