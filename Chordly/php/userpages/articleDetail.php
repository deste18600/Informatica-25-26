<?php
// 1. Richiamiamo il file centrale
require_once('../include/menuchoice.php');

// 2. Controllo di sicurezza: verifichiamo che ci sia un "id" nell'indirizzo web e che sia un numero
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Se qualcuno prova a scrivere a mano "articleDetail.php?id=pippo", lo rispediamo alla home!
    header('Location: mainPage.php');
    exit;
}

$idUtenteLoggato = $_SESSION['userId'];
$idArticolo = (int)$_GET['id']; // Trasformiamo l'id in un numero intero per sicurezza

try {
    // 3. Prepariamo la query per prendere tutti i dati dell'articolo E del suo venditore
    $sql = "SELECT a.*, u.nome, u.cognome, u.email, u.idUtente as vendId
            FROM ArticoloInVendita a
            JOIN Utente u ON a.fkUtenteId = u.idUtente
            WHERE a.idArticolo = :id AND a.disponibilita = TRUE";
            
    $istruzione = DBHandler::getPDO()->prepare($sql);
    $istruzione->bindParam(':id', $idArticolo, PDO::PARAM_INT);
    $istruzione->execute();
    
    // fetch() prende UN SOLO risultato (l'articolo specifico)
    $articolo = $istruzione->fetch();

    // Se l'articolo non esiste (o è stato già venduto/eliminato), torniamo alla home
    if (!$articolo) {
        header('Location: mainPage.php');
        exit;
    }

    // 4. L'utente loggato segue già questo venditore?
    // Facciamo una query veloce per vedere se esiste un collegamento tra di loro
    $sqlSeguito = "SELECT 1 FROM Segue WHERE idFollower = :me AND idSeguito = :lui";
    $istruzioneS = DBHandler::getPDO()->prepare($sqlSeguito);
    $istruzioneS->execute([':me' => $idUtenteLoggato, ':lui' => $articolo['vendId']]);
    // Trasformiamo il risultato in un valore Vero/Falso (booleano)
    $giaSegui = (bool)$istruzioneS->fetch(); 

    // 5. Quanti follower ha questo venditore in totale?
    $sqlFollower = "SELECT COUNT(*) as tot FROM Segue WHERE idSeguito = :lui";
    $istruzioneF = DBHandler::getPDO()->prepare($sqlFollower);
    $istruzioneF->execute([':lui' => $articolo['vendId']]);
    $numFollower = $istruzioneF->fetch()['tot'];

    // 6. Prendiamo gli ultimi 4 articoli messi in vendita da questo stesso utente (per consigliarli in fondo alla pagina)
    $sqlAltri = "SELECT idArticolo, titolo, prezzo, immagine
                 FROM ArticoloInVendita
                 WHERE fkUtenteId = :uid AND idArticolo != :aid AND disponibilita = TRUE
                 ORDER BY dataPost DESC LIMIT 4";
    $istruzioneA = DBHandler::getPDO()->prepare($sqlAltri);
    // :uid è il venditore, :aid è l'articolo attuale (che escludiamo con != per non mostrare il doppio)
    $istruzioneA->execute([':uid' => $articolo['vendId'], ':aid' => $idArticolo]);
    $altriArticoli = $istruzioneA->fetchAll();

} catch (PDOException $e) {
    die("Errore di connessione: " . $e->getMessage());
}

// Generiamo le inziali del venditore (es. da Mario Rossi a MR) per l'avatar
$inizialiVenditore = strtoupper(substr($articolo['nome'], 0, 1) . substr($articolo['cognome'], 0, 1));

// Controlliamo se chi sta guardando la pagina è lo STESSO che ha pubblicato l'annuncio
$isMioAnnuncio = ($idUtenteLoggato == $articolo['vendId']);

