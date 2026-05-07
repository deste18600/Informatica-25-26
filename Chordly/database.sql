-- Active: 1761637034800@@127.0.0.1@3306@chordlydatabase

CREATE DATABASE IF NOT EXISTS ChordlyDatabase;
USE ChordlyDatabase;

CREATE TABLE Utente (
    idUtente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    riservatezza ENUM('privato', 'pubblico') DEFAULT 'pubblico',
    dataRegistrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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

CREATE TABLE Segue (
    idFollower INT NOT NULL,
    idSeguito INT NOT NULL,
    PRIMARY KEY (idFollower, idSeguito),
    FOREIGN KEY (idFollower) REFERENCES Utente(idUtente) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (idSeguito) REFERENCES Utente(idUtente) ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE Acquista (
    idAcquisto INT AUTO_INCREMENT PRIMARY KEY,
    fkAcquirenteId INT NOT NULL,
    fkArticoloId INT NOT NULL,
    dataAcquisto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fkAcquirenteId) REFERENCES Utente(idUtente) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (fkArticoloId) REFERENCES ArticoloInVendita(idArticolo) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE RecensioneUtente (
    idRecensione INT AUTO_INCREMENT PRIMARY KEY,
    fkRecensoreId INT NOT NULL,  
    fkRecensitoId INT NOT NULL,  
    valutazione TINYINT NOT NULL CHECK (valutazione BETWEEN 1 AND 5),
    commento TEXT,
    dataRecensione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unica_recensione (fkRecensoreId, fkRecensitoId),
    FOREIGN KEY (fkRecensoreId) REFERENCES Utente(idUtente) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (fkRecensitoId) REFERENCES Utente(idUtente) ON DELETE CASCADE ON UPDATE CASCADE
);