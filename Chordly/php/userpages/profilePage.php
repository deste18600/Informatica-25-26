<?php

$userId = $_SESSION['userId'];

//  RECUPERA I DATI DAL DATABASE 
try {
    // 1. Informazioni dell'utente
    $sqlUser = "SELECT nome, cognome, email, dataRegistrazione FROM Utente WHERE idUtente = :userId";
    $sthUser = DBHandler::getPDO()->prepare($sqlUser);
    $sthUser->bindParam(':userId', $userId, PDO::PARAM_INT);
    $sthUser->execute();
    $user = $sthUser->fetch();

    // 2. Statistiche 
    $sqlArticoliCount = "SELECT COUNT(*) as totale FROM ArticoloInVendita WHERE fkUtenteId = :userId";
    $sthArticoliCount = DBHandler::getPDO()->prepare($sqlArticoliCount);
    $sthArticoliCount->bindParam(':userId', $userId, PDO::PARAM_INT);
    $sthArticoliCount->execute();
    $stats = $sthArticoliCount->fetch();

    // 3. Statistiche (Follower)
    $sqlSeguaci = "SELECT COUNT(*) as totale FROM Segue WHERE idSeguito = :userId";
    $sthSeguaci = DBHandler::getPDO()->prepare($sqlSeguaci);
    $sthSeguaci->bindParam(':userId', $userId, PDO::PARAM_INT);
    $sthSeguaci->execute();
    $followers = $sthSeguaci->fetch();

    // 4. LISTA ARTICOLI PERSONALI 
    $sqlMieiArticoli = "SELECT idArticolo, titolo, prezzo, categoria, stato FROM ArticoloInVendita 
                        WHERE fkUtenteId = :userId ORDER BY dataPost DESC";
    $sthMieiArticoli = DBHandler::getPDO()->prepare($sqlMieiArticoli);
    $sthMieiArticoli->bindParam(':userId', $userId, PDO::PARAM_INT);
    $sthMieiArticoli->execute();
    $mieiArticoli = $sthMieiArticoli->fetchAll();

} catch (PDOException $e) {
    die("Errore nel recupero dati: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/profilePage.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <title>Profilo - Chordly</title>
</head>
<body>

    <nav class="navbar">
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>
        <a class="btn-back" href="mainPage.php">← Torna alla home</a>
    </nav>

    <main class="container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['nome'], 0, 1) . substr($user['cognome'], 0, 1)); ?>
                </div>
                <h1 class="profile-name">
                    <?php echo htmlspecialchars($user['nome'] . ' ' . $user['cognome']); ?>
                </h1>
                <p class="profile-email">
                    <?php echo htmlspecialchars($user['email']); ?>
                </p>
            </div>

            <div class="profile-stats">
                <div class="stat">
                    <div class="stat-number"><?php echo $stats['totale']; ?></div>
                    <p class="stat-label">Articoli in vendita</p>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo $followers['totale']; ?></div>
                    <p class="stat-label">Follower</p>
                </div>
            </div>

            <div class="profile-actions">
                <a href="addArticle.php" class="btn-action btn-sell">+ Vendi articolo</a>
                <button onclick="editProfile()" class="btn-action btn-edit">Modifica profilo</button>
            </div>

            <p class="join-date">
                Membro dal <?php echo date('d/m/Y', strtotime($user['dataRegistrazione'])); ?>
            </p>
        </div>

        <div class="my-articles-section" style="margin-top: 40px; width: 100%; max-width: 600px; margin-left: auto; margin-right: auto;">
            <h2 style="color: white; font-family: 'Bebas Neue', sans-serif; font-size: 2rem; border-bottom: 2px solid #ff4d4d; display: inline-block; margin-bottom: 20px;">
                I tuoi annunci
            </h2>

            <div class="articles-list">
                <?php if (empty($mieiArticoli)): ?>
                    <div style="text-align: center; padding: 30px; background: rgba(255,255,255,0.05); border-radius: 10px; color: #888;">
                        Non hai ancora messo nulla in vendita.
                    </div>
                <?php else: ?>
                    <?php foreach ($mieiArticoli as $art): ?>
                        <div class="article-item" style="background: #1a1a1a; padding: 15px; border-radius: 12px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #333; transition: transform 0.2s;">
                            <div>
                                <strong style="color: white; font-size: 1.1rem; display: block; margin-bottom: 4px;">
                                    <?php echo htmlspecialchars($art['titolo']); ?>
                                </strong>
                                <span style="color: #ff4d4d; font-weight: bold; font-family: 'DM Sans', sans-serif;">
                                    € <?php echo number_format($art['prezzo'], 2, ',', '.'); ?>
                                </span>
                                <span style="background: #333; color: #ccc; font-size: 0.75rem; padding: 2px 8px; border-radius: 4px; margin-left: 10px; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($art['categoria']); ?>
                                </span>
                            </div>
                            
                            <a href="deleteArticle.php?id=<?php echo $art['idArticolo']; ?>" 
                               onclick="return confirm('Sei sicuro di voler eliminare questo annuncio? Questa azione è irreversibile.')" 
                               style="background: #ff4d4d; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight: bold; transition: background 0.3s;">
                                Elimina
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function editProfile() {
            alert('Funzionalità modifica profilo in sviluppo');
        }
    </script>

</body>
</html>