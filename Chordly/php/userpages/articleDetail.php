<?php
session_start();
require_once('../include/dbHandler.php');

if (!isset($_SESSION['userId'])) {
    header('Location: userLoginpage.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: mainPage.php');
    exit;
}

$userId   = $_SESSION['userId'];
$idArticolo = (int)$_GET['id'];

try {
    // Dati articolo + venditore
    $sql = "SELECT a.*, u.nome, u.cognome, u.email, u.idUtente as vendId
            FROM ArticoloInVendita a
            JOIN Utente u ON a.fkUtenteId = u.idUtente
            WHERE a.idArticolo = :id AND a.disponibilita = TRUE";
    $sth = DBHandler::getPDO()->prepare($sql);
    $sth->bindParam(':id', $idArticolo, PDO::PARAM_INT);
    $sth->execute();
    $articolo = $sth->fetch();

    if (!$articolo) {
        header('Location: mainPage.php');
        exit;
    }

    // Controlla se già segui il venditore
    $sqlSeguito = "SELECT 1 FROM Segue WHERE idFollower = :me AND idSeguito = :lui";
    $sthS = DBHandler::getPDO()->prepare($sqlSeguito);
    $sthS->execute([':me' => $userId, ':lui' => $articolo['vendId']]);
    $giaSegui = (bool)$sthS->fetch();

    // Numero di follower del venditore
    $sqlFollower = "SELECT COUNT(*) as tot FROM Segue WHERE idSeguito = :lui";
    $sthF = DBHandler::getPDO()->prepare($sqlFollower);
    $sthF->execute([':lui' => $articolo['vendId']]);
    $numFollower = $sthF->fetch()['tot'];

    // Altri articoli dello stesso venditore
    $sqlAltri = "SELECT idArticolo, titolo, prezzo, immagine
                 FROM ArticoloInVendita
                 WHERE fkUtenteId = :uid AND idArticolo != :aid AND disponibilita = TRUE
                 ORDER BY dataPost DESC LIMIT 4";
    $sthA = DBHandler::getPDO()->prepare($sqlAltri);
    $sthA->execute([':uid' => $articolo['vendId'], ':aid' => $idArticolo]);
    $altriArticoli = $sthA->fetchAll();

} catch (PDOException $e) {
    die("Errore: " . $e->getMessage());
}

