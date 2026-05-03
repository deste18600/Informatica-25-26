<?php
// 1. Richiamiamo il file centrale (che gestisce sessione, login e database)
require_once('../include/menuchoice.php');

// Salviamo l'ID dell'utente per collegarlo all'articolo che sta creando
$userId = $_SESSION['userId'];

// Prepariamo due variabili vuote che useremo per mostrare i messaggi a schermo (es. "Errore" o "Successo")
$messaggio = '';
$tipoMessaggio = '';

// 2. Controlliamo se l'utente ha premuto il pulsante per inviare il form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Raccogliamo e ripuliamo i testi inseriti
    $titolo      = trim($_POST['titolo']);
    $descrizione = trim($_POST['descrizione']);
    $stato       = $_POST['stato'];
    $categoria   = $_POST['categoria'];
    
    // floatval() assicura che il prezzo venga trattato come un numero con i decimali, non come testo
    $prezzo      = floatval($_POST['prezzo']); 

    // 3. Controlli di sicurezza (Validazione)
    // Se manca anche solo un dato obbligatorio...
    if (empty($titolo) || empty($prezzo) || empty($stato) || empty($categoria)) {
        $messaggio = "Per favore, compila tutti i campi obbligatori (quelli con l'asterisco *).";
        $tipoMessaggio = "error";
    } 
    // Se l'utente fa il furbo e mette un prezzo negativo...
    elseif ($prezzo < 0) {
        $messaggio = "Il prezzo non può essere negativo.";
        $tipoMessaggio = "error";
    } 
    else {
        // 4. GESTIONE DELL'IMMAGINE
        $nomeImmagine = null; // Di base, partiamo presupponendo che non ci sia immagine

        // Controlliamo se è stato caricato un file e se non ci sono stati errori di caricamento (UPLOAD_ERR_OK)
        if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
            
            // Decidiamo in quale cartella del server salvare la foto
            $cartellaDestinazione = '../../uploads/articoli/';

            // Se la cartella non esiste ancora, chiediamo a PHP di crearla
            if (!is_dir($cartellaDestinazione)) {
                mkdir($cartellaDestinazione, 0755, true);
            }

            // Definiamo quali tipi di immagine accettiamo
            $estensioniPermesse = ['jpg', 'jpeg', 'png', 'webp'];
            
            // Troviamo l'estensione del file caricato e la mettiamo in minuscolo (es. da "Foto.JPG" a "jpg")
            $estensioneFile = strtolower(pathinfo($_FILES['immagine']['name'], PATHINFO_EXTENSION));

            // Il file caricato ha l'estensione giusta?
            if (!in_array($estensioneFile, $estensioniPermesse)) {
                $messaggio = "Formato immagine non valido. Usa solo file JPG, PNG o WEBP.";
                $tipoMessaggio = "error";
            } 
            // Il file è troppo pesante? (Il limite è 5MB: 5 * 1024KB * 1024Byte)
            elseif ($_FILES['immagine']['size'] > 5 * 1024 * 1024) {
                $messaggio = "L'immagine è troppo pesante. Il limite massimo è di 5MB.";
                $tipoMessaggio = "error";
            } 
            else {
                // Se tutto va bene, creiamo un NOME UNICO per il file. 
                // Serve per evitare che due utenti carichino una foto chiamata "chitarra.jpg" sovrascrivendosi a vicenda!
                $nomeFileUnico = uniqid('art_') . '.' . $estensioneFile; // Diventa qualcosa tipo: art_65a4f...jpg
                $percorsoCompleto = $cartellaDestinazione . $nomeFileUnico;
                
                // Spostiamo fisicamente l'immagine dalla cartella temporanea del server a quella definitiva
                if (move_uploaded_file($_FILES['immagine']['tmp_name'], $percorsoCompleto)) {
                    $nomeImmagine = $nomeFileUnico; // Salviamo il nome per metterlo nel database
                }
            }
        }

        // 5. SALVATAGGIO NEL DATABASE
        // Se non ci sono stati messaggi di errore fino ad ora (es. per colpa dell'immagine)...
        if ($tipoMessaggio !== 'error') {
            try {
                // Prepariamo il comando per salvare i dati
                // Nota: impostiamo "disponibilita = TRUE" di default perché l'articolo è appena stato creato
                $sql = "INSERT INTO ArticoloInVendita 
                        (fkUtenteId, titolo, descrizione, prezzo, stato, categoria, immagine, disponibilita)
                        VALUES (:userId, :titolo, :descrizione, :prezzo, :stato, :categoria, :immagine, TRUE)";

                $istruzione = DBHandler::getPDO()->prepare($sql);
                
                // Inseriamo i dati al posto dei segnaposto
                $istruzione->execute([
                    ':userId'      => $userId,
                    ':titolo'      => $titolo,
                    ':descrizione' => $descrizione,
                    ':prezzo'      => $prezzo,
                    ':stato'       => $stato,
                    ':categoria'   => $categoria,
                    ':immagine'    => $nomeImmagine
                ]);

                // Successo! 
                $messaggio = "Articolo pubblicato con successo! Ti sto riportando alla vetrina...";
                $tipoMessaggio = "success";
                
                // "refresh:2" fa aspettare 2 secondi per far leggere il messaggio all'utente prima di cambiare pagina
                header("refresh:2; url=mainPage.php");

            } catch (PDOException $e) {
                // Se c'è un errore del database (es. database disconnesso)
                $messaggio = "Ops! C'è stato un problema nel salvataggio: " . $e->getMessage();
                $tipoMessaggio = "error";
            }
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
    <style>
        /* (Ho mantenuto i tuoi stili interni per il riquadro dell'immagine) */
        .upload-area {
            border: 2px dashed rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .upload-area:hover { border-color: rgba(197,160,89,0.5); }
        .upload-area input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .upload-area .upload-icon { font-size: 32px; margin-bottom: 8px; }
        .upload-area p { color: rgba(255,255,255,0.4); font-size: 13px; margin: 0; }
        #preview-img {
            width: 100%; max-height: 200px; object-fit: cover;
            border-radius: 8px; display: none; margin-top: 12px;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>
        <a href="mainPage.php" class="btn-back">← Torna alla home</a>
    </nav>

    <main class="add-article-container">
        <div class="form-wrapper">
            <h1>Vendi il tuo articolo</h1>
            <p class="form-subtitle">Completa il form per mettere in vendita il tuo strumento musicale</p>

            <!-- Mostriamo il banner colorato se c'è un messaggio (errore o successo) -->
            <?php if ($messaggio): ?>
                <div class="alert alert-<?php echo $tipoMessaggio; ?>">
                    <?php echo htmlspecialchars($messaggio); ?>
                </div>
            <?php endif; ?>

            <!-- 
              IMPORTANTISSIMO: enctype="multipart/form-data" 
              Senza questa riga, il form manderà solo i testi e NON invierà il file dell'immagine al server!
            -->
            <form method="POST" class="form-articolo" enctype="multipart/form-data">

                <div class="form-group">
                    <label for="titolo">Titolo articolo *</label>
                    <input type="text" id="titolo" name="titolo" placeholder="es: Fender Stratocaster MIM" required>
                </div>

                <div class="form-row">
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

                    <div class="form-group">
                        <label for="prezzo">Prezzo (€) *</label>
                        <input type="number" id="prezzo" name="prezzo" placeholder="0.00" step="0.01" min="0" required>
                    </div>

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

                <div class="form-group">
                    <label for="descrizione">Descrizione</label>
                    <textarea id="descrizione" name="descrizione"
                              placeholder="Descrivi lo stato dell'articolo, eventuali difetti, accessori inclusi..."
                              rows="6"></textarea>
                </div>

                <!-- ZONA DI CARICAMENTO FOTO -->
                <div class="form-group">
                    <label>Foto articolo</label>
                    <div class="upload-area" id="uploadArea">
                        <input type="file" name="immagine" id="immagineInput"
                               accept="image/jpeg,image/png,image/webp"
                               onchange="previewImage(event)">
                        <div class="upload-icon">📷</div>
                        <p>Clicca per caricare una foto (JPG, PNG, WEBP — max 5MB)</p>
                    </div>
                    <!-- Qui apparirà l'anteprima della foto appena l'utente la seleziona -->
                    <img id="preview-img" src="" alt="Anteprima">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Pubblica articolo</button>
                    <a href="mainPage.php" class="btn-cancel">Annulla</a>
                </div>
            </form>
        </div>
    </main>

    <!-- Script per mostrare l'anteprima dell'immagine prima dell'invio -->
    <script>
        function previewImage(event) {
            // Prendiamo il file che l'utente ha appena selezionato
            const file = event.target.files[0];
            if (!file) return; // Se ha annullato, ci fermiamo
            
            // FileReader è uno strumento di Javascript per leggere i file nel browser
            const reader = new FileReader();
            
            // Quando il file finisce di caricare...
            reader.onload = e => {
                // Prendiamo il tag <img> nascosto
                const img = document.getElementById('preview-img');
                // Gli diamo come sorgente (src) il file appena letto
                img.src = e.target.result;
                img.style.display = 'block'; // Lo rendiamo visibile
                
                // Nascondiamo l'icona della macchina fotografica e aggiorniamo il testo col nome del file
                document.querySelector('.upload-area .upload-icon').style.display = 'none';
                document.querySelector('.upload-area p').textContent = file.name;
            };
            
            // Diciamo a FileReader di iniziare a leggere il file come se fosse un URL immagine
            reader.readAsDataURL(file);
        }
    </script>

</body>
</html>