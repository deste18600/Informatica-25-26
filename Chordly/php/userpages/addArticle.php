<?php
require_once('../include/menuchoice.php');

if (!isset($_SESSION['userId'])) {
    header('Location: userLoginpage.php');
    exit;
}

$userId = $_SESSION['userId'];
$messaggio = '';
$tipoMessaggio = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titolo      = trim($_POST['titolo']);
    $descrizione = trim($_POST['descrizione']);
    $prezzo      = floatval($_POST['prezzo']);
    $stato       = $_POST['stato'];
    $categoria   = $_POST['categoria'];

    if (empty($titolo) || empty($prezzo) || empty($stato) || empty($categoria)) {
        $messaggio = "Per favore riempi tutti i campi obbligatori";
        $tipoMessaggio = "error";
    } elseif ($prezzo < 0) {
        $messaggio = "Il prezzo non può essere negativo";
        $tipoMessaggio = "error";
    } else {

        $nomeImmagine = null;

        if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/articoli/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $estensioni = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['immagine']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $estensioni)) {
                $messaggio = "Formato immagine non valido. Usa JPG, PNG o WEBP.";
                $tipoMessaggio = "error";
            } elseif ($_FILES['immagine']['size'] > 5 * 1024 * 1024) {
                // CORRETTO: sia il limite che il messaggio dicono 5MB
                $messaggio = "L'immagine non deve superare 5MB.";
                $tipoMessaggio = "error";
            } else {
                $nomeFile = uniqid('art_') . '.' . $ext;
                $percorsoCompleto = $uploadDir . $nomeFile;
                if (move_uploaded_file($_FILES['immagine']['tmp_name'], $percorsoCompleto)) {
                    $nomeImmagine = $nomeFile;
                }
            }
        }

        if ($tipoMessaggio !== 'error') {
            try {
                $sql = "INSERT INTO ArticoloInVendita 
                        (fkUtenteId, titolo, descrizione, prezzo, stato, categoria, immagine, disponibilita)
                        VALUES (:userId, :titolo, :descrizione, :prezzo, :stato, :categoria, :immagine, TRUE)";

                $sth = DBHandler::getPDO()->prepare($sql);
                $sth->execute([
                    ':userId'      => $userId,
                    ':titolo'      => $titolo,
                    ':descrizione' => $descrizione,
                    ':prezzo'      => $prezzo,
                    ':stato'       => $stato,
                    ':categoria'   => $categoria,
                    ':immagine'    => $nomeImmagine
                ]);

                $messaggio = "Articolo aggiunto con successo! Reindirizzamento...";
                $tipoMessaggio = "success";
                header("refresh:2; url=mainPage.php");

            } catch (PDOException $e) {
                $messaggio = "Errore nell'aggiunta articolo: " . $e->getMessage();
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

            <?php if ($messaggio): ?>
                <div class="alert alert-<?php echo $tipoMessaggio; ?>">
                    <?php echo htmlspecialchars($messaggio); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form-articolo" enctype="multipart/form-data">

                <div class="form-group">
                    <label for="titolo">Titolo articolo *</label>
                    <input type="text" id="titolo" name="titolo"
                           placeholder="es: Fender Stratocaster MIM" required>
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
                        <input type="number" id="prezzo" name="prezzo"
                               placeholder="0.00" step="0.01" min="0" required>
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

                <div class="form-group">
                    <label>Foto articolo</label>
                    <div class="upload-area" id="uploadArea">
                        <input type="file" name="immagine" id="immagineInput"
                               accept="image/jpeg,image/png,image/webp"
                               onchange="previewImage(event)">
                        <div class="upload-icon">📷</div>
                        <p>Clicca per caricare una foto (JPG, PNG, WEBP — max 5MB)</p>
                    </div>
                    <img id="preview-img" src="" alt="Anteprima">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Pubblica articolo</button>
                    <a href="mainPage.php" class="btn-cancel">Annulla</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.getElementById('preview-img');
                img.src = e.target.result;
                img.style.display = 'block';
                document.querySelector('.upload-area .upload-icon').style.display = 'none';
                document.querySelector('.upload-area p').textContent = file.name;
            };
            reader.readAsDataURL(file);
        }
    </script>

</body>
</html>
