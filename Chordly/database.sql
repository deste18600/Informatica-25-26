-- Creazione del database (se non esiste già)
CREATE DATABASE IF NOT EXISTS ChordlyDatabase;
USE ChordlyDatabase;

-- 1. TABELLA UTENTE
CREATE TABLE Utente (
    idUtente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    riservatezza ENUM('privato', 'pubblico') DEFAULT 'pubblico',
    dataRegistrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. TABELLA ARTICOLI IN VENDITA
CREATE TABLE ArticoloInVendita (
    idArticolo INT AUTO_INCREMENT PRIMARY KEY,
    fkUtenteId INT NOT NULL, 
    titolo VARCHAR(100) NOT NULL,
    descrizione TEXT,
    prezzo DECIMAL(10, 2) NOT NULL,
    stato ENUM('ottimo', 'buono', 'difettato') NOT NULL,
    disponibilita BOOLEAN DEFAULT TRUE,
    categoria ENUM('chitarre', 'bassi', 'batterie', 'tastiere', 'accessori', 'altro') NOT NULL,
    immagine VARCHAR(255), 
    dataPost DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fkUtenteId) REFERENCES Utente(idUtente)
        ON DELETE CASCADE    
        ON UPDATE CASCADE     
);

-- 3. TABELLA SEGUITI (Follower)
CREATE TABLE Segue (
    idFollower INT NOT NULL,
    idSeguito INT NOT NULL,
    PRIMARY KEY (idFollower, idSeguito),
    FOREIGN KEY (idFollower) REFERENCES Utente(idUtente) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (idSeguito) REFERENCES Utente(idUtente) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 4. TABELLA COMMENTI (Che avevi già impostato benissimo)
CREATE TABLE Commenti (
    idCommento INT AUTO_INCREMENT PRIMARY KEY,
    idUtente INT, 
    idArticolo INT NOT NULL, 
    commento TEXT NOT NULL,
    dataCommento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idUtente) REFERENCES Utente(idUtente) 
        ON DELETE SET NULL    
        ON UPDATE CASCADE,
    FOREIGN KEY (idArticolo) REFERENCES ArticoloInVendita(idArticolo) 
        ON DELETE CASCADE     
        ON UPDATE CASCADE
);

-- 5. TABELLA ACQUISTI (Nuova, serve per buyArticle.php e per il Profilo)
CREATE TABLE Acquisti (
    idAcquisto INT AUTO_INCREMENT PRIMARY KEY,
    fkAcquirenteId INT NOT NULL,
    fkArticoloId INT NOT NULL,
    dataAcquisto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fkAcquirenteId) REFERENCES Utente(idUtente) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (fkArticoloId) REFERENCES ArticoloInVendita(idArticolo) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 6. TABELLA RECENSIONI (Sostituisce "Merita", allineata ad addReview.php)
CREATE TABLE RecensioneUtente (
    idRecensione INT AUTO_INCREMENT PRIMARY KEY,
    fkRecensoreId INT NOT NULL,  
    fkRecensitoId INT NOT NULL,  
    valutazione TINYINT NOT NULL CHECK (valutazione BETWEEN 1 AND 5),
    commento TEXT,
    dataRecensione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unica_recensione (fkRecensoreId, fkRecensitoId), -- Permette 1 sola recensione per coppia di utenti
    FOREIGN KEY (fkRecensoreId) REFERENCES Utente(idUtente) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    FOREIGN KEY (fkRecensitoId) REFERENCES Utente(idUtente) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
);