$iniziali = strtoupper(substr($articolo['nome'], 0, 1) . substr($articolo['cognome'], 0, 1));
$isMio    = ($userId == $articolo['vendId']);
$imgPath  = $articolo['immagine'] ? '../../uploads/articoli/' . htmlspecialchars($articolo['immagine']) : null;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/articleDetail.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <title><?php echo htmlspecialchars($articolo['titolo']); ?> - Chordly</title>
</head>
<body>

    <nav class="navbar">
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>
        <a href="mainPage.php" class="btn-back">← Torna alla home</a>
    </nav>

    <main class="detail-container">

        <!--  COLONNA SINISTRA: IMMAGINE -->
        <div class="image-col">
            <div class="main-image-wrapper" id="mainImgWrapper"
                 <?php if($imgPath): ?>onclick="openLightbox()"<?php endif; ?>>
                <?php if ($imgPath): ?>
                    <img src="<?php echo $imgPath; ?>"
                         alt="<?php echo htmlspecialchars($articolo['titolo']); ?>"
                         class="main-image" id="mainImg">
                    <div class="zoom-hint">🔍 Clicca per ingrandire</div>
                <?php else: ?>
                    <div class="placeholder-img">🎸</div>
                <?php endif; ?>
            </div>

            <span class="stato-badge <?php echo $articolo['stato']; ?>">
                <?php echo ucfirst($articolo['stato']); ?>
            </span>
        </div>

        <!-- COLONNA DESTRA: DETTAGLI  -->
        <div class="info-col">
            <div class="categoria-label"><?php echo ucfirst($articolo['categoria']); ?></div>
            <h1 class="titolo"><?php echo htmlspecialchars($articolo['titolo']); ?></h1>
            <div class="prezzo">€ <?php echo number_format($articolo['prezzo'], 2, ',', '.'); ?></div>

            <?php if (!empty($articolo['descrizione'])): ?>
                <div class="descrizione">
                    <h3>Descrizione</h3>
                    <p><?php echo nl2br(htmlspecialchars($articolo['descrizione'])); ?></p>
                </div>
            <?php endif; ?>

            <!--  CARD VENDITORE  -->
            <div class="seller-card">
                <div class="seller-info">
                    <div class="seller-avatar"><?php echo $iniziali; ?></div>
                    <div>
                        <div class="seller-name">
                            <?php echo htmlspecialchars($articolo['nome'] . ' ' . $articolo['cognome']); ?>
                        </div>
                        <div class="seller-meta"><?php echo $numFollower; ?> follower</div>
                    </div>
                </div>

                <?php if (!$isMio): ?>
                    <div class="seller-actions">
                        <!-- PULSANTE FOLLOW/UNFOLLOW -->
                        <button class="btn-follow <?php echo $giaSegui ? 'following' : ''; ?>"
                                id="followBtn"
                                onclick="toggleFollow(<?php echo $articolo['vendId']; ?>)">
                            <?php echo $giaSegui ? 'Stai seguendo' : '+ Segui'; ?>
                        </button>

                        <!-- PULSANTE MESSAGGIO -->
                        <a href="messagesPage.php?with=<?php echo $articolo['vendId']; ?>&art=<?php echo $idArticolo; ?>"
                           class="btn-message">
                            Contatta venditore
                        </a>
                    </div>
                <?php else: ?>
                    <div class="my-listing-badge">Il tuo annuncio</div>
                <?php endif; ?>
            </div>

            <div class="post-date">
                Pubblicato il <?php echo date('d/m/Y', strtotime($articolo['dataPost'])); ?>
            </div>
        </div>
    </main>

    <!--  ALTRI ARTICOLI DEL VENDITORE  -->
    <?php if (!empty($altriArticoli)): ?>
    <section class="altri-section">
        <div class="altri-inner">
            <h2>Altri annunci di <?php echo htmlspecialchars($articolo['nome']); ?></h2>
            <div class="altri-grid">
                <?php foreach ($altriArticoli as $art): ?>
                    <a href="articleDetail.php?id=<?php echo $art['idArticolo']; ?>" class="mini-card">
                        <div class="mini-img">
                            <?php if ($art['immagine']): ?>
                                <img src="../../uploads/articoli/<?php echo htmlspecialchars($art['immagine']); ?>"
                                     alt="<?php echo htmlspecialchars($art['titolo']); ?>">
                            <?php else: ?>
                                🎸
                            <?php endif; ?>
                        </div>
                        <div class="mini-info">
                            <div class="mini-title"><?php echo htmlspecialchars($art['titolo']); ?></div>
                            <div class="mini-price">€ <?php echo number_format($art['prezzo'], 2, ',', '.'); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!--  LIGHTBOX da completare -->
    <?php if ($imgPath): ?>
    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()">✕</button>
        <img src="<?php echo $imgPath; ?>"
             alt="<?php echo htmlspecialchars($articolo['titolo']); ?>"
             class="lightbox-img"
             onclick="event.stopPropagation()">
    </div>
    <?php endif; ?>

    <script>
        // LIGHTBOX 
        function openLightbox() {
            document.getElementById('lightbox').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeLightbox();
        });

        // FOLLOW/UNFOLLOW
        function toggleFollow(vendId) {
            const btn = document.getElementById('followBtn');
            const isFollowing = btn.classList.contains('following');

            fetch('followUser.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'idSeguito=' + vendId + '&action=' + (isFollowing ? 'unfollow' : 'follow')
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.classList.toggle('following');
                    btn.textContent = isFollowing ? '+ Segui' : 'Stai seguendo';
                }
            })
            .catch(() => alert('Errore di rete'));
        }
    </script>

</body>
</html>
