<?php
// Salva l'ID dell'utente in una variabile per usarla dopo
$userId = $_SESSION['userId'];
// RECUPERA GLI ARTICOLI DAL DATABASE

try {

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
    
    // Prepara la query 
    $sth = DBHandler::getPDO()->prepare($sql);
    // Esegui la query
    $sth->execute();
    // Prendi tutti i risultati e mettili in un array
    $articoli = $sth->fetchAll();
} catch (PDOException $e) {
    // Se c'è un errore, mostra il messaggio
    die("Errore nel recupero articoli: " . $e->getMessage());
}


//inizio html


?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/mainPage.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <title>Chordly</title>
</head>
<body>


    <nav class="navbar">
       
        <a class="nav-logo" href="mainPage.php">CHORDLY</a>

       
        <div class="nav-search">
        
            <!-- Input per scrivere cosa cercare -->
            <input type="text" placeholder="Cerca chitarre, bassi, tastiere..." id="searchInput">
        </div>

        <!-- MENU DELLA NAVBAR -->
        <div class="nav-actions">
            <a href="followingPage.php" class="nav-link">Seguiti</a>
            <a href="messagesPage.php" class="nav-link">Messaggi</a>
            <a href="profilePage.php" class="nav-link">Profilo</a>
            <a href="/CHORDLY/php/include/logout.php" class="nav-link">Esci</a>
            <!-- Pulsante per vendere articoli -->
            <a href="addArticle.php" class="btn-sell">+ Vendi</a>
        </div>
    </nav>

    <!--  BARRA DI FILTRI  -->
    <div class="filters-bar">
        <!-- Bottoni per filtrare per categoria -->
        <button class="chip active" onclick="filterCategory(this, 'tutti')">Tutto</button>
        <button class="chip" onclick="filterCategory(this, 'chitarre')">Chitarre</button>
        <button class="chip" onclick="filterCategory(this, 'bassi')">Bassi</button>
        <button class="chip" onclick="filterCategory(this, 'batterie')">Batterie</button>
        <button class="chip" onclick="filterCategory(this, 'tastiere')">Tastiere</button>
        <button class="chip" onclick="filterCategory(this, 'accessori')">Accessori</button>
        <button class="chip" onclick="filterCategory(this, 'altro')">Altro</button>

        <!-- Separatore -->
        <div class="filter-divider"></div>

        <!-- Filtro per stato dell'articolo -->
        <select class="chip select-chip" id="statoFilter" onchange="applyFilters()">
            <option value="">Stato</option>
            <option value="ottimo">Ottimo</option>
            <option value="buono">Buono</option>
            <option value="difettato">Difettato</option>
        </select>

        <!-- Filtro per prezzo -->
        <select class="chip select-chip" id="prezzoFilter" onchange="applyFilters()">
            <option value="">Prezzo</option>
            <option value="asc">Prezzo ↑</option>
            <option value="desc">Prezzo ↓</option>
        </select>
    </div>

    <!--  CONTENUTO PRINCIPALE  -->
    <main class="main-content">
        <!-- Numero di articoli disponibili -->
        <div class="results-label" id="resultsLabel"><?php echo count($articoli); ?> articoli disponibili</div>

        <!-- GRIGLIA CON GLI ARTICOLI -->
        <div class="product-grid" id="productGrid">
            
            <!-- Se non ci sono articoli, mostra un messaggio -->
            <?php if (empty($articoli)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; color: rgba(255,255,255,0.4);">
                    <p>Nessun articolo disponibile al momento</p>
                </div>
            
            <!-- Se ci sono articoli, mostrali uno ad uno -->
            <?php else: ?>
                <?php foreach ($articoli as $articolo): 
                ?>
                
                <!-- UNA CARD DEL PRODOTTO -->
                <div class="product-card" 
                     data-category="<?php echo htmlspecialchars($articolo['categoria']); ?>"
                     data-stato="<?php echo htmlspecialchars($articolo['stato']); ?>"
                     data-prezzo="<?php echo $articolo['prezzo']; ?>"
                     onclick="openArticle(<?php echo $articolo['idArticolo']; ?>)">
                    
                    <!-- IMMAGINE DEL PRODOTTO -->
<div class="card-image-wrapper">
    <?php if ($articolo['immagine']): ?>
        <img src="../../uploads/articoli/<?php echo htmlspecialchars($articolo['immagine']); ?>"
             alt="<?php echo htmlspecialchars($articolo['titolo']); ?>"
             class="card-image"
             style="width:100%; height:100%; object-fit:cover;">
    <?php else: ?>
        <div class="card-image placeholder-img" 
             style="background: linear-gradient(135deg, #1a1a1a 0%, #111 100%); display: flex; align-items: center; justify-content: center; font-size: 48px;">
            🎸
        </div>
    <?php endif; ?>

    <span class="stato-badge <?php echo htmlspecialchars($articolo['stato']); ?>">
        <?php echo htmlspecialchars($articolo['stato']); ?>
    </span>
</div>

                    <!-- INFORMAZIONI DEL PRODOTTO -->
                    <div class="card-body">
                        <!-- Prezzo in euro -->
                        <div class="card-price">€ <?php echo number_format($articolo['prezzo'], 2, ',', '.'); ?></div>
                        <!-- Titolo articolo -->
                        <div class="card-title"><?php echo htmlspecialchars($articolo['titolo']); ?></div>
                        <!-- Categoria (Chitarre, Bassi, ecc) -->
                        <div class="card-category"><?php echo htmlspecialchars(ucfirst($articolo['categoria'])); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!--  JAVASCRIPT PER I FILTRI -->
    <script>
        // Variabili che ricordano filtri selezionati
        let filtroAttuale = 'tutti';
        let statoAttuale = '';
        let prezzoAttuale = '';

        // Quando clicchi su un bottone categoria, questa funzione si attiva
        function filterCategory(btn, category) {
            // Tolgi il colore attivo da tutti i bottoni
            document.querySelectorAll('.chip:not(.select-chip)').forEach(c => {
                c.classList.remove('active');
            });
            // Aggiungi il colore attivo solo al bottone cliccato
            btn.classList.add('active');
            // Salva quale categoria hai scelto
            filtroAttuale = category;
            // Applica tutti i filtri
            applyFilters();
        }

        // FUNZIONE: APPLICA TUTTI I FILTRI 
        // Questa funzione mostra/nascondi gli articoli in base ai filtri
        function applyFilters() {
            // Prendi il valore dello stato dal dropdown
            statoAttuale = document.getElementById('statoFilter').value;
            // Prendi il valore del prezzo dal dropdown
            prezzoAttuale = document.getElementById('prezzoFilter').value;

            // Prendi tutte le card degli articoli
            const cards = document.querySelectorAll('.product-card');
            let visibleCount = 0;

            // Per ogni card, controlla se deve essere mostrata o nascosta
            cards.forEach(card => {
                const categoria = card.dataset.category;
                const stato = card.dataset.stato;
                const prezzo = parseFloat(card.dataset.prezzo);

                // Assume che la card debba essere mostrata
                let mostra = true;

                // Se la categoria selezionata non è "tutti" e non corrisponde, nascondi
                if (filtroAttuale !== 'tutti' && categoria !== filtroAttuale) {
                    mostra = false;
                }

                // Se c'è un filtro di stato e non corrisponde, nascondi
                if (statoAttuale && stato !== statoAttuale) {
                    mostra = false;
                }

                // Mostra o nascondi la card
                if (mostra) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Aggiorna il numero di articoli mostrati
            document.getElementById('resultsLabel').textContent = visibleCount + ' articoli';

            // Se è selezionato un ordine per prezzo, ordina
            if (prezzoAttuale) {
                sortByPrice();
            }
        }

        // Questa funzione riordina le card in base al prezzo
        function sortByPrice() {
            const grid = document.getElementById('productGrid');
            // Prendi tutte le card
            const cards = Array.from(document.querySelectorAll('.product-card'));
            // Filtra solo le card visibili
            const visibleCards = cards.filter(c => c.style.display !== 'none');

            // Ordina le card per prezzo
            visibleCards.sort((a, b) => {
                const prezzoA = parseFloat(a.dataset.prezzo);
                const prezzoB = parseFloat(b.dataset.prezzo);
                // Se prezzoAttuale è 'asc', ordina dal prezzo minore al maggiore
                // Altrimenti ordina dal maggiore al minore
                return prezzoAttuale === 'asc' ? prezzoA - prezzoB : prezzoB - prezzoA;
            });

            // Ripulisci la griglia
            grid.innerHTML = '';
            
            // Rimetti le card in ordine nella griglia
            cards.forEach(card => {
                if (card.style.display !== 'none') {
                    grid.appendChild(visibleCards.shift());
                } else {
                    grid.appendChild(card);
                }
            });
        }



        // RICERCA IN TEMPO REALE 
        // Mentre scrivi nella barra di ricerca, filtra gli articoli
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            // Prendi quello che hai scritto e rendilo minuscolo
            const searchTerm = e.target.value.toLowerCase();
            // Prendi tutte le card
            const cards = document.querySelectorAll('.product-card');
            
            // Per ogni card, controlla se corrisponde alla ricerca
            cards.forEach(card => {
                // Prendi il titolo dell'articolo
                const titolo = card.querySelector('.card-title').textContent.toLowerCase();
                // Prendi la categoria
                const categoria = card.querySelector('.card-category').textContent.toLowerCase();
                
                // Se il titolo o la categoria contengono quello che hai cercato, mostra la card
                if (titolo.includes(searchTerm) || categoria.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

            // FUNZIONE: APRI UN ARTICOLO
            function openArticle(idArticolo) {
        window.location.href = 'articleDetail.php?id=' + idArticolo;
}
    </script>

</body>
</html>
