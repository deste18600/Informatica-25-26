<?php
require_once('../include/menuchoice.php');
 
$idUtenteLoggato = $_SESSION['userId'];

// LETTURA DEI FILTRI (Se non ci sono, uso valori predefiniti)
$filtroCategoria = $_GET['categoria'] ?? 'tutti';
$filtroStato     = $_GET['stato']     ?? '';
$filtroPrezzo    = $_GET['prezzo']    ?? '';
$testoCercato    = $_GET['ricerca']   ?? '';
try {

    //seleziono tutti gli articoli e dati venditori
    $sql = "SELECT a.*, u.nome, u.cognome, u.email 
            FROM ArticoloInVendita a
            JOIN Utente u ON a.fkUtenteId = u.idUtente
            WHERE a.disponibilita = TRUE";
    
    $parametri = []; 
 

    // Assemblaggio delle query

    //categoria
    if ($filtroCategoria !== 'tutti') {
        $sql .= " AND a.categoria = :categoria";
        $parametri[':categoria'] = $filtroCategoria;
    }

    //stato
    if ($filtroStato !== '') {
        $sql .= " AND a.stato = :stato";
        $parametri[':stato'] = $filtroStato;
    }

    //barra di ricerca 
    if ($testoCercato !== '') {
        $sql .= " AND (a.titolo LIKE :ricerca OR a.categoria LIKE :ricerca)";

        $parametri[':ricerca'] = '%' . $testoCercato . '%'; 
    }


    // ORDINAMENTO DEL PREZZO
    if ($filtroPrezzo === 'asc') {
        $sql .= " ORDER BY a.prezzo ASC";
    } elseif ($filtroPrezzo === 'desc') {
        $sql .= " ORDER BY a.prezzo DESC";
    } else {
        $sql .= " ORDER BY a.dataPost DESC"; // Default
    }

   
    
    $istruzione = DBHandler::getPDO()->prepare($sql);
    $istruzione->execute($parametri); 
    
    $articoli = $istruzione->fetchAll();

} catch (PDOException $e) {
    die("Errore nel recupero degli articoli: " . $e->getMessage());
}





