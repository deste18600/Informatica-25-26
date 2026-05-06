<?php
require_once('../include/menuchoice.php');
 
$idUtenteLoggato = $_SESSION['userId'];

// LETTURA DEI FILTRI DALL'URL (Se non ci sono, uso valori predefiniti)
$filtroCategoria = isset($_GET['categoria']) ? $_GET['categoria'] : 'tutti';
$filtroStato     = isset($_GET['stato']) ? $_GET['stato'] : '';
$filtroPrezzo    = isset($_GET['prezzo']) ? $_GET['prezzo'] : '';
$testoCercato    = isset($_GET['ricerca']) ? $_GET['ricerca'] : '';

try {

    // Inizia a scrivere la query SQL base per selezionare gli articoli e i dati del venditore (JOIN)
    $sql = "SELECT a.*, u.nome, u.cognome, u.email 
            FROM ArticoloInVendita a
            JOIN Utente u ON a.fkUtenteId = u.idUtente
            WHERE a.disponibilita = TRUE";
    
    $parametri = []; 
    // Array per i prepared statements

    // ASSEMBLAGGIO DINAMICO DELLA QUERY

    if ($filtroCategoria !== 'tutti') {
        // .= unisce la mia stringa letta in $filtroCategoria alla query in modo da filtrare per quella specifica categoria

        $sql .= " AND a.categoria = :categoria";

        // Aggiungo il parametro per il prepared statement (il valore di $filtroCategoria) 
        $parametri[':categoria'] = $filtroCategoria;
    }

    // prima controllo se esite uno stato e non è più vuoto
    if ($filtroStato !== '') {
        //come sopra concateno la stringa alla query
        $sql .= " AND a.stato = :stato";
        //aggiungo il parametro per il prepared statement
        $parametri[':stato'] = $filtroStato;
    }

    // Se l'utente ha scritto qualcosa nella barra ($testoCercato)
    if ($testoCercato !== '') {

        $sql .= " AND (a.titolo LIKE :ricerca OR a.categoria LIKE :ricerca)";

        //prepared statement, mettendo % prima e dopo il testo cercato per far capire al database che può esserci qualsiasi cosa prima o dopo
        // % significa qualsiasi sequenza di caratteri e il . un +
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

   
    //variabile $sql che abbiamo costruito dinamicamente e l'array $parametri per eseguire la query in modo sicuro con i prepared statements
    $istruzione = DBHandler::getPDO()->prepare($sql);
    $istruzione->execute($parametri); 
    
    // Pesca i risultati già filtrati alla perfezione dal database
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
        
        <!-- Barra di ricerca: stampa del testo che l'utente ha appena cercato -->
        <div class="nav-search">
            <input type="text" placeholder="Cerca chitarre, bassi, tastiere..." id="searchInput" 
                   value="<?php echo htmlspecialchars($testoCercato); ?>">
        </div>
        
        <!-- Menu utente -->
        <div class="nav-actions">
            <a href="followingPage.php" class="nav-link">Seguiti</a>
            <a href="profilePage.php" class="nav-link">Profilo</a>
            <a href="../include/logout.php" class="nav-link">Esci</a>
            <a href="addArticle.php" class="btn-sell">+ Vendi</a>
        </div>
    </nav>

    <!-- BARRA DEI FILTRI (Categorie, Stato, Prezzo) -->
    <div class="filters-bar">

        <button class="chip" onclick="filterCategory(this, 'tutti')">Tutto</button>
        <button class="chip" onclick="filterCategory(this, 'chitarre')">Chitarre</button>
        <button class="chip" onclick="filterCategory(this, 'bassi')">Bassi</button>
        <button class="chip" onclick="filterCategory(this, 'batterie')">Batterie</button>
        <button class="chip" onclick="filterCategory(this, 'tastiere')">Tastiere</button>
        <button class="chip" onclick="filterCategory(this, 'accessori')">Accessori</button>
        <button class="chip" onclick="filterCategory(this, 'altro')">Altro</button>
        
        <div class="filter-divider"></div>
        
        <!-- Filtro a tendina per lo stato -->
        <select class="chip select-chip" id="statoFilter" onchange="applyFilters()">
            <option value="" <?php if($filtroStato == '') echo 'selected'; ?>>Qualsiasi stato</option>
            <option value="ottimo" <?php if($filtroStato == 'ottimo') echo 'selected'; ?>>Ottimo</option>
            <option value="buono" <?php if($filtroStato == 'buono') echo 'selected'; ?>>Buono</option>
            <option value="difettato" <?php if($filtroStato == 'difettato') echo 'selected'; ?>>Difettato</option>
        </select>
        
        <!-- Filtro a tendina per ordinamento prezzo -->
        <select class="chip select-chip" id="prezzoFilter" onchange="applyFilters()">
            <option value="" <?php if($filtroPrezzo == '') echo 'selected'; ?>>Ordina Prezzo</option>
            <option value="asc" <?php if($filtroPrezzo == 'asc') echo 'selected'; ?>>Prezzo: Crescente (↑)</option>
            <option value="desc" <?php if($filtroPrezzo == 'desc') echo 'selected'; ?>>Prezzo: Decrescente (↓)</option>
        </select>
    </div>

    <!-- CONTENITORE PRINCIPALE DEGLI ARTICOLI -->
    <main class="main-content">

        <div class="product-grid" id="productGrid">
            
            <?php if (empty($articoli)): ?>

                <!-- Se il database non ha trovato nulla con questi filtri -->
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; color: rgba(255,255,255,0.4);">
                    <p>Nessun articolo trovato con i filtri selezionati. </p>
                </div>

            <?php else: ?>

                <!-- Se ci sono articoli, li stampiamo in griglia -->

                <?php foreach ($articoli as $articolo): ?>
                    
                    <div class="product-card" onclick="openArticle(<?php echo $articolo['idArticolo']; ?>)">

                        <div class="card-image-wrapper">
                            <?php if ($articolo['immagine']): ?>
                                <img src="../../uploads/articoli/<?php echo htmlspecialchars($articolo['immagine']); ?>"
                                     alt="<?php echo htmlspecialchars($articolo['titolo']); ?>"
                                     class="card-image">
                            <?php else: ?>
                                <div class="card-image placeholder-img" style="font-size: 48px;"></div>
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







    <!-- JAVASCRIPT  -->
    <script>

        // Leggiamo dall'indirizzo web cosa ha scelto l'utente
        const urlParams = new URLSearchParams(window.location.search);
        let filtroCategoriaSelezionata = urlParams.get('categoria') || 'tutti';
        
        // Appena la pagina finisce di caricare, coloriamo di giallo (active) il bottone giusto
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.chip:not(.select-chip)').forEach(bottone => {
                const testoBottone = bottone.textContent.toLowerCase();
                if (testoBottone === filtroCategoriaSelezionata || (filtroCategoriaSelezionata === 'tutti' && testoBottone === 'tutto')) {
                    bottone.classList.add('active');
                } else {
                    bottone.classList.remove('active');
                }
            });
        });

        // Quando clicchi su un bottone delle categorie...
        function filterCategory(bottoneCliccato, categoriaScelta) {
            filtroCategoriaSelezionata = categoriaScelta;
            applyFilters();
        }

        // QUESTA È LA FUNZIONE CHE RICARICA LA PAGINA INVIANDO I FILTRI AL PHP
        function applyFilters() {
            const stato = document.getElementById('statoFilter').value;
            const prezzo = document.getElementById('prezzoFilter').value;
            // encodeURIComponent "impacchetta" le parole per metterle sicure nell'URL (es. gestisce gli spazi)
            const ricerca = encodeURIComponent(document.getElementById('searchInput').value);
            
            // Costruiamo il nuovo URL della pagina
            const nuovoUrl = `mainPage.php?categoria=${filtroCategoriaSelezionata}&stato=${stato}&prezzo=${prezzo}&ricerca=${ricerca}`;
            
            // Facciamo fisicamente il salto verso il nuovo URL
            window.location.href = nuovoUrl;
        }

        // Se scrivi nella barra di ricerca, il filtro parte quando premi Invio (o clicchi fuori)
        document.getElementById('searchInput').addEventListener('change', applyFilters);

        // Apre il dettaglio dell'articolo
        function openArticle(idArticolo) {
            window.location.href = 'articleDetail.php?id=' + idArticolo;
        }
    </script>



</body>
</html>