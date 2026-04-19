-- Active: 1761637034800@@127.0.0.1@3306@chordlydatabase
CREATE DATABASE IF NOT EXISTS ChordlyDatabase;

USE ChordlyDatabase;

-- 1. Tabella Utente (Nessun cambiamento necessario qui)
CREATE TABLE Utente (
    idUtente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    riservatezza ENUM('privato', 'pubblico') DEFAULT 'pubblico',
    dataRegistrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. ArticoloInVendita: Rimosso CASCADE su DELETE
-- Usiamo RESTRICT o SET NULL per evitare di perdere l'annuncio se l'utente viene rimosso accidentalmente.
-- Ho anche rimosso il vincolo NOT NULL sulla FK se decidi per SET NULL, ma qui usiamo RESTRICT per sicurezza.
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
        ON DELETE RESTRICT    -- Impedisce di eliminare l'utente finché ha articoli attivi
        ON UPDATE CASCADE     -- Se cambia l'ID utente, l'articolo si aggiorna
);

-- 3. Segue: Manteniamo CASCADE
-- Questa è una tabella di puro collegamento: se l'utente non esiste più, il "legame" deve sparire.
CREATE TABLE Segue (
    idFollower INT NOT NULL,
    idSeguito INT NOT NULL,
    PRIMARY KEY (idFollower, idSeguito),
    FOREIGN KEY (idFollower) REFERENCES Utente(idUtente) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (idSeguito) REFERENCES Utente(idUtente) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 4. Commenti: Modificato CASCADE
-- Se eliminiamo un articolo, i commenti spariscono (CASCADE).
-- Ma se eliminiamo l'utente, meglio tenere il commento come "Utente Eliminato" (SET NULL).
CREATE TABLE Commenti (
    idCommento INT AUTO_INCREMENT PRIMARY KEY,
    idUtente INT, -- Rimosso NOT NULL per permettere SET NULL
    idArticolo INT NOT NULL, 
    commento TEXT NOT NULL,
    dataCommento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idUtente) REFERENCES Utente(idUtente) 
        ON DELETE SET NULL    -- Il commento resta, l'autore diventa NULL
        ON UPDATE CASCADE,
    FOREIGN KEY (idArticolo) REFERENCES ArticoloInVendita(idArticolo) 
        ON DELETE CASCADE     -- Se sparisce il post, sparisce il commento
        ON UPDATE CASCADE
);

-- 5. Merita (Valutazioni): Modificato CASCADE
CREATE TABLE Merita (
    fkUtenteId INT NOT NULL,  -- Deve essere NOT NULL perché è in PK nel diagramma
    fkArticoloId INT NOT NULL, 
    valutazione TINYINT NOT NULL CHECK (valutazione BETWEEN 1 AND 5),
    recensione TEXT,
    dataRecensione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (fkUtenteId, fkArticoloId), 
    FOREIGN KEY (fkUtenteId) REFERENCES Utente(idUtente) 
        ON DELETE CASCADE    -- Cambiato da SET NULL a CASCADE per compatibilità PK
        ON UPDATE CASCADE,
    FOREIGN KEY (fkArticoloId) REFERENCES ArticoloInVendita(idArticolo) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
);