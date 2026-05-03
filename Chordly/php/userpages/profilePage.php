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

    // Conteggio articoli in vendita
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

    // Lista articoli personali in vendita
    $sqlMieiArticoli = "SELECT idArticolo, titolo, prezzo, categoria, stato 
                        FROM ArticoloInVendita 
                        WHERE fkUtenteId = :userId ORDER BY dataPost DESC";
    $sthMieiArticoli = DBHandler::getPDO()->prepare($sqlMieiArticoli);
    $sthMieiArticoli->bindParam(':userId', $userId, PDO::PARAM_INT);
    $sthMieiArticoli->execute();
    $mieiArticoli = $sthMieiArticoli->fetchAll();

    // Articoli acquistati
    $sqlAcquisti = "SELECT a.idArticolo, a.titolo, a.prezzo, a.categoria, a.immagine,
                           u.nome as venditore_nome, u.cognome as venditore_cognome,
                           ac.dataAcquisto
                    FROM Acquisti ac
                    JOIN ArticoloInVendita a ON ac.fkArticoloId = a.idArticolo
                    JOIN Utente u ON a.fkUtenteId = u.idUtente
                    WHERE ac.fkAcquirenteId = :userId
                    ORDER BY ac.dataAcquisto DESC";
    $sthAcquisti = DBHandler::getPDO()->prepare($sqlAcquisti);
    $sthAcquisti->bindParam(':userId', $userId, PDO::PARAM_INT);
    $sthAcquisti->execute();
    $acquisti = $sthAcquisti->fetchAll();

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

        <!-- CARD PROFILO -->
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
                <div class="stat">
                    <div class="stat-number"><?php echo count($acquisti); ?></div>
                    <p class="stat-label">Acquisti effettuati</p>
                </div>
            </div>

            <div class="profile-actions">
                <a href="addArticle.php" class="btn-action btn-sell">+ Vendi articolo</a>
            </div>

            <p class="join-date">
                Membro dal <?php echo date('d/m/Y', strtotime($user['dataRegistrazione'])); ?>
            </p>
        </div>

        <!-- I TUOI ANNUNCI -->
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

        <!-- ARTICOLI ACQUISTATI -->
        <div class="section-block">
            <h2 class="section-title">Acquisti effettuati</h2>

            <?php if (empty($acquisti)): ?>
                <div class="empty-box">Non hai ancora acquistato nessun articolo.</div>
            <?php else: ?>
                <?php foreach ($acquisti as $acq): ?>
                    <div class="article-item">
                        <div class="article-item-info">
                            <strong><?php echo htmlspecialchars($acq['titolo']); ?></strong>
                            <span class="article-item-price">
                                € <?php echo number_format($acq['prezzo'], 2, ',', '.'); ?>
                            </span>
                            <span class="article-item-cat">
                                <?php echo htmlspecialchars($acq['categoria']); ?>
                                · Venduto da <?php echo htmlspecialchars($acq['venditore_nome'] . ' ' . $acq['venditore_cognome']); ?>
                            </span>
                            <span class="article-item-cat">
                                Acquistato il <?php echo date('d/m/Y', strtotime($acq['dataAcquisto'])); ?>
                            </span>
                        </div>
                    
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</body>
</html>
