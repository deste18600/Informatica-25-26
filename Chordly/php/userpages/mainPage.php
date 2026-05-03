<?php
require_once('../include/menuchoice.php');

$userId = $_SESSION['userId'];

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

    $sth = DBHandler::getPDO()->prepare($sql);
    $sth->execute();
    $articoli = $sth->fetchAll();
} catch (PDOException $e) {
    die("Errore nel recupero articoli: " . $e->getMessage());
}
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
            <input type="text" placeholder="Cerca chitarre, bassi, tastiere..." id="searchInput">
        </div>
        <div class="nav-actions">
            <a href="followingPage.php" class="nav-link">Seguiti</a>
            <a href="profilePage.php" class="nav-link">Profilo</a>
            <a href="../include/logout.php" class="nav-link">Esci</a>
            <a href="addArticle.php" class="btn-sell">+ Vendi</a>
        </div>
    </nav>

    <div class="filters-bar">
        <button class="chip active" onclick="filterCategory(this, 'tutti')">Tutto</button>
        <button class="chip" onclick="filterCategory(this, 'chitarre')">Chitarre</button>
        <button class="chip" onclick="filterCategory(this, 'bassi')">Bassi</button>
        <button class="chip" onclick="filterCategory(this, 'batterie')">Batterie</button>
        <button class="chip" onclick="filterCategory(this, 'tastiere')">Tastiere</button>
        <button class="chip" onclick="filterCategory(this, 'accessori')">Accessori</button>
        <button class="chip" onclick="filterCategory(this, 'altro')">Altro</button>
        <div class="filter-divider"></div>
        <select class="chip select-chip" id="statoFilter" onchange="applyFilters()">
            <option value="">Stato</option>
            <option value="ottimo">Ottimo</option>
            <option value="buono">Buono</option>
            <option value="difettato">Difettato</option>
        </select>
        <select class="chip select-chip" id="prezzoFilter" onchange="applyFilters()">
            <option value="">Prezzo</option>
            <option value="asc">Prezzo ↑</option>
            <option value="desc">Prezzo ↓</option>
        </select>
    </div>

    <main class="main-content">
        <div class="results-label" id="resultsLabel"><?php echo count($articoli); ?> articoli disponibili</div>

        <div class="product-grid" id="productGrid">
            <?php if (empty($articoli)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; color: rgba(255,255,255,0.4);">
                    <p>Nessun articolo disponibile al momento</p>
                </div>
            <?php else: ?>
                <?php foreach ($articoli as $articolo): ?>
                    <div class="product-card"
                         data-category="<?php echo htmlspecialchars($articolo['categoria']); ?>"
                         data-stato="<?php echo htmlspecialchars($articolo['stato']); ?>"
                         data-prezzo="<?php echo $articolo['prezzo']; ?>"
                         onclick="openArticle(<?php echo $articolo['idArticolo']; ?>)">

                        <div class="card-image-wrapper">
                            <?php if ($articolo['immagine']): ?>
                                <img src="../../uploads/articoli/<?php echo htmlspecialchars($articolo['immagine']); ?>"
                                     alt="<?php echo htmlspecialchars($articolo['titolo']); ?>"
                                     class="card-image">
                            <?php else: ?>
                                <div class="card-image placeholder-img" style="font-size: 48px;">🎸</div>
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
        let filtroCategoria = 'tutti';
        let filtroStato     = '';
        let filtroPrezzo    = '';

        function filterCategory(btn, category) {
            document.querySelectorAll('.chip:not(.select-chip)').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            filtroCategoria = category;
            applyFilters();
        }

        // Unica funzione che gestisce ricerca + categoria + stato + prezzo insieme
        function applyFilters() {
            filtroStato  = document.getElementById('statoFilter').value;
            filtroPrezzo = document.getElementById('prezzoFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            const cards = document.querySelectorAll('.product-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const categoria = card.dataset.category;
                const stato     = card.dataset.stato;
                const titolo    = card.querySelector('.card-title').textContent.toLowerCase();
                const catLabel  = card.querySelector('.card-category').textContent.toLowerCase();

                const okCategoria = filtroCategoria === 'tutti' || categoria === filtroCategoria;
                const okStato     = !filtroStato || stato === filtroStato;
                const okSearch    = !searchTerm || titolo.includes(searchTerm) || catLabel.includes(searchTerm);

                if (okCategoria && okStato && okSearch) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('resultsLabel').textContent = visibleCount + ' articoli';

            if (filtroPrezzo) sortByPrice();
        }

        function sortByPrice() {
            const grid  = document.getElementById('productGrid');
            const cards = Array.from(document.querySelectorAll('.product-card'));
            const visible = cards.filter(c => c.style.display !== 'none');

            visible.sort((a, b) => {
                const pA = parseFloat(a.dataset.prezzo);
                const pB = parseFloat(b.dataset.prezzo);
                return filtroPrezzo === 'asc' ? pA - pB : pB - pA;
            });

            cards.forEach(c => grid.appendChild(c.style.display !== 'none' ? visible.shift() : c));
        }

        // Ricerca collegata alla stessa funzione dei filtri
        document.getElementById('searchInput').addEventListener('keyup', applyFilters);

        function openArticle(id) {
            window.location.href = 'articleDetail.php?id=' + id;
        }
    </script>

</body>
</html>
