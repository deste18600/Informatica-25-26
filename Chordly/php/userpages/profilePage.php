<?php
require_once('../include/menuchoice.php');

$userId = $_SESSION['userId'];

try {
    // Informazioni dell'utente
    $sqlUser = "SELECT nome, cognome, email, dataRegistrazione FROM Utente WHERE idUtente = :userId";
    $sthUser = DBHandler::getPDO()->prepare($sqlUser);
    $sthUser->bindParam(':userId', $userId, PDO::PARAM_INT);
    $sthUser->execute();
    $user = $sthUser->fetch();

    // Conteggio articoli
    $sqlArticoliCount = "SELECT COUNT(*) as totale FROM ArticoloInVendita WHERE fkUtenteId = :userId";
    $sthArticoliCount = DBHandler::getPDO()->prepare($sqlArticoliCount);
    $sthArticoliCount->bindParam(':userId', $userId, PDO::PARAM_INT);
    $sthArticoliCount->execute();
    $stats = $sthArticoliCount->fetch();

    // Follower
    $sqlSeguaci = "SELECT COUNT(*) as totale FROM Segue WHERE idSeguito = :userId";
    $sthSeguaci = DBHandler::getPDO()->prepare($sqlSeguaci);
    $sthSeguaci->bindParam(':userId', $userId, PDO::PARAM_INT);
    $sthSeguaci->execute();
    $followers = $sthSeguaci->fetch();

    // Lista articoli personali
    $sqlMieiArticoli = "SELECT idArticolo, titolo, prezzo, categoria, stato 
                        FROM ArticoloInVendita 
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
    <style>
        .review-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .review-rating { color: #c5a059; margin-bottom: 5px; }
        .review-author { font-size: 14px; font-weight: 500; color: #fff; }
        .review-text { font-size: 14px; color: rgba(255,255,255,0.6); margin-top: 5px; }
        .average-info { color: #c5a059; margin-bottom: 20px; font-weight: 500; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>
        <a class="btn-back" href="mainPage.php">← Torna alla home</a>
    </nav>

    <main class="container">

        <!--  CARD PROFILO  -->
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
            </div>

            <p class="join-date">
                Membro dal <?php echo date('d/m/Y', strtotime($user['dataRegistrazione'])); ?>
            </p>
        </div>

        <!-- I TUOI ANNUNCI  -->
        <div class="section-block">
            <h2 class="section-title">I tuoi annunci</h2>

            <?php if (empty($mieiArticoli)): ?>
                <div class="empty-box">Non hai ancora messo nulla in vendita.</div>
            <?php else: ?>
                <?php foreach ($mieiArticoli as $art): ?>
                    <div class="article-item">
                        <div class="article-item-info">
                            <strong><?php echo htmlspecialchars($art['titolo']); ?></strong>
                            <span class="article-item-price">
                                € <?php echo number_format($art['prezzo'], 2, ',', '.'); ?>
                            </span>
                            <span class="article-item-cat">
                                <?php echo htmlspecialchars($art['categoria']); ?>
                            </span>
                        </div>
                        <a href="deleteArticle.php?id=<?php echo $art['idArticolo']; ?>"
                           onclick="return confirm('Sei sicuro di voler eliminare questo annuncio?')"
                           class="btn-delete">
                            Elimina
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</body>
</html>