?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/mainPage.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <title>Vetrina - Chordly</title>
</head>
<body>

    <nav class="navbar">
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>
        
        <div class="nav-search">
            <input type="text" placeholder="Cerca chitarre, bassi, tastiere..." id="searchInput" 
                   value="<?php echo htmlspecialchars($testoCercato); ?>">
        </div>
        
        <div class="nav-actions">
            <a href="followingPage.php" class="nav-link">Seguiti</a>
            <a href="profilePage.php" class="nav-link">Profilo</a>
            <a href="../include/logout.php" class="nav-link">Esci</a>
            <a href="addArticle.php" class="btn-sell">+ Vendi</a>
        </div>
    </nav>

    <div class="filters-bar">
        <button class="chip" onclick="filterCategory(this, 'tutti')">Tutto</button>
        <button class="chip" onclick="filterCategory(this, 'chitarre')">Chitarre</button>
        <button class="chip" onclick="filterCategory(this, 'bassi')">Bassi</button>
        <button class="chip" onclick="filterCategory(this, 'batterie')">Batterie</button>
        <button class="chip" onclick="filterCategory(this, 'tastiere')">Tastiere</button>
        <button class="chip" onclick="filterCategory(this, 'accessori')">Accessori</button>
        <button class="chip" onclick="filterCategory(this, 'altro')">Altro</button>
        
        <div class="filter-divider"></div>
        
        <select class="chip select-chip" id="statoFilter" onchange="applyFilters()">
            <option value="" <?php if($filtroStato == '') echo 'selected'; ?>>Qualsiasi stato</option>
            <option value="ottimo" <?php if($filtroStato == 'ottimo') echo 'selected'; ?>>Ottimo</option>
            <option value="buono" <?php if($filtroStato == 'buono') echo 'selected'; ?>>Buono</option>
            <option value="difettato" <?php if($filtroStato == 'difettato') echo 'selected'; ?>>Difettato</option>
        </select>
        
        <select class="chip select-chip" id="prezzoFilter" onchange="applyFilters()">
            <option value="" <?php if($filtroPrezzo == '') echo 'selected'; ?>>Ordina Prezzo</option>
            <option value="asc" <?php if($filtroPrezzo == 'asc') echo 'selected'; ?>>Prezzo: Crescente (↑)</option>
            <option value="desc" <?php if($filtroPrezzo == 'desc') echo 'selected'; ?>>Prezzo: Decrescente (↓)</option>
        </select>
    </div>

    <main class="main-content">
        <div class="product-grid" id="productGrid">
            <?php if (empty($articoli)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: rgba(255,255,255,0.4);">
                    <p>Nessun articolo trovato con i filtri selezionati.</p>
                </div>
            <?php else: ?>
                <?php foreach ($articoli as $articolo): ?>
                    <div class="product-card" onclick="openArticle(<?php echo $articolo['idArticolo']; ?>)">
                        <div class="card-image-wrapper">
                            <?php if ($articolo['immagine']): ?>
                                <img src="../../uploads/articoli/<?php echo htmlspecialchars($articolo['immagine']); ?>"
                                     alt="<?php echo htmlspecialchars($articolo['titolo']); ?>"
                                     class="card-image">
                            <?php else: ?>
                                <div class="card-image placeholder-img"></div>
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
            <?php endif; ?>
        </div>
    </main>






    
 
    <script>
    // Capire quale categoria è attiva guardando l'indirizzo (URL)
    const parametriIndirizzo = new URLSearchParams(window.location.search);
    const categoriaAttiva = parametriIndirizzo.get('categoria') || 'tutti';

    // Quando la pagina è pronta, colora il bottone della categoria selezionata
    document.addEventListener('DOMContentLoaded', function() {
        // Prendi tutti i bottoni delle categorie
        const bottoni = document.querySelectorAll('.chip:not(.select-chip)');
        
        bottoni.forEach(function(bottone) {
            const testo = bottone.textContent.toLowerCase();
            
            // Se il testo del bottone coincide con la categoria nell'URL, coloralo
            if (testo === categoriaAttiva || (categoriaAttiva === 'tutti' && testo === 'tutto')) {
                bottone.classList.add('active');
            } else {
                bottone.classList.remove('active');
            }
        });
    });

    // Funzione che raccoglie tutti i filtri e ricarica la pagina
    function applyFilters() {
        // Prendi i valori attuali dai menu a tendina e dalla barra di ricerca
        const stato = document.getElementById('statoFilter').value;
        const prezzo = document.getElementById('prezzoFilter').value;
        const ricerca = document.getElementById('searchInput').value;
        
        // Costruisci l'indirizzo con tutti i pezzi
        // Usiamo categoriaAttiva (salvata all'inizio) per non perderla
        let nuovoIndirizzo = "mainPage.php?categoria=" + categoriaAttiva;
        nuovoIndirizzo += "&stato=" + stato;
        nuovoIndirizzo += "&prezzo=" + prezzo;
        nuovoIndirizzo += "&ricerca=" + encodeURIComponent(ricerca);

        // Vai all'indirizzo creato
        window.location.href = nuovoIndirizzo;
    }

    // Quando clicchi su una categoria, cambia la variabile e aggiorna tutto
    function filterCategory(elemento, nomeCategoria) {
        // Questa funzione viene chiamata dai bottoni nell'HTML
        window.location.href = "mainPage.php?categoria=" + nomeCategoria;
    }

    // Se scrivi nella ricerca e premi Invio, attiva i filtri
    document.getElementById('searchInput').addEventListener('change', applyFilters);

    // Funzione per aprire la pagina del dettaglio di un articolo
function openArticle(idArticolo) {
    window.location.href = 'articleDetail.php?id=' + idArticolo;
}

</script>
    

</body>
</html>