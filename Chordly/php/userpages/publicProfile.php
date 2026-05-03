<?php
require_once('../include/menuchoice.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: mainPage.php');
    exit;
}

$profileUserId  = (int)$_GET['id'];
$loggedInUserId = $_SESSION['userId'] ?? null;

// Se l'utente loggato guarda se stesso, manda al profilo privato
if ($loggedInUserId && $profileUserId === $loggedInUserId) {
    header('Location: profilePage.php');
    exit;
}

try {
    // Dati utente del profilo
    $sqlUser = "SELECT idUtente, nome, cognome, email, dataRegistrazione FROM Utente WHERE idUtente = :profileUserId";
    $sthUser = DBHandler::getPDO()->prepare($sqlUser);
    $sthUser->bindParam(':profileUserId', $profileUserId, PDO::PARAM_INT);
    $sthUser->execute();
    $user = $sthUser->fetch();

    if (!$user) {
        header('Location: mainPage.php');
        exit;
    }

    // Conteggio articoli in vendita
    $sqlArticoliCount = "SELECT COUNT(*) as totale FROM ArticoloInVendita WHERE fkUtenteId = :profileUserId AND disponibilita = TRUE";
    $sthArticoliCount = DBHandler::getPDO()->prepare($sqlArticoliCount);
    $sthArticoliCount->bindParam(':profileUserId', $profileUserId, PDO::PARAM_INT);
    $sthArticoliCount->execute();
    $stats = $sthArticoliCount->fetch();

    // Follower
    $sqlFollower = "SELECT COUNT(*) as totale FROM Segue WHERE idSeguito = :profileUserId";
    $sthFollower = DBHandler::getPDO()->prepare($sqlFollower);
    $sthFollower->bindParam(':profileUserId', $profileUserId, PDO::PARAM_INT);
    $sthFollower->execute();
    $followers = $sthFollower->fetch();

    // Articoli in vendita
    $sqlArticoli = "SELECT idArticolo, titolo, prezzo, categoria, stato, immagine
                    FROM ArticoloInVendita
                    WHERE fkUtenteId = :profileUserId AND disponibilita = TRUE
                    ORDER BY dataPost DESC";
    $sthArticoli = DBHandler::getPDO()->prepare($sqlArticoli);
    $sthArticoli->bindParam(':profileUserId', $profileUserId, PDO::PARAM_INT);
    $sthArticoli->execute();
    $articoliInVendita = $sthArticoli->fetchAll();

    // Controlla se l'utente loggato segue già questo profilo
    $giaSegui = false;
    if ($loggedInUserId) {
        $sqlCheckFollow = "SELECT 1 FROM Segue WHERE idFollower = :me AND idSeguito = :lui";
        $sthCheckFollow = DBHandler::getPDO()->prepare($sqlCheckFollow);
        $sthCheckFollow->execute([':me' => $loggedInUserId, ':lui' => $profileUserId]);
        $giaSegui = (bool)$sthCheckFollow->fetch();
    }

    // Controlla se l'utente loggato ha già recensito questo profilo
    $giaRecensito = false;
    if ($loggedInUserId) {
        $sqlGiaR = "SELECT 1 FROM RecensioneUtente WHERE fkRecensoreId = :me AND fkRecensitoId = :lui";
        $sthGiaR = DBHandler::getPDO()->prepare($sqlGiaR);
        $sthGiaR->execute([':me' => $loggedInUserId, ':lui' => $profileUserId]);
        $giaRecensito = (bool)$sthGiaR->fetch();
    }

    // Recensioni ricevute
    $sqlRecensioni = "SELECT r.valutazione, r.commento, r.dataRecensione, u.nome, u.cognome
                      FROM RecensioneUtente r
                      JOIN Utente u ON r.fkRecensoreId = u.idUtente
                      WHERE r.fkRecensitoId = :profileUserId
                      ORDER BY r.dataRecensione DESC";
    $sthRecensioni = DBHandler::getPDO()->prepare($sqlRecensioni);
    $sthRecensioni->execute([':profileUserId' => $profileUserId]);
    $recensioni = $sthRecensioni->fetchAll();

    // Media valutazioni
    $mediaVoto = 0;
    if (!empty($recensioni)) {
        $mediaVoto = round(array_sum(array_column($recensioni, 'valutazione')) / count($recensioni), 1);
    }

} catch (PDOException $e) {
    die("Errore nel recupero dati del profilo: " . $e->getMessage());
}

