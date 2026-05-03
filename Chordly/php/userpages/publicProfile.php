<?php
// Richiamiamo il controllore
require_once('../include/menuchoice.php');

// Verifichiamo di chi è il profilo che vogliamo guardare (tramite l'indirizzo web ?id=...)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: mainPage.php');
    exit;
}

$idUtenteProfilo = (int)$_GET['id'];

// Chi sta guardando la pagina? Potrebbe essere un ospite non loggato, quindi usiamo "?? null" per non dare errore
$idUtenteLoggato = $_SESSION['userId'] ?? null;

// PICCOLA MAGIA: Se l'utente clicca per sbaglio sul *suo* profilo pubblico, 
// lo reindirizziamo automaticamente al suo profilo privato!
if ($idUtenteLoggato && $idUtenteProfilo === $idUtenteLoggato) {
    header('Location: profilePage.php');
    exit;
}

try {
    // 1. Dati generali dell'utente
    $sqlUtente = "SELECT idUtente, nome, cognome, email, dataRegistrazione FROM Utente WHERE idUtente = :idProfilo";
    $istruzioneUtente = DBHandler::getPDO()->prepare($sqlUtente);
    $istruzioneUtente->execute([':idProfilo' => $idUtenteProfilo]);
    $datiUtente = $istruzioneUtente->fetch();

    // Se l'utente non esiste nel database, torna alla home
    if (!$datiUtente) {
        header('Location: mainPage.php');
        exit;
    }

    // 2. Statistiche (Quanti articoli in vendita ha)
    $sqlArticoliCount = "SELECT COUNT(*) as totale FROM ArticoloInVendita WHERE fkUtenteId = :idProfilo AND disponibilita = TRUE";
    $istruzioneCount = DBHandler::getPDO()->prepare($sqlArticoliCount);
    $istruzioneCount->execute([':idProfilo' => $idUtenteProfilo]);
    $statisticheArticoli = $istruzioneCount->fetch();

    // 3. Statistiche (Quanti follower ha)
    $sqlFollower = "SELECT COUNT(*) as totale FROM Segue WHERE idSeguito = :idProfilo";
    $istruzioneFollower = DBHandler::getPDO()->prepare($sqlFollower);
    $istruzioneFollower->execute([':idProfilo' => $idUtenteProfilo]);
    $numeroFollower = $istruzioneFollower->fetch();

    // 4. Lista dei suoi articoli attualmente in vendita
    $sqlArticoli = "SELECT idArticolo, titolo, prezzo, categoria, stato, immagine
                    FROM ArticoloInVendita
                    WHERE fkUtenteId = :idProfilo AND disponibilita = TRUE
                    ORDER BY dataPost DESC";
    $istruzioneArticoli = DBHandler::getPDO()->prepare($sqlArticoli);
    $istruzioneArticoli->execute([':idProfilo' => $idUtenteProfilo]);
    $articoliInVendita = $istruzioneArticoli->fetchAll();

    // VARIABILI PER L'UTENTE LOGGATO (Segui e Recensioni)
    $giaSegui = false;
    $giaRecensito = false;

    // Se stiamo guardando la pagina e SIAMO loggati...
    if ($idUtenteLoggato) {
        // Controlliamo se lo stiamo già seguendo
        $sqlCheckFollow = "SELECT 1 FROM Segue WHERE idFollower = :me AND idSeguito = :lui";
        $istruzioneCheckFollow = DBHandler::getPDO()->prepare($sqlCheckFollow);
        $istruzioneCheckFollow->execute([':me' => $idUtenteLoggato, ':lui' => $idUtenteProfilo]);
        $giaSegui = (bool)$istruzioneCheckFollow->fetch();
        
        // Controlliamo se gli abbiamo già lasciato una recensione
        $sqlGiaRecensito = "SELECT 1 FROM RecensioneUtente WHERE fkRecensoreId = :me AND fkRecensitoId = :lui";
        $istruzioneGiaRecensito = DBHandler::getPDO()->prepare($sqlGiaRecensito);
        $istruzioneGiaRecensito->execute([':me' => $idUtenteLoggato, ':lui' => $idUtenteProfilo]);
        $giaRecensito = (bool)$istruzioneGiaRecensito->fetch();
    }

    // 5. Estraiamo TUTTE le recensioni ricevute da questo utente
    $sqlRecensioni = "SELECT r.valutazione, r.commento, r.dataRecensione, u.nome, u.cognome
                      FROM RecensioneUtente r
                      JOIN Utente u ON r.fkRecensoreId = u.idUtente
                      WHERE r.fkRecensitoId = :idProfilo
                      ORDER BY r.dataRecensione DESC";
    $istruzioneRecensioni = DBHandler::getPDO()->prepare($sqlRecensioni);
    $istruzioneRecensioni->execute([':idProfilo' => $idUtenteProfilo]);
    $listaRecensioni = $istruzioneRecensioni->fetchAll();

    // CALCOLIAMO LA MEDIA VOTI
    $mediaVoto = 0;
    if (!empty($listaRecensioni)) {
        // array_column prende solo i numeri delle "valutazioni", array_sum li somma tutti. 
        // Poi dividiamo per il numero di recensioni e arrotondiamo a 1 decimale.
        $sommaVoti = array_sum(array_column($listaRecensioni, 'valutazione'));
        $mediaVoto = round($sommaVoti / count($listaRecensioni), 1);
    }

} catch (PDOException $e) {
    die("Errore nel recupero dati del profilo: " . $e->getMessage());
}

