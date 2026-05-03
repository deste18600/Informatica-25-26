<?php
// 1. Richiamiamo il nostro "controllore" centrale (gestisce sessione e database)
require_once('../include/menuchoice.php');

// Salviamo l'ID dell'utente loggato in una variabile per comodità (anche se qui non lo usiamo direttamente per filtrare)
$idUtenteLoggato = $_SESSION['userId'];
try {
    // 2. Prepariamo la richiesta al database per prendere TUTTI gli articoli in vendita.
    // Usiamo "JOIN Utente" per unire i dati dell'articolo con i dati di chi lo ha pubblicato.
    // WHERE a.disponibilita = TRUE -> Prendiamo solo gli articoli ancora disponibili
    // ORDER BY a.dataPost DESC     -> Ordiniamo dal più recente al più vecchio
    $sql = "SELECT 
                a.idArticolo,
                a.titolo,
                a.descrizione,
                a.prezzo,
                a.stato,
                a.categoria,
                a.immagine,
                a.disponibilita,
                u.nome,
                u.cognome,
                u.email
            FROM ArticoloInVendita a
            JOIN Utente u ON a.fkUtenteId = u.idUtente
            WHERE a.disponibilita = TRUE 
            ORDER BY a.dataPost DESC";

    $istruzione = DBHandler::getPDO()->prepare($sql);
    $istruzione->execute();
    
    // fetchAll() prende TUTTI i risultati trovati e li salva in una lista chiamata $articoli
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

    <!-- BARRA DI NAVIGAZIONE IN ALTO -->
    <nav class="navbar">
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>
        
        <!-- Barra di ricerca -->
        <div class="nav-search">
            <input type="text" placeholder="Cerca chitarre, bassi, tastiere..." id="searchInput">
        </div>
        
        <!-- Menu utente -->
        <div class="nav-actions">
            <a href="followingPage.php" class="nav-link">Seguiti</a>
            <a href="profilePage.php" class="nav-link">Profilo</a>
            <a href="../include/logout.php" class="nav-link">Esci</a>
            <!-- Pulsante in evidenza per vendere -->
            <a href="addArticle.php" class="btn-sell">+ Vendi</a>
        </div>
    </nav>

    <!-- BARRA DEI FILTRI (Categorie, Stato, Prezzo) -->
    <div class="filters-bar">
        <!-- Quando clicchiamo, attiviamo la funzione JavaScript filterCategory() -->
        <button class="chip active" onclick="filterCategory(this, 'tutti')">Tutto</button>
        <button class="chip" onclick="filterCategory(this, 'chitarre')">Chitarre</button>
        <button class="chip" onclick="filterCategory(this, 'bassi')">Bassi</button>
        <button class="chip" onclick="filterCategory(this, 'batterie')">Batterie</button>
        <button class="chip" onclick="filterCategory(this, 'tastiere')">Tastiere</button>
        <button class="chip" onclick="filterCategory(this, 'accessori')">Accessori</button>
        <button class="chip" onclick="filterCategory(this, 'altro')">Altro</button>
        
        <div class="filter-divider"></div>
        
        <!-- Filtro a tendina per lo STATO (usiamo onchange per attivare il JS quando scegliamo un'opzione) -->
        <select class="chip select-chip" id="statoFilter" onchange="applyFilters()">
            <option value="">Qualsiasi stato</option>
            <option value="ottimo">Ottimo</option>
            <option value="buono">Buono</option>
            <option value="difettato">Difettato</option>
        </select>
        
        <!-- Filtro a tendina per l'ORDINAMENTO DEL PREZZO -->
        <select class="chip select-chip" id="prezzoFilter" onchange="applyFilters()">
            <option value="">Ordina Prezzo</option>
            <option value="asc">Prezzo: Crescente (↑)</option>
            <option value="desc">Prezzo: Decrescente (↓)</option>
        </select>
    </div>

    <!-- CONTENITORE PRINCIPALE DEGLI ARTICOLI -->
    <main class="main-content">
        <!-- Questo contatore verrà aggiornato dal JavaScript se filtriamo gli elementi -->
        <div class="results-label" id="resultsLabel"><?php echo count($articoli); ?> articoli disponibili</div>

        <div class="product-grid" id="productGrid">
            
            <?php if (empty($articoli)): ?>
                <!-- Se il database non ha trovato nulla in vendita -->
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; color: rgba(255,255,255,0.4);">
                    <p>Nessun articolo disponibile al momento. Sii il primo a vendere qualcosa!</p>
                </div>
            <?php else: ?>
                <!-- Se ci sono articoli, facciamo un "giro" (foreach) per stamparli tutti -->
                <?php foreach ($articoli as $articolo): ?>
                    
                    <!-- 
                      ATTENZIONE QUI: usiamo gli attributi 'data-' (es. data-category). 
                      Questi sono "dati nascosti" che il nostro JavaScript leggerà per fare i filtri veloci!
                    -->
                    <div class="product-card"
                         data-category="<?php echo htmlspecialchars($articolo['categoria']); ?>"
                         data-stato="<?php echo htmlspecialchars($articolo['stato']); ?>"
                         data-prezzo="<?php echo $articolo['prezzo']; ?>"
                         onclick="openArticle(<?php echo $articolo['idArticolo']; ?>)">

                        <div class="card-image-wrapper">
                            <!-- Controlliamo se c'è un'immagine caricata -->
                            <?php if ($articolo['immagine']): ?>
                                <img src="../../uploads/articoli/<?php echo htmlspecialchars($articolo['immagine']); ?>"
                                     alt="<?php echo htmlspecialchars($articolo['titolo']); ?>"
                                     class="card-image">
                            <?php else: ?>
                                <!-- Se non c'è immagine, mostriamo un'icona sostitutiva -->
                                <div class="card-image placeholder-img" style="font-size: 48px;">🎸</div>
                            <?php endif; ?>
                            
                            <!-- Etichetta con lo stato (ottimo, buono, ecc.) -->
                            <span class="stato-badge <?php echo htmlspecialchars($articolo['stato']); ?>">
                                <?php echo htmlspecialchars($articolo['stato']); ?>
                            </span>
                        </div>

                        <!-- Info scritte sotto l'immagine -->
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

    <!-- LOGICA JAVASCRIPT PER I FILTRI -->
    <script>
        // Variabili che ricordano le scelte attuali dell'utente
        let filtroCategoriaSelezionata = 'tutti';
        
        // Quando si clicca un bottone di una categoria (es. "Chitarre")
        function filterCategory(bottoneCliccato, categoriaScelta) {
            // 1. Togliamo il colore acceso (la classe 'active') a tutti i bottoni categoria
            document.querySelectorAll('.chip:not(.select-chip)').forEach(bottone => {
                bottone.classList.remove('active');
            });
            // 2. Accendiamo solo il bottone che abbiamo appena cliccato
            bottoneCliccato.classList.add('active');
            
            // 3. Memorizziamo la scelta e applichiamo tutti i filtri insieme
            filtroCategoriaSelezionata = categoriaScelta;
            applyFilters();
        }

        // QUESTA È LA FUNZIONE PRINCIPALE: decide cosa mostrare e cosa nascondere
        function applyFilters() {
            // Leggiamo cosa c'è scritto nei menu a tendina e nella barra di ricerca in questo esatto momento
            const filtroStatoSelezionato = document.getElementById('statoFilter').value;
            const filtroPrezzoSelezionato = document.getElementById('prezzoFilter').value;
            // Trasformiamo il testo cercato in minuscolo per facilitare il confronto
            const testoCercato = document.getElementById('searchInput').value.toLowerCase();

            // Prendiamo tutte le "card" (i riquadri degli articoli) presenti sulla pagina
            const tutteLeCard = document.querySelectorAll('.product-card');
            let contatoreVisibili = 0;

            // Controlliamo ogni singola card, una per una
            tutteLeCard.forEach(card => {
                // Leggiamo i "dati nascosti" che avevamo preparato nel PHP (dataset legge i data-*)
                const categoriaCard = card.dataset.category;
                const statoCard     = card.dataset.stato;
                // Leggiamo il testo del titolo e della categoria per la ricerca testuale
                const titoloCard    = card.querySelector('.card-title').textContent.toLowerCase();
                const etichettaCategoriaCard = card.querySelector('.card-category').textContent.toLowerCase();

                // 3 Controlli: per ogni card, verifichiamo se "passa l'esame" dei filtri
                // Passa l'esame della categoria? (Sì se cerchiamo "tutti" o se la categoria combacia)
                const okCategoria = (filtroCategoriaSelezionata === 'tutti') || (categoriaCard === filtroCategoriaSelezionata);
                // Passa l'esame dello stato?
                const okStato     = (filtroStatoSelezionato === '') || (statoCard === filtroStatoSelezionato);
                // Passa l'esame del testo cercato?
                const okRicerca   = (testoCercato === '') || titoloCard.includes(testoCercato) || etichettaCategoriaCard.includes(testoCercato);

                // Se la card supera TUTTI E TRE gli esami...
                if (okCategoria && okStato && okRicerca) {
                    card.style.display = 'block'; // Mostrala!
                    contatoreVisibili++;          // Aumentiamo il contatore
                } else {
                    card.style.display = 'none';  // Nascondila!
                }
            });

            // Aggiorniamo il numero di articoli mostrati in alto
            document.getElementById('resultsLabel').textContent = contatoreVisibili + ' articoli trovati';

            // Infine, se l'utente ha scelto un ordinamento (es. Prezzo Crescente), ordiniamo le card visibili
            if (filtroPrezzoSelezionato !== '') {
                ordinaPerPrezzo(filtroPrezzoSelezionato);
            }
        }

        // Funzione per ordinare i prezzi
        function ordinaPerPrezzo(ordine) {
            const griglia = document.getElementById('productGrid');
            // Trasformiamo l'elenco delle card in un vero Array per poterlo ordinare
            const arrayCard = Array.from(document.querySelectorAll('.product-card'));
            
            // Prendiamo solo le card che NON sono nascoste
            const cardVisibili = arrayCard.filter(card => card.style.display !== 'none');

            // Ordiniamo le card in base al loro data-prezzo
            cardVisibili.sort((a, b) => {
                const prezzoA = parseFloat(a.dataset.prezzo);
                const prezzoB = parseFloat(b.dataset.prezzo);
                // Se 'asc' facciamo A - B (crescente), altrimenti B - A (decrescente)
                return ordine === 'asc' ? prezzoA - prezzoB : prezzoB - prezzoA;
            });

            // "Riattacchiamo" le card nella griglia nel nuovo ordine
            arrayCard.forEach(card => {
                if (card.style.display !== 'none') {
                    // Se era visibile, prendiamo la prima della nostra lista ordinata
                    griglia.appendChild(cardVisibili.shift());
                } else {
                    // Se era nascosta, la rimettiamo in fondo così com'è
                    griglia.appendChild(card);
                }
            });
        }

        // Diciamo alla barra di ricerca di attivare i filtri ogni volta che l'utente solleva un tasto della tastiera ('keyup')
        document.getElementById('searchInput').addEventListener('keyup', applyFilters);

        // Funzione per aprire il dettaglio dell'articolo
        function openArticle(idArticolo) {
            window.location.href = 'articleDetail.php?id=' + idArticolo;
        }
    </script>

</body>
</html>