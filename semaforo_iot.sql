-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 22, 2026 alle 11:57
-- Versione del server: 10.4.25-MariaDB
-- Versione PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `semaforo_iot`
--
CREATE DATABASE IF NOT EXISTS `semaforo_iot` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `semaforo_iot`;

-- --------------------------------------------------------

--
-- Struttura della tabella `dati_sensori`
--

CREATE TABLE IF NOT EXISTS `dati_sensori` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `semaforo_id` bigint(20) NOT NULL,
  `topic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `ricevuto_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_semaforo_time` (`semaforo_id`,`ricevuto_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `ruoli`
--

CREATE TABLE IF NOT EXISTS `ruoli` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `ruoli`
--

INSERT INTO `ruoli` (`id`, `nome`) VALUES
(1, 'admin'),
(2, 'manutentore');

-- --------------------------------------------------------

--
-- Struttura della tabella `semafori`
--

CREATE TABLE IF NOT EXISTS `semafori` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `codice_seriale` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_incrocio` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitudine` decimal(10,8) DEFAULT NULL,
  `longitudine` decimal(11,8) DEFAULT NULL,
  `creato_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `aggiornato_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codice_seriale` (`codice_seriale`),
  KEY `idx_seriale` (`codice_seriale`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `semafori`
--

INSERT INTO `semafori` (`id`, `codice_seriale`, `nome_incrocio`, `password`, `latitudine`, `longitudine`, `creato_at`, `aggiornato_at`) VALUES
(1, 'semaforo_01', 'Incrocio Centrale', '$2y$10$7Z8Hh9Kx7mB9L4dE2fR3uOnVxY6ZpWqQrRsStTuUvVwWxXyYzZmMa', 45.46420300, 9.19003100, '2026-05-22 08:38:14', '2026-05-22 08:38:14');

-- --------------------------------------------------------

--
-- Struttura della tabella `semafori_manutentori`
--

CREATE TABLE IF NOT EXISTS `semafori_manutentori` (
  `utente_id` bigint(20) NOT NULL,
  `semaforo_id` bigint(20) NOT NULL,
  PRIMARY KEY (`utente_id`,`semaforo_id`),
  KEY `semaforo_id` (`semaforo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

CREATE TABLE IF NOT EXISTS `utenti` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruolo_id` int(11) NOT NULL,
  `creato_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `ruolo_id` (`ruolo_id`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`id`, `username`, `email`, `password`, `ruolo_id`, `creato_at`) VALUES
(1, 'admin', 'admin.sistema@semanet.it', '$2y$10$il9iJULRoOJWqY4L9PmOGeL32WJnLdPYWjtWtS3KnxWBXxWK6dQnG', 1, '2026-05-22 09:32:04'),
(2, 'tecnico', 'tecnico.operativo@semanet.it', '$2y$10$UE4I6iAbZ5KvadGShTJMcelhb9s5DC0drsjC4wElkcrRZf0.BUMgW', 2, '2026-05-22 09:32:04');

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `dati_sensori`
--
ALTER TABLE `dati_sensori`
  ADD CONSTRAINT `dati_sensori_ibfk_1` FOREIGN KEY (`semaforo_id`) REFERENCES `semafori` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `semafori_manutentori`
--
ALTER TABLE `semafori_manutentori`
  ADD CONSTRAINT `semafori_manutentori_ibfk_1` FOREIGN KEY (`utente_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `semafori_manutentori_ibfk_2` FOREIGN KEY (`semaforo_id`) REFERENCES `semafori` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `utenti`
--
ALTER TABLE `utenti`
  ADD CONSTRAINT `utenti_ibfk_1` FOREIGN KEY (`ruolo_id`) REFERENCES `ruoli` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