// Prepariamo il percorso dell'immagine (se esiste)
$imgPath = $articolo['immagine'] ? '../../uploads/articoli/' . htmlspecialchars($articolo['immagine']) : null;
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

        <!-- COLONNA SINISTRA: IMMAGINE -->
        <div class="image-col">
            <!-- Se c'è un'immagine, permettiamo di cliccarla per ingrandirla (Lightbox) -->
            <div class="main-image-wrapper" id="mainImgWrapper" <?php if($imgPath): ?>onclick="openLightbox()"<?php endif; ?>>
                
                <?php if ($imgPath): ?>
                    <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($articolo['titolo']); ?>" class="main-image" id="mainImg">
                    <div class="zoom-hint">🔍 Clicca per ingrandire</div>
                <?php else: ?>
                    <div class="placeholder-img">🎸</div>
                <?php endif; ?>
                
            </div>
            <span class="stato-badge <?php echo $articolo['stato']; ?>">
                <?php echo ucfirst($articolo['stato']); ?>
            </span>
        </div>

        <!-- COLONNA DESTRA: DETTAGLI E PULSANTI -->
        <div class="info-col">
            <div class="categoria-label"><?php echo ucfirst($articolo['categoria']); ?></div>
            <h1 class="titolo"><?php echo htmlspecialchars($articolo['titolo']); ?></h1>
            <div class="prezzo">€ <?php echo number_format($articolo['prezzo'], 2, ',', '.'); ?></div>

            <?php if (!empty($articolo['descrizione'])): ?>
                <div class="descrizione">
                    <h3>Descrizione</h3>
                    <!-- nl2br converte gli invii "a capo" fatti dall'utente nel testo in <br> html -->
                    <p><?php echo nl2br(htmlspecialchars($articolo['descrizione'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- SCHEDA DEL VENDITORE -->
            <div class="seller-card">
                <div class="seller-info">
                    <div class="seller-avatar"><?php echo $inizialiVenditore; ?></div>
                    <div>
                        <div class="seller-name">
                            <?php echo htmlspecialchars($articolo['nome'] . ' ' . $articolo['cognome']); ?>
                        </div>
                        <div class="seller-meta"><?php echo $numFollower; ?> follower</div>
                    </div>
                </div>

                <!-- Se NON è il mio annuncio, mostro i tasti Segui e Compra -->
                <?php if (!$isMioAnnuncio): ?>
                    <div class="seller-actions">
                        
                        <!-- Pulsante per Seguire -->
                        <button class="btn-follow <?php echo $giaSegui ? 'following' : ''; ?>" id="followBtn" onclick="toggleFollow(<?php echo $articolo['vendId']; ?>)">
                            <?php echo $giaSegui ? 'Stai seguendo' : '+ Segui'; ?>
                        </button>

                        <!-- Pulsante per Acquistare (usa Javascript per chiamare buyArticle.php) -->
                        <?php if ($articolo['disponibilita']): ?>
                        <button class="btn-buy" id="buyBtn" onclick="buyArticle(<?php echo $idArticolo; ?>)">
                            Acquista
                        </button>
                        <?php endif; ?>
                        
                    </div>
                <!-- Se è il MIO annuncio, mostro solo un'etichetta -->
                <?php else: ?>
                    <div class="my-listing-badge">Il tuo annuncio</div>
                <?php endif; ?>
            </div>

            <div class="post-date" style="margin-top: 20px;">
                Pubblicato il <?php echo date('d/m/Y', strtotime($articolo['dataPost'])); ?>
            </div>
        </div>
    </main>

    <!-- SEZIONE IN BASSO: ALTRI ARTICOLI DELLO STESSO VENDITORE -->
    <?php if (!empty($altriArticoli)): ?>
    <section class="altri-section">
        <div class="altri-inner">
            <h2>Altri annunci di <?php echo htmlspecialchars($articolo['nome']); ?></h2>
            <div class="altri-grid">
                <?php foreach ($altriArticoli as $art): ?>
                    <a href="articleDetail.php?id=<?php echo $art['idArticolo']; ?>" class="mini-card">
                        <div class="mini-img">
                            <?php if ($art['immagine']): ?>
                                <img src="../../uploads/articoli/<?php echo htmlspecialchars($art['immagine']); ?>" alt="<?php echo htmlspecialchars($art['titolo']); ?>">
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

    <!-- LIGHTBOX: La finestra nera che appare sopra lo schermo per ingrandire l'immagine -->
    <?php if ($imgPath): ?>
    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()">✕</button>
        <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($articolo['titolo']); ?>" class="lightbox-img" onclick="event.stopPropagation()">
    </div>
    <?php endif; ?>

    <!-- JAVASCRIPT: Logica per i pulsanti -->
    <script>
        // --- FUNZIONI LIGHTBOX (Ingrandimento immagine) ---
        function openLightbox() {
            document.getElementById('lightbox').classList.add('active');
            document.body.style.overflow = 'hidden'; // Blocca lo scorrimento della pagina dietro
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
            document.body.style.overflow = ''; // Riattiva lo scorrimento
        }
        // Chiudi il lightbox anche premendo il tasto Esc sulla tastiera
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeLightbox();
        });

        // --- FUNZIONE PER SEGUIRE/SMETTERE DI SEGUIRE ---
        function toggleFollow(idVenditore) {
            const btn = document.getElementById('followBtn');
            const staGiaSeguendo = btn.classList.contains('following');

            // Mandiamo una richiesta "invisibile" a followUser.php
            fetch('followUser.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'idSeguito=' + idVenditore + '&action=' + (staGiaSeguendo ? 'unfollow' : 'follow')
            })
            .then(risposta => risposta.json())
            .then(dati => {
                if (dati.success) {
                    // Cambiamo colore e testo al pulsante in modo dinamico
                    btn.classList.toggle('following');
                    btn.textContent = staGiaSeguendo ? '+ Segui' : 'Stai seguendo';
                }
            })
            .catch(() => alert('Errore di rete'));
        }

        // --- FUNZIONE DI ACQUISTO ---
        function buyArticle(idDellArticolo) {
            // Chiediamo conferma all'utente prima di procedere
            if (!confirm("Sei sicuro di voler acquistare questo articolo? L'azione è irreversibile.")) {
                return;
            }

            // Mandiamo la richiesta "invisibile" al motore di acquisto (buyArticle.php)
            fetch('buyArticle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'articleId=' + idDellArticolo
            })
            .then(risposta => risposta.json())
            .then(dati => {
                if (dati.success) {
                    alert(dati.message); // Diciamo "Acquistato con successo!"
                    window.location.href = 'mainPage.php'; // Lo riportiamo alla vetrina
                } else {
                    alert("Errore durante l'acquisto: " + dati.error);
                }
            })
            .catch(() => alert("Errore di rete durante l'acquisto"));
        }
    </script>

</body>
</html>