$inizialiAvatar = strtoupper(substr($datiUtente['nome'], 0, 1) . substr($datiUtente['cognome'], 0, 1));
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/mainPage.css">
    <link rel="stylesheet" href="../../css/profilePage.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <title>Profilo di <?php echo htmlspecialchars($datiUtente['nome'] . ' ' . $datiUtente['cognome']); ?> - Chordly</title>
    <!-- (Ho mantenuto intatti i tuoi stili interni per le stelle delle recensioni e il layout) -->
    <style>
        .profile-actions { display: flex; gap: 12px; margin-top: 20px; }
        .btn-follow { flex: 1; padding: 10px 20px; border-radius: 8px; border: 1px solid rgba(197,160,89,0.5); background: transparent; color: #c5a059; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s; font-family: 'DM Sans', sans-serif; }
        .btn-follow:hover { background: rgba(197,160,89,0.1); }
        .btn-follow.following { background: rgba(197,160,89,0.15); border-color: #c5a059; }
        .product-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); margin-top: 16px; }
        .review-form { background: rgba(15,15,15,0.7); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 14px; }
        .rating-stars { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; }
        .rating-stars input[type="radio"] { display: none; }
        .rating-stars label { font-size: 28px; color: rgba(255,255,255,0.2); cursor: pointer; transition: color 0.15s; }
        .rating-stars label:hover, .rating-stars label:hover ~ label, .rating-stars input[type="radio"]:checked ~ label { color: #c5a059; }
        .review-form textarea { padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.08); color: white; font-family: 'DM Sans', sans-serif; font-size: 14px; resize: vertical; min-height: 80px; }
        .review-form button { padding: 10px 20px; border-radius: 8px; border: none; background: #c5a059; color: #111; font-weight: 500; font-size: 15px; cursor: pointer; transition: all 0.2s; font-family: 'DM Sans', sans-serif; align-self: flex-start; }
        .review-form button:hover { background: #d4b06a; }
        .review-item { background: rgba(15,15,15,0.7); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 16px 20px; margin-bottom: 12px; }
        .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .review-header strong { color: white; font-weight: 500; font-size: 14px; }
        .review-date { font-size: 12px; color: rgba(255,255,255,0.4); }
        .review-stars { color: #c5a059; font-size: 16px; margin-bottom: 6px; }
        .review-comment { font-size: 14px; color: rgba(255,255,255,0.65); line-height: 1.6; }
        .media-voto { color: #c5a059; font-size: 16px; font-weight: 500; margin-bottom: 16px; }
        .alert-success { padding: 10px 14px; border-radius: 8px; background: rgba(80,200,100,0.1); border: 1px solid rgba(80,200,100,0.3); color: #5cc877; font-size: 14px; margin-bottom: 16px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>
        <a class="btn-back" href="mainPage.php">← Torna alla home</a>
    </nav>

    <main class="container">

        <!-- INTESTAZIONE PROFILO -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar"><?php echo $inizialiAvatar; ?></div>
                <h1 class="profile-name">
                    <?php echo htmlspecialchars($datiUtente['nome'] . ' ' . $datiUtente['cognome']); ?>
                </h1>
                <p class="profile-email"><?php echo htmlspecialchars($datiUtente['email']); ?></p>
            </div>

            <div class="profile-stats">
                <div class="stat">
                    <div class="stat-number"><?php echo $statisticheArticoli['totale']; ?></div>
                    <p class="stat-label">Articoli in vendita</p>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo $numeroFollower['totale']; ?></div>
                    <p class="stat-label">Follower</p>
                </div>
                <?php if (!empty($listaRecensioni)): ?>
                <div class="stat">
                    <div class="stat-number"><?php echo $mediaVoto; ?>★</div>
                    <p class="stat-label">Valutazione media</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Mostriamo il bottone Segui SOLO SE chi guarda è un utente registrato -->
            <?php if ($idUtenteLoggato): ?>
            <div class="profile-actions">
                <button class="btn-follow <?php echo $giaSegui ? 'following' : ''; ?>"
                        id="followBtn"
                        onclick="toggleFollow(<?php echo $idUtenteProfilo; ?>)">
                    <?php echo $giaSegui ? 'Stai seguendo' : '+ Segui'; ?>
                </button>
            </div>
            <?php endif; ?>

            <p class="join-date">Membro dal <?php echo date('d/m/Y', strtotime($datiUtente['dataRegistrazione'])); ?></p>
        </div>

        <!-- SEZIONE: LASCIA UNA RECENSIONE (Solo se loggati) -->
        <?php if ($idUtenteLoggato): ?>
        <div class="section-block">
            <h2 class="section-title">Lascia una recensione</h2>

            <?php if (isset($_GET['review_success'])): ?>
                <div class="alert-success">✓ Recensione inviata con successo! Grazie per il tuo feedback.</div>
            <?php endif; ?>

            <?php if ($giaRecensito): ?>
                <div class="empty-box">Hai già lasciato una recensione per questo utente. Le regole ne permettono una sola!</div>
            <?php else: ?>
                <!-- Modulo per la recensione -->
                <form action="addReview.php" method="POST" class="review-form">
                    <!-- Campo nascosto che passa l'ID del tizio che stiamo recensendo -->
                    <input type="hidden" name="fkRecensitoId" value="<?php echo $idUtenteProfilo; ?>">

                    <div>
                        <p style="font-size:14px; color:rgba(255,255,255,0.6); margin-bottom:8px;">Valutazione *</p>
                        <!-- Geniale l'utilizzo dei radio button al contrario per fare l'effetto riempimento delle stelline! -->
                        <div class="rating-stars">
                            <input type="radio" name="valutazione" id="s5" value="5" required><label for="s5">★</label>
                            <input type="radio" name="valutazione" id="s4" value="4"><label for="s4">★</label>
                            <input type="radio" name="valutazione" id="s3" value="3"><label for="s3">★</label>
                            <input type="radio" name="valutazione" id="s2" value="2"><label for="s2">★</label>
                            <input type="radio" name="valutazione" id="s1" value="1"><label for="s1">★</label>
                        </div>
                    </div>

                    <textarea name="commento" placeholder="Scrivi la tua recensione (opzionale)..."></textarea>
                    <button type="submit">Invia recensione</button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- SEZIONE: LISTA DELLE RECENSIONi RICEVUTE -->
        <?php if (!empty($listaRecensioni)): ?>
        <div class="section-block">
            <h2 class="section-title">Recensioni ricevute</h2>
            <p class="media-voto">Media: <?php echo $mediaVoto; ?>/5 — basata su <?php echo count($listaRecensioni); ?> recensioni.</p>

            <?php foreach ($listaRecensioni as $recensione): ?>
                <div class="review-item">
                    <div class="review-header">
                        <strong><?php echo htmlspecialchars($recensione['nome'] . ' ' . $recensione['cognome']); ?></strong>
                        <span class="review-date"><?php echo date('d/m/Y', strtotime($recensione['dataRecensione'])); ?></span>
                    </div>
                    <div class="review-stars">
                        <!-- Disegna le stelle piene in base al voto, e le stelle vuote (5 - voto) -->
                        <?php echo str_repeat('★', $recensione['valutazione']) . str_repeat('☆', 5 - $recensione['valutazione']); ?>
                    </div>
                    <?php if (!empty($recensione['commento'])): ?>
                        <div class="review-comment"><?php echo htmlspecialchars($recensione['commento']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- SEZIONE: ARTICOLI IN VENDITA DI QUESTO UTENTE -->
        <div class="section-block">
            <h2 class="section-title">Articoli in vendita di <?php echo htmlspecialchars($datiUtente['nome']); ?></h2>
            <?php if (empty($articoliInVendita)): ?>
                <div class="empty-box">Nessun articolo in vendita al momento.</div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($articoliInVendita as $articolo): ?>
                        <div class="product-card" onclick="location.href='articleDetail.php?id=<?php echo $articolo['idArticolo']; ?>'">
                            <div class="card-image-wrapper">
                                <?php if ($articolo['immagine']): ?>
                                    <img src="../../uploads/articoli/<?php echo htmlspecialchars($articolo['immagine']); ?>" alt="<?php echo htmlspecialchars($articolo['titolo']); ?>" class="card-image">
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

    <!-- JS per gestire il pulsante SEGUI (Identico a quello nella pagina dettaglio articolo) -->
    <script>
        function toggleFollow(idVenditore) {
            const btn = document.getElementById('followBtn');
            const staGiaSeguendo = btn.classList.contains('following');

            fetch('followUser.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'idSeguito=' + idVenditore + '&action=' + (staGiaSeguendo ? 'unfollow' : 'follow')
            })
            .then(risposta => risposta.json())
            .then(dati => {
                if (dati.success) {
                    btn.classList.toggle('following');
                    btn.textContent = staGiaSeguendo ? '+ Segui' : 'Stai seguendo';
                } else {
                    alert('Errore: ' + dati.error);
                }
            })
            .catch(() => alert('Errore di rete'));
        }
    </script>

</body>
</html>