$iniziali = strtoupper(substr($user['nome'], 0, 1) . substr($user['cognome'], 0, 1));
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- mainPage.css fornisce già le classi product-card, stato-badge ecc. -->
    <link rel="stylesheet" href="../../css/mainPage.css">
    <link rel="stylesheet" href="../../css/profilePage.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <title>Profilo di <?php echo htmlspecialchars($user['nome'] . ' ' . $user['cognome']); ?> - Chordly</title>
    <style>
        /* ---- Stili specifici solo di questa pagina ---- */
        .profile-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .btn-follow {
            flex: 1; padding: 10px 20px;
            border-radius: 8px; border: 1px solid rgba(197,160,89,0.5);
            background: transparent; color: #c5a059;
            font-size: 14px; font-weight: 500;
            cursor: pointer; transition: all 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-follow:hover { background: rgba(197,160,89,0.1); }
        .btn-follow.following {
            background: rgba(197,160,89,0.15);
            border-color: #c5a059;
        }

        /* Griglia articoli (override dimensioni per questo contesto) */
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            margin-top: 16px;
        }

        /* Form recensione */
        .review-form {
            background: rgba(15,15,15,0.7);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .rating-stars {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 4px;
        }
        .rating-stars input[type="radio"] { display: none; }
        .rating-stars label {
            font-size: 28px;
            color: rgba(255,255,255,0.2);
            cursor: pointer;
            transition: color 0.15s;
        }
        .rating-stars label:hover,
        .rating-stars label:hover ~ label,
        .rating-stars input[type="radio"]:checked ~ label {
            color: #c5a059;
        }
        .review-form textarea {
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.08);
            color: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            resize: vertical;
            min-height: 80px;
        }
        .review-form button {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            background: #c5a059;
            color: #111;
            font-weight: 500;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'DM Sans', sans-serif;
            align-self: flex-start;
        }
        .review-form button:hover { background: #d4b06a; }

        /* Singola recensione */
        .review-item {
            background: rgba(15,15,15,0.7);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 12px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .review-header strong { color: white; font-weight: 500; font-size: 14px; }
        .review-date { font-size: 12px; color: rgba(255,255,255,0.4); }
        .review-stars { color: #c5a059; font-size: 16px; margin-bottom: 6px; }
        .review-comment { font-size: 14px; color: rgba(255,255,255,0.65); line-height: 1.6; }
        .media-voto { color: #c5a059; font-size: 16px; font-weight: 500; margin-bottom: 16px; }
        .alert-success {
            padding: 10px 14px; border-radius: 8px;
            background: rgba(80,200,100,0.1);
            border: 1px solid rgba(80,200,100,0.3);
            color: #5cc877; font-size: 14px; margin-bottom: 16px;
        }
    </style>
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
                <div class="profile-avatar"><?php echo $iniziali; ?></div>
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
                <?php if (!empty($recensioni)): ?>
                <div class="stat">
                    <div class="stat-number"><?php echo $mediaVoto; ?>★</div>
                    <p class="stat-label">Valutazione media</p>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($loggedInUserId): ?>
            <div class="profile-actions">
                <button class="btn-follow <?php echo $giaSegui ? 'following' : ''; ?>"
                        id="followBtn"
                        onclick="toggleFollow(<?php echo $profileUserId; ?>)">
                    <?php echo $giaSegui ? 'Stai seguendo' : '+ Segui'; ?>
                </button>
            </div>
            <?php endif; ?>

            <p class="join-date">
                Membro dal <?php echo date('d/m/Y', strtotime($user['dataRegistrazione'])); ?>
            </p>
        </div>

        <!-- LASCIA UNA RECENSIONE -->
        <?php if ($loggedInUserId): ?>
        <div class="section-block">
            <h2 class="section-title">Lascia una recensione</h2>

            <?php if (isset($_GET['review_success'])): ?>
                <div class="alert-success">✓ Recensione inviata con successo!</div>
            <?php endif; ?>

            <?php if ($giaRecensito): ?>
                <div class="empty-box">Hai già lasciato una recensione per questo utente.</div>
            <?php else: ?>
                <form action="addReview.php" method="POST" class="review-form">
                    <input type="hidden" name="fkRecensitoId" value="<?php echo $profileUserId; ?>">

                    <div>
                        <p style="font-size:14px; color:rgba(255,255,255,0.6); margin-bottom:8px;">Valutazione *</p>
                        <div class="rating-stars">
                            <input type="radio" name="valutazione" id="s5" value="5" required>
                            <label for="s5">★</label>
                            <input type="radio" name="valutazione" id="s4" value="4">
                            <label for="s4">★</label>
                            <input type="radio" name="valutazione" id="s3" value="3">
                            <label for="s3">★</label>
                            <input type="radio" name="valutazione" id="s2" value="2">
                            <label for="s2">★</label>
                            <input type="radio" name="valutazione" id="s1" value="1">
                            <label for="s1">★</label>
                        </div>
                    </div>

                    <textarea name="commento" placeholder="Scrivi la tua recensione (opzionale)..."></textarea>
                    <button type="submit">Invia recensione</button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- RECENSIONI RICEVUTE -->
        <?php if (!empty($recensioni)): ?>
        <div class="section-block">
            <h2 class="section-title">Recensioni ricevute</h2>
            <p class="media-voto">Media: <?php echo $mediaVoto; ?>/5 — <?php echo count($recensioni); ?> recension<?php echo count($recensioni) === 1 ? 'e' : 'i'; ?></p>

            <?php foreach ($recensioni as $rec): ?>
                <div class="review-item">
                    <div class="review-header">
                        <strong><?php echo htmlspecialchars($rec['nome'] . ' ' . $rec['cognome']); ?></strong>
                        <span class="review-date"><?php echo date('d/m/Y', strtotime($rec['dataRecensione'])); ?></span>
                    </div>
                    <div class="review-stars">
                        <?php echo str_repeat('★', $rec['valutazione']) . str_repeat('☆', 5 - $rec['valutazione']); ?>
                    </div>
                    <?php if (!empty($rec['commento'])): ?>
                        <div class="review-comment"><?php echo htmlspecialchars($rec['commento']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ARTICOLI IN VENDITA -->
        <div class="section-block">
            <h2 class="section-title">Articoli in vendita di <?php echo htmlspecialchars($user['nome']); ?></h2>
            <?php if (empty($articoliInVendita)): ?>
                <div class="empty-box">Nessun articolo in vendita al momento.</div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($articoliInVendita as $articolo): ?>
                        <div class="product-card"
                             onclick="location.href='articleDetail.php?id=<?php echo $articolo['idArticolo']; ?>'">
                            <div class="card-image-wrapper">
                                <?php if ($articolo['immagine']): ?>
                                    <img src="../../uploads/articoli/<?php echo htmlspecialchars($articolo['immagine']); ?>"
                                         alt="<?php echo htmlspecialchars($articolo['titolo']); ?>"
                                         class="card-image">
                                <?php else: ?>
                                    <div class="card-image placeholder-img">🎸</div>
                                <?php endif; ?>
                                <span class="stato-badge <?php echo htmlspecialchars($articolo['stato']); ?>">
                                    <?php echo htmlspecialchars($articolo['stato']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="card-price">€ <?php echo number_format($articolo['prezzo'], 2, ',', '.'); ?></div>
                                <div class="card-title"><?php echo htmlspecialchars($articolo['titolo']); ?></div>
                                <div class="card-category"><?php echo htmlspecialchars(ucfirst($articolo['categoria'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <script>
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
                } else {
                    alert('Errore: ' + data.error);
                }
            })
            .catch(() => alert('Errore di rete'));
        }
    </script>

</body>
</html>
