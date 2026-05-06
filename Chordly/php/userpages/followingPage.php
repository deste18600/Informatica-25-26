<?php
require_once('../include/menuchoice.php');

$idUtenteLoggato = $_SESSION['userId'];

try {
    // Prendo la lista degli utenti che seguo

    $sqlSeguiti = "SELECT u.idUtente, u.nome, u.cognome, u.email 
                   FROM Segue s
                   JOIN Utente u ON s.idSeguito = u.idUtente
                   WHERE s.idFollower = :userId
                   ORDER BY u.nome";
    
    $istruzione = DBHandler::getPDO()->prepare($sqlSeguiti);
    $istruzione->execute([':userId' => $idUtenteLoggato]);
    $utentiSeguiti = $istruzione->fetchAll();

    // Per ogni utente, vado a contare quanti articoli ha
    // Uso il simbolo & per modificare direttamente l'array $utentiSeguiti

    foreach ($utentiSeguiti as &$utente) {
        $sqlConteggio = "SELECT COUNT(*) as totale 
                         FROM ArticoloInVendita 
                         WHERE fkUtenteId = :idVenditore AND disponibilita = TRUE";
        
        $istrConteggio = DBHandler::getPDO()->prepare($sqlConteggio);
        $istrConteggio->execute([':idVenditore' => $utente['idUtente']]);
        
        // Aggiungo il numero trovato dentro l'array dell'utente
        $risultato = $istrConteggio->fetch();
        $utente['numArticoli'] = $risultato['totale'];
    }

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
            
            <!-- se non segui nessuno -->
            <?php if (empty($utentiSeguiti)): ?>
                
                <p>Non segui ancora nessuno, cerca tra gli articoli che ti interessano.</p>
            
            <!-- se segui qualcuno -->
            <?php else: ?>

                <!-- creazione riquadri utente -->
                <?php foreach ($utentiSeguiti as $utente): ?>
                    <div class="seguiti-card">
                        
                        <!-- info utente a sinistra -->
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($utente['nome'], 0, 1) . substr($utente['cognome'], 0, 1)); ?>
                            </div>
                            <div class="user-details">
                                <h3><?php echo htmlspecialchars($utente['nome'] . ' ' . $utente['cognome']); ?></h3>
                                <!-- Mostriamo il conteggio calcolato dalla Query -->
                                <p><?php echo $utente['numArticoli']; ?> articoli in vendita</p>
                            </div>
                        </div>
                        

                        <!-- pulsanti a destra -->
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