<?php

// Salva l'ID dell'utente
$userId = $_SESSION['userId'];

// ===== VARIABILI PER I MESSAGGI =====
// Queste variabili vengono usate per mostrare messaggi di successo/errore
$messaggio = '';
$tipoMessaggio = ''; // 'success' o 'error'

// ===== PROCESSA IL FORM QUANDO VIENE INVIATO =====
// Quando l'utente clicca "Pubblica articolo", il form viene inviato con REQUEST_METHOD POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ===== LEGGI I DATI DAL FORM =====
    // trim() rimuove gli spazi bianchi all'inizio e fine
    $titolo = trim($_POST['titolo']);
    $descrizione = trim($_POST['descrizione']);
    $prezzo = floatval($_POST['prezzo']); // Converti a numero decimale
    $stato = $_POST['stato'];
    $categoria = $_POST['categoria'];
    
    // ===== VALIDAZIONE (Controlla se i dati sono corretti) =====
    // Controlla che i campi obbligatori siano riempiti
    if (empty($titolo) || empty($prezzo) || empty($stato) || empty($categoria)) {
        $messaggio = "Per favore riempi tutti i campi obbligatori";
        $tipoMessaggio = "error";
    } 
    // Controlla che il prezzo non sia negativo
    else if ($prezzo < 0) {
        $messaggio = "Il prezzo non può essere negativo";
        $tipoMessaggio = "error";
    } 
    // Se la validazione passa, salva nel database
    else {
        // Per ora ignoriamo le immagini (useremo placeholder)
        $nomeImmagine = null;
        
        try {
            // ===== QUERY PER INSERIRE L'ARTICOLO =====
            // INSERT INTO significa "aggiungi una nuova riga"
            // VALUES sono i valori da inserire
            // :userId, :titolo, ecc. sono placeholder per evitare SQL injection (sicurezza)
            $sql = "INSERT INTO ArticoloInVendita 
                    (fkUtenteId, titolo, descrizione, prezzo, stato, categoria, immagine, disponibilita)
                    VALUES (:userId, :titolo, :descrizione, :prezzo, :stato, :categoria, :immagine, TRUE)";
            
            // Prepara la query
            $sth = DBHandler::getPDO()->prepare($sql);
            
            // Esegui la query con i dati
            $sth->execute([
                ':userId' => $userId,
                ':titolo' => $titolo,
                ':descrizione' => $descrizione,
                ':prezzo' => $prezzo,
                ':stato' => $stato,
                ':categoria' => $categoria,
                ':immagine' => $nomeImmagine
            ]);
            
            // Se tutto va bene, mostra un messaggio di successo
            $messaggio = "Articolo aggiunto con successo! Reindirizzamento...";
            $tipoMessaggio = "success";
            
            // Reindirizza alla home dopo 2 secondi
            header("refresh:2; url=mainPage.php");
            
        } catch (PDOException $e) {
            // Se c'è un errore nel database, mostralo
            $messaggio = "Errore nell'aggiunta articolo: " . $e->getMessage();
            $tipoMessaggio = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/addArticle.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <title>Vendi un articolo - Chordly</title>
</head>
<body>

    <!--  NAVBAR -->
    <nav class="navbar">
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>
        <!-- Bottone per tornare indietro -->
        <a href="mainPage.php" class="btn-back">← Torna alla home</a>
    </nav>

    <!--  CONTENUTO PRINCIPALE -->
    <main class="add-article-container">
        <div class="form-wrapper">
            <!-- Titolo della pagina -->
            <h1>Vendi il tuo articolo</h1>
            <p class="form-subtitle">Completa il form per mettere in vendita il tuo strumento musicale</p>

            <!--  MESSAGGIO DI SUCCESSO/ERRORE -->
            <!-- Se c'è un messaggio, lo mostra -->
            <?php if ($messaggio): ?>
                <div class="alert alert-<?php echo $tipoMessaggio; ?>">
                    <?php echo htmlspecialchars($messaggio); ?>
                </div>
            <?php endif; ?>

            <!--  FORM PER AGGIUNGERE ARTICOLO -->
            <form method="POST" class="form-articolo">
                
                <!--  CAMPO: TITOLO -->
                <div class="form-group">
                    <label for="titolo">Titolo articolo *</label>
                    <!-- L'asterisco * significa che è obbligatorio -->
                    <input type="text" id="titolo" name="titolo" 
                           placeholder="es: Fender Stratocaster MIM" required>
                </div>

                <!-- RIGA CON TRE CAMPI: CATEGORIA, PREZZO, STATO -->
                <div class="form-row">
                    
                    <!-- CATEGORIA -->
                    <div class="form-group">
                        <label for="categoria">Categoria *</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Seleziona categoria</option>
                            <option value="chitarre">Chitarre</option>
                            <option value="bassi">Bassi</option>
                            <option value="batterie">Batterie</option>
                            <option value="tastiere">Tastiere</option>
                            <option value="accessori">Accessori</option>
                            <option value="altro">Altro</option>
                        </select>
                    </div>

                    <!-- PREZZO -->
                    <div class="form-group">
                        <label for="prezzo">Prezzo (€) *</label>
                        <!-- type="number" permette di inserire solo numeri -->
                        <!-- step="0.01" permette di inserire fino a 2 decimali -->
                        <!-- min="0" non permette numeri negativi -->
                        <input type="number" id="prezzo" name="prezzo" 
                               placeholder="0.00" step="0.01" min="0" required>
                    </div>

                    <!-- STATO -->
                    <div class="form-group">
                        <label for="stato">Stato *</label>
                        <select id="stato" name="stato" required>
                            <option value="">Seleziona stato</option>
                            <option value="ottimo">Ottimo</option>
                            <option value="buono">Buono</option>
                            <option value="difettato">Difettato</option>
                        </select>
                    </div>
                </div>

                <!-- CAMPO: DESCRIZIONE -->
                <div class="form-group">
                    <label for="descrizione">Descrizione</label>
                    
                    <textarea id="descrizione" name="descrizione" 
                              placeholder="Descrivi lo stato dell'articolo, eventuali difetti, accessori inclusi..." 
                              rows="6"></textarea>
                </div>

                <!--  PULSANTI -->
                <div class="form-actions">
                    
                    <button type="submit" class="btn-submit">Pubblica articolo</button>
                    
                    <a href="mainPage.php" class="btn-cancel">Annulla</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
