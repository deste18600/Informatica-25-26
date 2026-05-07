
**Studente:** Simone D’Este  
**Classe:** 5IE  


# 1. Introduzione al Progetto

Chordly è una piattaforma web dedicata alla compravendita di strumenti musicali usati tra utenti registrati.  

L’applicazione permette agli utenti di:

- registrarsi e autenticarsi;
- pubblicare annunci;
- acquistare strumenti;
- seguire altri utenti;
- lasciare recensioni;
- gestire il proprio profilo personale.

L’intero progetto è stato sviluppato utilizzando:

- PHP
- MySQL
- JavaScript
- HTML5
- CSS
- Apache/XAMPP
- Docker

---

# 2. Architettura Generale del Sistema

L’architettura dell’applicazione segue il modello client-server:

- il frontend gestisce l’interfaccia grafica e l’interazione utente;
- il backend PHP elabora le richieste;
- il database MySQL memorizza tutte le informazioni.

La struttura del progetto è suddivisa in cartelle specializzate:

---

## 3 Tabelle Principali del database

Le tabelle principali del sistema sono:

| Tabella | Funzione |
|---|---|
| `Utente` |
| `ArticoloInVendita` |
| `Segue` |  |
| `Commenti` | 
| `Acquisti` | 
| `RecensioneUtente` |


# 4. Backend

Il backend è stato sviluppato in PHP con una logica centralizzata per:

- autenticazione;
- permessi;
- caricamento dinamico;
- connessione database.

---

# 5. Gestione Permessi tramite JSON

Per evitare codice duplicato, i permessi sono stati centralizzati nel file `pages.json`.

## Esempio

```json
{
    "richiedeLogin": [
        "addArticle.php",
        "profilePage.php",
        "buyArticle.php"
    ],

    "richiedeDatabase": [
        "mainPage.php",
        "login.php",
        "signIn.php"
    ]
}
```

---

In modo da:

- modificare facilmente i permessi;
- evitare controlli ripetuti in ogni pagina;

---

# 6. Controllo Sessione Utente

Le pagine protette verificano la presenza della sessione utente.

## Codice

```php
if (!isset($_SESSION['userId'])) {
    header('Location: /CHORDLY/php/userpages/userLoginpage.php');
    exit;
}
```

---

# 7. Gestione Database con PDO

Per l’accesso al database è stato utilizzato PDO perchè garantisce.

- prepared statements;
- protezione SQL injection;
- gestione eccezioni;
- compatibilità multipla DBMS.

---

# 8. Transazioni 

La pagina `buyArticle.php` utilizza transazioni  per garantire coerenza durante gli acquisti.

## Codice

```php
$pdo->beginTransaction();


$sqlA = "
UPDATE ArticoloInVendita
SET disponibilita = FALSE
WHERE idArticolo = :articleId
";

$sqlB = "
INSERT INTO Acquisti
(fkAcquirenteId, fkArticoloId)
VALUES
(:buyerId, :articleId)
";

$pdo->commit();
```

---
Utilizzo le transazioni anche senza effettivo denaro per assicurasi che :

- nessuna operazione venga salvata parzialmente;
- il database resti consistente.

In caso di errore:

```php
$pdo->rollBack();
```

ripristina lo stato precedente.

---

# 9. Gestione Upload Immagini

utilizzo un nome file univoco per evitare errori di caricamenti

## Codice

```php
$nomeFileUnico = uniqid('art_') . '.' . $estensioneFile;
```

sono consentiti formati di tipo JPG PNG e WEBP con un limite di 15 MB

- JPG
- PNG
- WEBP

---

# 10. Frontend

Il frontend è stato progettato per risultare:


# 11. Comunicazione Asincrona con Fetch 

Per evitare il refresh completo delle pagine è stata utilizzata la Fetch .

## Esempio

```javascript
fetch('followUser.php', {
    method: 'POST',

    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },

    body:
        'idSeguito=' + idVenditore +
        '&action=' +
        (staGiaSeguendo ? 'unfollow' : 'follow')
})
```

---


# 12. Anteprima Immagini Client-Side

Per migliorare il caricamento annunci è stato utilizzato `FileReader`.

## Codice

```javascript
const reader = new FileReader();

reader.onload = e => {
    img.src = e.target.result;
    img.style.display = 'block';
};

reader.readAsDataURL(file);
```

---


In questo modo l’utente può:

- vedere subito l’immagine;
- verificare il file scelto;
- evitare errori prima dell’upload.

---
