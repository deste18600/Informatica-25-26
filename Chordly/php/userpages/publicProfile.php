<?php
require_once('../include/menuchoice.php');

// Assicurati che l'ID dell'utente da visualizzare sia passato tramite GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: mainPage.php'); // Reindirizza se l'ID non è valido
    exit;
}

$profileUserId = (int)$_GET['id']; // L'ID dell'utente il cui profilo stiamo visualizzando
$loggedInUserId = $_SESSION['userId'] ?? null; // L'ID dell'utente loggato (potrebbe essere null se non loggato)

// Se l'utente loggato sta visualizzando il proprio profilo, reindirizza a profilePage.php
if ($loggedInUserId && $profileUserId === $loggedInUserId) {
    header('Location: profilePage.php');
    exit;
}

try {
    // Informazioni dell'utente del profilo
    $sqlUser = "SELECT idUtente, nome, cognome, email, dataRegistrazione FROM Utente WHERE idUtente = :profileUserId";
    $sthUser = DBHandler::getPDO()->prepare($sqlUser);
    $sthUser->bindParam(':profileUserId', $profileUserId, PDO::PARAM_INT);
    $sthUser->execute();
    $user = $sthUser->fetch();

    if (!$user) {
        // Utente non trovato, reindirizza o mostra un errore
        header('Location: mainPage.php');
        exit;
    }

    // Conteggio articoli in vendita
    $sqlArticoliCount = "SELECT COUNT(*) as totale FROM ArticoloInVendita WHERE fkUtenteId = :profileUserId AND disponibilita = TRUE";
    $sthArticoliCount = DBHandler::getPDO()->prepare($sqlArticoliCount);
    $sthArticoliCount->bindParam(':profileUserId', $profileUserId, PDO::PARAM_INT);
    $sthArticoliCount->execute();
    $stats = $sthArticoliCount->fetch();

    // Follower del profilo
    $sqlFollower = "SELECT COUNT(*) as totale FROM Segue WHERE idSeguito = :profileUserId";
    $sthFollower = DBHandler::getPDO()->prepare($sqlFollower);
    $sthFollower->bindParam(':profileUserId', $profileUserId, PDO::PARAM_INT);
    $sthFollower->execute();
    $followers = $sthFollower->fetch();

    // Articoli in vendita dell'utente del profilo
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
        $sqlCheckFollow = "SELECT 1 FROM Segue WHERE idFollower = :loggedInUserId AND idSeguito = :profileUserId";
        $sthCheckFollow = DBHandler::getPDO()->prepare($sqlCheckFollow);
        $sthCheckFollow->execute([':loggedInUserId' => $loggedInUserId, ':profileUserId' => $profileUserId]);
        $giaSegui = (bool)$sthCheckFollow->fetch();
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
    <link rel="stylesheet" href="../../css/profilePage.css"> <!-- Riutilizziamo il CSS del profilo -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <title>Profilo di <?php echo htmlspecialchars($user['nome'] . ' ' . $user['cognome']); ?> - Chordly</title>
    <style>
        /* Stili specifici per publicProfile.php o override */
        .review-form {
            background: rgba(15, 15, 15, 0.7);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .review-form h3 {
            font-size: 18px;
            color: white;
            margin-bottom: 10px;
        }
        .review-form textarea {
            width: 100%;
            min-height: 80px;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.08);
            color: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            resize: vertical;
        }
        .rating-stars {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
            flex-direction: row-reverse; /* Per avere le stelle da destra a sinistra */
            justify-content: flex-end;
        }
        .rating-stars input[type="radio"] {
            display: none;
        }
        .rating-stars label {
            cursor: pointer;
            font-size: 24px;
            color: rgba(255,255,255,0.3);
            transition: color 0.2s;
        }
        .rating-stars label:hover,
        .rating-stars label:hover ~ label,
        .rating-stars input[type="radio"]:checked ~ label {
            color: #c5a059;
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
        }
        .review-form button:hover {
            transform: translateY(-2px);
        }

        .reviews-list {
            margin-top: 30px;
        }
        .review-item {
            background: rgba(15, 15, 15, 0.7);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 12px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .reviewer-info strong {
            color: white;
            font-weight: 500;
        }
        .review-date {
            font-size: 12px;
            color: rgba(255,255,255,0.4);
        }
        .review-rating {
            color: #c5a059;
            font-size: 18px;
            margin-bottom: 8px;
        }
        .review-comment {
            font-size: 14px;
            color: rgba(255,255,255,0.7);
            line-height: 1.6;
        }
        .average-rating {
            font-size: 20px;
            color: #c5a059;
            font-weight: 500;
            margin-bottom: 10px;
        }
        .average-rating span {
            font-size: 14px;
            color: rgba(255,255,255,0.5);
        }
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
        .btn-message {
            flex: 1; padding: 10px 20px;
            border-radius: 8px;
            background: #c5a059; color: #111;
            font-size: 14px; font-weight: 500;
            text-decoration: none; text-align: center;
            transition: background 0.2s;
        }
        .btn-message:hover { background: #d4b06a; }
        /* Stili per la griglia degli articoli, ripresi da mainPage.css */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        .product-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
        }
        .product-card:hover {
            border-color: #c5a059;
        }
        .card-image-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 3/4;
            background: #111;
            overflow: hidden;
        }
        .card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .placeholder-img {
            background: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: rgba(255,255,255,0.3);
        }
        .stato-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            font-size: 10px;
            font-weight: 500;
            padding: 3px 9px;
            border-radius: 10px;
            text-transform: uppercase;
            border: 1px solid #333;
            color: #888;
        }
        .stato-badge.ottimo { color: #c5a059; border-color: #c5a059; }
        .stato-badge.difettato { color: #cc6666; border-color: #cc6666; }
        .card-body {
            padding: 10px 12px 12px;
        }
        .card-price {
            color: #c5a059;
            font-weight: 500;
            font-size: 16px;
            margin-bottom: 4px;
        }
        .card-title {
            color: #e5e1d8;
            font-size: 13px;
            line-height: 1.3;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-category {
            color: #888;
            font-size: 11px;
            margin-bottom: 8px;
        }
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
                    <?php echo $iniziali; ?>
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

            <?php if ($loggedInUserId): // Mostra azioni solo se l'utente è loggato ?>
            <div class="profile-actions">
                <!-- PULSANTE FOLLOW/UNFOLLOW -->
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

        <!-- ARTICOLI IN VENDITA -->
        <div class="section-block">
            <h2 class="section-title">Articoli in vendita di <?php echo htmlspecialchars($user['nome']); ?></h2>
            <?php if (empty($articoliInVendita)): ?>
                <div class="empty-box">Nessun articolo in vendita al momento.</div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($articoliInVendita as $articolo): ?>
                        <div class="product-card" onclick="location.href='articleDetail.php?id=<?php echo $articolo['idArticolo']; ?>'">
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
        // Funzione per gestire il follow/unfollow (copiata da articleDetail.php)
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
                    // Aggiorna il numero di follower dinamicamente (opzionale, per semplicità non lo faccio ora)
                } else {
                    alert('Errore: ' + data.error);
                }
            })
            .catch(() => alert('Errore di rete durante il follow/unfollow'));
        }
    </script>
</body>
</html>