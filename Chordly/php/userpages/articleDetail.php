<?php
require_once('../include/menuchoice.php');

//controllo se id (del articolo) manca e se è un numero
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: mainPage.php');
    exit;
}

$idUtenteLoggato = $_SESSION['userId'];

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
        header('Location: mainPage.php');
        exit;
    }


   //l'utente segue già il venditore?
  //controllo la riga(uso 1 per guardare la riga in generale) dove idFollower = me e idSeguito = lui esiste nella tabella Segue

    $sqlSeguito = "SELECT 1 FROM Segue WHERE idFollower = :me AND idSeguito = :lui";

    $istruzioneS = DBHandler::getPDO()->prepare($sqlSeguito);

    $istruzioneS->execute([':me' => $idUtenteLoggato, ':lui' => $articolo['vendId']]);

    // Trasformazione risultato in un valore Vero/Falso 

    $giaSegui = (bool)$istruzioneS->fetch(); 



    // Numero di follower del venditore

    $sqlFollower = "SELECT COUNT(*) as tot FROM Segue WHERE idSeguito = :lui";

    $istruzioneF = DBHandler::getPDO()->prepare($sqlFollower);

    $istruzioneF->execute([':lui' => $articolo['vendId']]);

    $numFollower = $istruzioneF->fetch()['tot'];




    //Articoli consigliati
    $sqlArticoli = "SELECT idArticolo, titolo, prezzo, immagine
               FROM ArticoloInVendita
               WHERE fkUtenteId = :idVenditore 
               AND idArticolo != :idArticoloCorrente 
               AND disponibilita = TRUE
               ORDER BY dataPost DESC";

    $istruzioneA = DBHandler::getPDO()->prepare($sqlArticoli);

    $istruzioneA->execute([
        ':idVenditore'       => $articolo['vendId'], 
        ':idArticoloCorrente' => $idArticolo
    ]);

    $altriArticoli = $istruzioneA->fetchAll();

    } catch (PDOException $e) {
    die("Errore di connessione: " . $e->getMessage());
    }







// Generazione iniziali avatar
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
        
        <?php if ($imgPath): ?>
            <!-- Mostro l'immagine così com'è -->
            <img src="<?php echo $imgPath; ?>" class="main-image">
        <?php else: ?>
            <!-- Rettangolo grigio se manca la foto -->
            <div class="placeholder-img">Nessuna foto</div>
        <?php endif; ?>

        <!-- Badge dello stato (senza ucfirst) -->
        <div class="stato-badge">
            Stato: <?php echo $articolo['stato']; ?>
        </div>

    </div>

    <!-- COLONNA DESTRA: INFO E BOTTONI -->
    <div class="info-col">
        
        <!-- Titolo -->
        <div class="categoria-label"><?php echo $articolo['categoria']; ?></div>
        <h1 class="titolo"><?php echo $articolo['titolo']; ?></h1>
        
        <!-- Prezzo  -->
        <div class="prezzo">€ <?php echo $articolo['prezzo']; ?></div>

        <!-- Descrizione  -->
        <?php if (!empty($articolo['descrizione'])): ?>
            <div class="descrizione">
                <h3>Descrizione</h3>
                <p><?php echo nl2br($articolo['descrizione']); ?></p>
            </div>
        <?php endif; ?>

        <!-- SCHEDA VENDITORE -->
        <div class="seller-card">
            <div class="seller-avatar"><?php echo $inizialiVenditore; ?></div>
            <div class="seller-name">
                <?php echo $articolo['nome'] . ' ' . $articolo['cognome']; ?>
            </div>


            <!-- Azioni se l'utente è un altro -->
            <?php if (!$isMioAnnuncio): ?>
             
                <div class="seller-actions">
                    
                    <button class="btn-follow <?php echo $giaSegui ? 'following' : ''; ?>" id="followBtn" onclick="toggleFollow(<?php echo $articolo['vendId']; ?>)">
                        <?php echo $giaSegui ? 'Seguito' : '+ Segui'; ?>
                    </button>

                    <button class="btn-buy" onclick="buyArticle(<?php echo $idArticolo; ?>)">
                        Acquista
                    </button>
                    
                </div>
            <?php else: ?>
                <div class="my-listing-badge">Questo è il tuo annuncio</div>
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

