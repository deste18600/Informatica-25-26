<?php
require_once('../include/menuchoice.php');

$idUtenteLoggato = $_SESSION['userId'];





try {
    // Dati Personali 
    $sqlUtente = "SELECT nome, cognome, email, dataRegistrazione FROM Utente WHERE idUtente = :userId";
    $istruzioneUtente = DBHandler::getPDO()->prepare($sqlUtente);
    $istruzioneUtente->execute([':userId' => $idUtenteLoggato]);
    $datiUtente = $istruzioneUtente->fetch();




    //  Conteggio dei tuoi articoli in vendita 
    $sqlConteggioArticoli = "SELECT COUNT(*) as totale FROM ArticoloInVendita WHERE fkUtenteId = :userId";
    $istruzioneConteggio = DBHandler::getPDO()->prepare($sqlConteggioArticoli);
    $istruzioneConteggio->execute([':userId' => $idUtenteLoggato]);
    $statisticheArticoli = $istruzioneConteggio->fetch();





    // Numero follower 
    $sqlSeguaci = "SELECT COUNT(*) as totale FROM Segue WHERE idSeguito = :userId";

    $istruzioneSeguaci = DBHandler::getPDO()->prepare($sqlSeguaci);
    $istruzioneSeguaci->execute([':userId' => $idUtenteLoggato]);
    $numeroFollower = $istruzioneSeguaci->fetch();




    //  La lista articoli in vendita 
    $sqlMieiArticoli = "SELECT idArticolo, titolo, prezzo, categoria, stato 
                        FROM ArticoloInVendita 
                        WHERE fkUtenteId = :userId ORDER BY dataPost DESC";

    $istruzioneMieiArticoli = DBHandler::getPDO()->prepare($sqlMieiArticoli);
    $istruzioneMieiArticoli->execute([':userId' => $idUtenteLoggato]);



    // fetchAll() perché voglio tutti gli articoli (con fetch si ferma al primo)
    $mieiArticoli = $istruzioneMieiArticoli->fetchAll();








    // La lista di ciò che hai ACQUISTATO  
    // Uniamo (JOIN) tre tabelle: Acquisti, Articoli e Utente (per sapere il nome di chi ce lo ha venduto)
    $sqlAcquisti = "SELECT a.idArticolo, a.titolo, a.prezzo, a.categoria, a.immagine,
                           u.nome as venditore_nome, u.cognome as venditore_cognome,
                           ac.dataAcquisto
                    FROM Acquisti ac
                    JOIN ArticoloInVendita a ON ac.fkArticoloId = a.idArticolo
                    JOIN Utente u ON a.fkUtenteId = u.idUtente
                    WHERE ac.fkAcquirenteId = :userId
                    ORDER BY ac.dataAcquisto DESC";
            
    $istruzioneAcquisti = DBHandler::getPDO()->prepare($sqlAcquisti);
    $istruzioneAcquisti->execute([':userId' => $idUtenteLoggato]);
    $mieiAcquisti = $istruzioneAcquisti->fetchAll();

} catch (PDOException $e) {
    die("Errore nel recupero dati del profilo: " . $e->getMessage());
}
?>






<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/profilePage.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <title>Il tuo Profilo - Chordly</title>
</head>
<body>

    <nav class="navbar">
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>
        <a class="btn-back" href="mainPage.php">← Torna alla home</a>
    </nav>

    <main class="container">

        <!-- CARD PRINCIPALE DEL PROFILO -->

        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">

                    <!-- Estraiamo le iniziali dal nome e cognome -->

                    <!-- strtoupper() per mettere tutto maiuscolo, substr() per prendere solo la prima lettera di nome e cognome 0 e da dove iniziare 1 e quella da prendere -->
                    
                    <?php echo strtoupper(substr($datiUtente['nome'], 0, 1) . substr($datiUtente['cognome'], 0, 1)); ?>
                </div>

                   <!-- questa parte di codice mostra il nome e cognome dell'utente e usa echo per stampare (nome e cognome) su html e htmlspecialchars per proteggere da cross site scripting xss  -->
                <h1 class="profile-name">
                    <?php echo htmlspecialchars($datiUtente['nome'] . ' ' . $datiUtente['cognome']); ?>
                </h1>

                 <!-- questa parte di codice mostra la email dell'utente e usa echo per stamparla su html e htmlspecialchars per proteggere da cross site scripting xss  -->
                <p class="profile-email">
                    <?php echo htmlspecialchars($datiUtente['email']); ?>
                </p>

            </div>

            <!-- STATISTICHE  -->
            <div class="profile-stats">
                <div class="stat">
                    <div class="stat-number"><?php echo $statisticheArticoli['totale']; ?></div>
                    <p class="stat-label">Articoli in vendita</p>
                </div>

                <div class="stat">
                    <div class="stat-number"><?php echo $numeroFollower['totale']; ?></div>
                    <p class="stat-label">Follower</p>
                </div>

                <div class="stat">
                    <div class="stat-number"><?php echo count($mieiAcquisti); ?></div>
                    <p class="stat-label">Acquisti effettuati</p>
                </div>
            </div>

            <div class="profile-actions">
                <a href="addArticle.php" class="btn-action btn-sell">+ Vendi articolo</a>
            </div>
        </div>

        <!-- SEZIONE: I TUOI ANNUNCI (Quelli che stai vendendo) -->
        <div class="section-block">
            <h2 class="section-title">I tuoi annunci</h2>

            <?php if (empty($mieiArticoli)): ?>
                <div class="empty-box">Non hai ancora messo nulla in vendita.</div>
            <?php else: ?>
                <?php foreach ($mieiArticoli as $annuncio): ?>
                    <div class="article-item">
                        <div class="article-item-info">
                            <strong><?php echo htmlspecialchars($annuncio['titolo']); ?></strong>
                            <span class="article-item-price">€ <?php echo number_format($annuncio['prezzo'], 2, ',', '.'); ?></span>
                            <span class="article-item-cat"><?php echo htmlspecialchars($annuncio['categoria']); ?></span>
                        </div>
                        
                        <!-- 
                          PULSANTE ELIMINA: 
                          L'attributo onclick="return confirm(...)" è un trucco JavaScript velocissimo 
                          per mostrare una finestrella di conferma prima di far cliccare il link!
                        -->

                        <a href="deleteArticle.php?id=<?php echo $annuncio['idArticolo']; ?>"
                           onclick="return confirm('Sei sicuro di voler eliminare definitivamente questo annuncio?')"
                           class="btn-delete">
                            Elimina
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- SEZIONE: ARTICOLI ACQUISTATI -->
        <div class="section-block">
            <h2 class="section-title">Acquisti effettuati</h2>

            <?php if (empty($mieiAcquisti)): ?>
                <div class="empty-box">Non hai ancora acquistato nessun articolo.</div>
            <?php else: ?>
                <?php foreach ($mieiAcquisti as $acquisto): ?>
                    <div class="article-item">
                        <div class="article-item-info">
                            <strong><?php echo htmlspecialchars($acquisto['titolo']); ?></strong>
                            <span class="article-item-price">€ <?php echo number_format($acquisto['prezzo'], 2, ',', '.'); ?></span>
                            <span class="article-item-cat">
                                <?php echo htmlspecialchars($acquisto['categoria']); ?>
                                · Venduto da <?php echo htmlspecialchars($acquisto['venditore_nome'] . ' ' . $acquisto['venditore_cognome']); ?>
                            </span>
                            <span class="article-item-cat">
                                Acquistato il <?php echo date('d/m/Y', strtotime($acquisto['dataAcquisto'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</body>
</html>