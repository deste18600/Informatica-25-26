<?php
require_once('../include/menuchoice.php');

// Se non c'è un ID valido nella sessione
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    //ritorno alla mainPage
    header('Location: mainPage.php');
    exit;
}

$idUtenteProfilo = (int)$_GET['id'];

$idUtenteLoggato = $_SESSION['userId'];

// se utente preme sul suo profilo pubblico lo riporta sul privato
if ($idUtenteLoggato && $idUtenteProfilo === $idUtenteLoggato) {
    header('Location: profilePage.php');
    exit;
}

try {
    // Dati generali dell'utente
    $sqlUtente = "SELECT idUtente, nome, cognome, email, dataRegistrazione FROM Utente WHERE idUtente = :idProfilo";
    $istruzioneUtente = DBHandler::getPDO()->prepare($sqlUtente);
    $istruzioneUtente->execute([':idProfilo' => $idUtenteProfilo]);
    $datiUtente = $istruzioneUtente->fetch();


    if (!$datiUtente) {
        header('Location: mainPage.php');
        exit;
    }

    // Statistiche (Quanti articoli in vendita ha)
    $sqlArticoliCount = "SELECT COUNT(*) as totale FROM ArticoloInVendita WHERE fkUtenteId = :idProfilo AND disponibilita = TRUE";
    $istruzioneCount = DBHandler::getPDO()->prepare($sqlArticoliCount);
    $istruzioneCount->execute([':idProfilo' => $idUtenteProfilo]);
    $statisticheArticoli = $istruzioneCount->fetch();

    // Statistiche (Quanti follower ha)
    $sqlFollower = "SELECT COUNT(*) as totale FROM Segue WHERE idSeguito = :idProfilo";
    $istruzioneFollower = DBHandler::getPDO()->prepare($sqlFollower);
    $istruzioneFollower->execute([':idProfilo' => $idUtenteProfilo]);
    $numeroFollower = $istruzioneFollower->fetch();

    // Lista dei suoi articoli attualmente in vendita
    $sqlArticoli = "SELECT idArticolo, titolo, prezzo, categoria, stato, immagine
                    FROM ArticoloInVendita
                    WHERE fkUtenteId = :idProfilo AND disponibilita = TRUE
                    ORDER BY dataPost DESC";
    $istruzioneArticoli = DBHandler::getPDO()->prepare($sqlArticoli);
    $istruzioneArticoli->execute([':idProfilo' => $idUtenteProfilo]);
    $articoliInVendita = $istruzioneArticoli->fetchAll();


    // Variabili per utente loggato (Segui e Recensioni)
    $giaSegui = false;
    $giaRecensito = false;



    if ($idUtenteLoggato) {
        // Controllo se lo stiamo già seguendo
        $sqlCheckFollow = "SELECT 1 FROM Segue WHERE idFollower = :me AND idSeguito = :lui";
        $istruzioneCheckFollow = DBHandler::getPDO()->prepare($sqlCheckFollow);
        $istruzioneCheckFollow->execute([':me' => $idUtenteLoggato, ':lui' => $idUtenteProfilo]);
        $giaSegui = (bool)$istruzioneCheckFollow->fetch();
        
        // controllo solo una recensione per utente 
        $sqlGiaRecensito = "SELECT 1 FROM RecensioneUtente WHERE fkRecensoreId = :me AND fkRecensitoId = :lui";
        $istruzioneGiaRecensito = DBHandler::getPDO()->prepare($sqlGiaRecensito);
        $istruzioneGiaRecensito->execute([':me' => $idUtenteLoggato, ':lui' => $idUtenteProfilo]);
        $giaRecensito = (bool)$istruzioneGiaRecensito->fetch();
    }

    //tutte recensioni ricevute da questo utente
    $sqlRecensioni = "SELECT r.valutazione, r.commento, r.dataRecensione, u.nome, u.cognome
                      FROM RecensioneUtente r
                      JOIN Utente u ON r.fkRecensoreId = u.idUtente
                      WHERE r.fkRecensitoId = :idProfilo
                      ORDER BY r.dataRecensione DESC";
    $istruzioneRecensioni = DBHandler::getPDO()->prepare($sqlRecensioni);
    $istruzioneRecensioni->execute([':idProfilo' => $idUtenteProfilo]);
    $listaRecensioni = $istruzioneRecensioni->fetchAll();




    //media voti
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
    <link rel="stylesheet" href="../../css/publicProfile.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <title>Profilo di <?php echo htmlspecialchars($datiUtente['nome'] . ' ' . $datiUtente['cognome']); ?> - Chordly</title>

</head>
<body>

    <nav class="navbar">
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>
        <a class="btn-back" href="mainPage.php">← Torna alla home</a>
    </nav>

    <main class="container">

        <!-- Intestazione profilo -->
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

            <p class="join-date">Membro dal <?php echo date('d/m/Y', strtotime($datiUtente['dataRegistrazione'])); ?></p>
        </div>

        <!-- Recensione -->
        <?php if ($idUtenteLoggato): ?>
        <div class="section-block">
            <h2 class="section-title">Lascia una recensione</h2>

            <?php if (isset($_GET['review_success'])): ?>
                <div class="alert-success">Recensione inviata con successo!.</div>
            <?php endif; ?>

            <?php if ($giaRecensito): ?>
                <div class="empty-box">Hai già lasciato una recensione per questo utente. Le regole ne permettono una sola!</div>
            <?php else: ?>

                <!-- Modulo per la recensione -->

                <form action="addReview.php" method="POST" class="review-form">

                    <!-- Recupero idutente (visualizzato)-->

                    <input type="hidden" name="fkRecensitoId" value="<?php echo $idUtenteProfilo; ?>">

                    <div>

                        <p style="font-size:14px; color:rgba(255,255,255,0.6); margin-bottom:8px;">Valutazione *</p>
                        <div class="rating-stars">

                            <input type="radio" name="valutazione" id="s5" value="5" required><label for="s5">★</label>
                            <input type="radio" name="valutazione" id="s4" value="4"><label for="s4">★</label>
                            <input type="radio" name="valutazione" id="s3" value="3"><label for="s3">★</label>
                            <input type="radio" name="valutazione" id="s2" value="2"><label for="s2">★</label>
                            <input type="radio" name="valutazione" id="s1" value="1"><label for="s1">★</label>
                        </div>
                    </div>

                    <textarea name="commento" placeholder="Scrivi la tua recensione "></textarea>

                    <button type="submit">Invia recensione</button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>



        <!-- Lista recensioni ricevute -->
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

        
<!-- Articoli in vendita dell'utente -->
<div class="section-block">
    <h2 class="section-title">Articoli di <?php echo $datiUtente['nome']; ?></h2>

    <?php if (empty($articoliInVendita)): ?>
        <div class="empty-box">Nessun articolo al momento.</div>
    <?php else: ?>

        <div class="product-grid">
            <?php foreach ($articoliInVendita as $articolo): ?>
                
                <div class="product-card" onclick="location.href='articleDetail.php?id=<?php echo $articolo['idArticolo']; ?>'">
                    
                    <!-- Immagine dell'articolo -->
                    <?php if ($articolo['immagine']): ?>
                        <img src="../../uploads/articoli/<?php echo $articolo['immagine']; ?>" class="card-image">
                    <?php else: ?>
                        <div class="card-image placeholder-img">NESSUNA FOTO</div>
                    <?php endif; ?>

                    <!-- Info dell'articolo -->
                    <div class="card-body">
                        <div class="card-price">€ <?php echo $articolo['prezzo']; ?></div>
                        <div class="card-title"><?php echo $articolo['titolo']; ?></div>
                        <div class="card-category"><?php echo $articolo['categoria']; ?></div>
                        <div class="stato-badge"><?php echo $articolo['stato']; ?></div>
                    </div>

                </div>

            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

    </main>














    <!-- JS per gestire il pulsante segui collegato a followUser.php -->
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