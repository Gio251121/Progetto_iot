-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 24, 2026 alle 18:12
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

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

CREATE TABLE `dati_sensori` (
                                `id` bigint(20) NOT NULL,
                                `semaforo_id` bigint(20) NOT NULL,
                                `topic` varchar(255) NOT NULL,
                                `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
                                `ricevuto_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `ruoli`
--

CREATE TABLE `ruoli` (
                         `id` int(11) NOT NULL,
                         `nome` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `semafori` (
                            `id` bigint(20) NOT NULL,
                            `codice_seriale` varchar(100) NOT NULL,
                            `nome_incrocio` varchar(150) NOT NULL,
                            `password` varchar(255) NOT NULL,
                            `latitudine` decimal(10,8) DEFAULT NULL,
                            `longitudine` decimal(11,8) DEFAULT NULL,
                            `creato_at` timestamp NOT NULL DEFAULT current_timestamp(),
                            `aggiornato_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `semafori`
--

INSERT INTO `semafori` (`id`, `codice_seriale`, `nome_incrocio`, `password`, `latitudine`, `longitudine`, `creato_at`, `aggiornato_at`) VALUES
                                                                                                                                            (1, 'semaforo_01', 'Rotonda', '$2y$10$7Z8Hh9Kx7mB9L4dE2fR3uOnVxY6ZpWqQrRsStTuUvVwWxXyYzZmMa', 45.55264100, 11.55381100, '2026-05-22 08:38:14', '2026-05-23 05:47:26'),
                                                                                                                                            (2, 'SR-77X2-VI', 'Rotonda ITIS Rossi - Lato Est', '', 45.55032000, 11.55661000, '2026-05-24 14:22:40', '2026-05-24 14:22:40'),
                                                                                                                                            (3, 'SR-1011-VI', 'Incrocio Stazione - Viale Milano / Viale dell Ippodromo', '', 45.54280000, 11.54010000, '2026-05-24 16:01:55', '2026-05-24 16:01:55'),
                                                                                                                                            (4, 'SR-1022-VI', 'Zona Ovest - Viale San Lazzaro / Via Rossi', '', 45.53920000, 11.51750000, '2026-05-24 16:01:55', '2026-05-24 16:01:55'),
                                                                                                                                            (5, 'SR-1033-VI', 'Corso Santi Felice e Fortunato / Viale Torino', '', 45.54450000, 11.53320000, '2026-05-24 16:01:55', '2026-05-24 16:01:55'),
                                                                                                                                            (6, 'SR-1044-VI', 'Zona Università - Viale Margherita / Viale Giuriolo', '', 45.54580000, 11.55210000, '2026-05-24 16:01:55', '2026-05-24 16:01:55'),
                                                                                                                                            (7, 'SR-1055-VI', 'Zona Anconetta - Viale Trieste / Via Quadri', '', 45.55920000, 11.56450000, '2026-05-24 16:01:55', '2026-05-24 16:01:55'),
                                                                                                                                            (8, 'SR-1066-VI', 'Circonvallazione Nord - Viale d Alviano / Via Fratelli Bandiera', '', 45.55430000, 11.54120000, '2026-05-24 16:01:55', '2026-05-24 16:01:55'),
                                                                                                                                            (9, 'SR-1077-VI', 'Zona Ospedale - Viale Rodolfi / Ingresso San Bortolo', '', 45.55520000, 11.54910000, '2026-05-24 16:01:55', '2026-05-24 16:01:55'),
                                                                                                                                            (10, 'SR-1088-VI', 'Zona Est - Viale della Pace / Via Zamenhof', '', 45.53210000, 11.57940000, '2026-05-24 16:01:55', '2026-05-24 16:01:55'),
                                                                                                                                            (11, 'SR-1099-VI', 'Zona Industriale - Strada Statale Pasubio / Via dell Edilizia', '', 45.57100000, 11.51920000, '2026-05-24 16:01:55', '2026-05-24 16:01:55'),
                                                                                                                                            (12, 'SR-1100-VI', 'Zona Sud - Viale Riviera Berica / Via d Asolo', '', 45.52850000, 11.55830000, '2026-05-24 16:01:55', '2026-05-24 16:01:55');

-- --------------------------------------------------------

--
-- Struttura della tabella `semafori_manutentori`
--

CREATE TABLE `semafori_manutentori` (
                                        `utente_id` bigint(20) NOT NULL,
                                        `semaforo_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `semafori_manutentori`
--

INSERT INTO `semafori_manutentori` (`utente_id`, `semaforo_id`) VALUES
                                                                    (2, 1),
                                                                    (2, 2),
                                                                    (9, 1),
                                                                    (9, 2);

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

CREATE TABLE `utenti` (
                          `id` bigint(20) NOT NULL,
                          `username` varchar(100) NOT NULL,
                          `email` varchar(150) NOT NULL,
                          `password` varchar(255) NOT NULL,
                          `ruolo_id` int(11) NOT NULL,
                          `creato_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`id`, `username`, `email`, `password`, `ruolo_id`, `creato_at`) VALUES
                                                                                          (1, 'admin', 'admin.sistema@semanet.it', '$2y$10$il9iJULRoOJWqY4L9PmOGeL32WJnLdPYWjtWtS3KnxWBXxWK6dQnG', 1, '2026-05-22 09:32:04'),
                                                                                          (2, 'tecnico', 'tecnico.operativo@semanet.it', '$2y$10$UE4I6iAbZ5KvadGShTJMcelhb9s5DC0drsjC4wElkcrRZf0.BUMgW', 2, '2026-05-22 09:32:04'),
                                                                                          (3, 'DJFede', 'sdas@ada', '$2y$10$8Q7crH1F7INpchUTlhI47.XSKVx1iiHEp6ofVaSZ/lBoMxlu2M/He', 2, '2026-05-24 15:09:27'),
                                                                                          (5, 'alok00256', 'adas@fdfs.ccc', '$2y$10$dWZ4ZwMJeg868qv6uTEfruDg0bGV/zDPjdzHZNXEPFchv8Xn2E.Ea', 2, '2026-05-24 15:38:12'),
                                                                                          (9, 'tecnico1', 'tecnico@gmail.com', '$2y$10$pMZuSfeDzmJSICr3VwQh5OhBSXsBRoBxSIGemHfNlfUrrbYUCWI76', 2, '2026-05-24 15:58:03');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `dati_sensori`
--
ALTER TABLE `dati_sensori`
    ADD PRIMARY KEY (`id`),
  ADD KEY `idx_semaforo_time` (`semaforo_id`,`ricevuto_at`);

--
-- Indici per le tabelle `ruoli`
--
ALTER TABLE `ruoli`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Indici per le tabelle `semafori`
--
ALTER TABLE `semafori`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codice_seriale` (`codice_seriale`),
  ADD KEY `idx_seriale` (`codice_seriale`);

--
-- Indici per le tabelle `semafori_manutentori`
--
ALTER TABLE `semafori_manutentori`
    ADD PRIMARY KEY (`utente_id`,`semaforo_id`),
  ADD KEY `semaforo_id` (`semaforo_id`);

--
-- Indici per le tabelle `utenti`
--
ALTER TABLE `utenti`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `ruolo_id` (`ruolo_id`),
  ADD KEY `idx_username` (`username`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `dati_sensori`
--
ALTER TABLE `dati_sensori`
    MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `ruoli`
--
ALTER TABLE `ruoli`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `semafori`
--
ALTER TABLE `semafori`
    MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT per la tabella `utenti`
--
ALTER TABLE `utenti`
    MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
