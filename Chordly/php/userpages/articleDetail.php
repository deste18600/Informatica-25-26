<?php
require_once('../include/menuchoice.php');

//controllo se id manca nell'indirizzo url e se è un numero
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    //se no torna alla mainaPage
    header('Location: mainPage.php');
    exit;
}

//salvo sessione utente
$idUtenteLoggato = $_SESSION['userId'];
//salvo id articolo da url (quello che ho controllato prima)
$idArticolo = (int)$_GET['id']; 

try {
    // prendi tutti i dati dalla tabella articoli e uniscili con i dati dell'utente (nome cognome email) dove l'articolo è acnora disponibile e salvalo come vendId)
    $sql = "SELECT a.*, u.nome, u.cognome, u.email, u.idUtente as vendId
            FROM ArticoloInVendita a
            JOIN Utente u ON a.fkUtenteId = u.idUtente
            WHERE a.idArticolo = :id AND a.disponibilita = TRUE";
            
    $istruzione = DBHandler::getPDO()->prepare($sql);
    $istruzione->bindParam(':id', $idArticolo, PDO::PARAM_INT);
    $istruzione->execute();
    
    $articolo = $istruzione->fetch();


    if (!$articolo) {
        //se non trovato torna alla mainPage
        header('Location: mainPage.php');
        exit;
    }


   //l'utente segue già il venditore?
    //controllo se la riga(uso 1 per guardare la riga in generale) dove idFollower = me e idSeguito = lui esiste nella tabella Segue

    $sqlSeguito = "SELECT 1 FROM Segue WHERE idFollower = :me AND idSeguito = :lui";

    $istruzioneS = DBHandler::getPDO()->prepare($sqlSeguito);

    $istruzioneS->execute([':me' => $idUtenteLoggato, ':lui' => $articolo['vendId']]);

    // Trasformazione risultato in un valore Vero/Falso (

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







// Generazione delle inziali del venditore (es. da Mario Rossi a MR) per l'avatar
$inizialiVenditore = strtoupper(substr($articolo['nome'], 0, 1) . substr($articolo['cognome'], 0, 1));


// Controllo se utente loggato è lo stesso che sta vendendo l'articolo 
$isMioAnnuncio = ($idUtenteLoggato == $articolo['vendId']);


// Preparazione percorso immagine
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
            
            <div class="main-image-wrapper" id="mainImgWrapper">

                <?php if ($imgPath): ?>

                    <!-- L'immagine viene mostrata -->
                    <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($articolo['titolo']); ?>" class="main-image" id="mainImg">
                <?php else: ?>
                    <!-- Se l'immagine manca, mostriamo il placeholder -->
                    <div class="placeholder-img"></div>
                <?php endif; ?>
            </div>

            <!-- Badge dello stato -->
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
                    </div>
                </div>

                <!-- Se NON è il mio annuncio, tasti Segui e Compra -->
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


                <!-- Se è il MIO annuncio-->
                <?php else: ?>
                    <div class="my-listing-badge">Il tuo annuncio</div>
                <?php endif; ?>
            </div>
        </div>
    </main>



    <!-- JAVASCRIPT: Logica per i pulsanti -->
    <script>

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



        // FUNZIONE DI ACQUISTO 
        function buyArticle(idDellArticolo) {

            //conferma all'utente

            if (!confirm("Sei sicuro di voler acquistare questo articolo?")) {
                return;
            }

            // Mandiamo la richiesta a (buyArticle.php)
            fetch('buyArticle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'articleId=' + idDellArticolo
            })

            .then(risposta => risposta.json())
            .then(dati => {
                if (dati.success) {
                    alert(dati.message); 

                    // Lo riportiamo alla vetrina
                    
                    window.location.href = 'mainPage.php'; 
                } else {
                    alert("Errore durante l'acquisto: " + dati.error);
                }
            })
            .catch(() => alert("Errore di rete durante l'acquisto"));
        }
    </script>

</body>
</html>

