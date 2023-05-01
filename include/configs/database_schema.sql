-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Erstellungszeit: 01. Mai 2023 um 11:48
-- Server-Version: 10.6.12-MariaDB-0ubuntu0.22.04.1
-- PHP-Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `accounts`
--

CREATE TABLE `accounts` (
  `userid` int(10) NOT NULL,
  `chash` varchar(32) NOT NULL,
  `lastaccess` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `username` varchar(100) NOT NULL,
  `email` varchar(80) NOT NULL,
  `baduser` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bans`
--

CREATE TABLE `bans` (
  `id` int(10) NOT NULL,
  `added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `addedby` int(10) NOT NULL,
  `comment` varchar(255) NOT NULL,
  `first` int(11) DEFAULT NULL,
  `last` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `history`
--

CREATE TABLE `history` (
  `history_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `language` varchar(3) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `method` enum('insert','update') NOT NULL,
  `old_string` varchar(1024) NOT NULL,
  `new_string` varchar(1024) NOT NULL,
  `translate_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `language`
--

CREATE TABLE `language` (
  `lng_id` int(2) NOT NULL,
  `lng_code` varchar(5) NOT NULL,
  `lng_title` varchar(128) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `secure`
--

CREATE TABLE `secure` (
  `id` int(10) NOT NULL,
  `secure` varchar(20) NOT NULL,
  `hash` varchar(200) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(10) NOT NULL,
  `username` varchar(100) NOT NULL,
  `passhash` varchar(32) NOT NULL,
  `pass` varchar(60) NOT NULL,
  `secret` tinyblob NOT NULL,
  `email` varchar(80) NOT NULL,
  `language` varchar(2) NOT NULL,
  `status` enum('pending','confirmed') NOT NULL,
  `added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_access` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip` varchar(15) NOT NULL,
  `enabled` enum('yes','no') NOT NULL,
  `admin` enum('yes','no') NOT NULL DEFAULT 'no',
  `session` varchar(255) NOT NULL,
  `translation` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users_blacklist`
--

CREATE TABLE `users_blacklist` (
  `id` int(10) NOT NULL,
  `bann_name` varchar(100) NOT NULL,
  `bann_datum` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `bann_email` varchar(80) NOT NULL,
  `bann_grund` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `xliff_general`
--

CREATE TABLE `xliff_general` (
  `general_id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `renpyid` varchar(1024) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `org_filename` varchar(1024) NOT NULL,
  `filename` varchar(1024) NOT NULL,
  `comment` varchar(1024) NOT NULL,
  `linenumber` int(5) NOT NULL,
  `person` varchar(128) NOT NULL,
  `emote` varchar(3) NOT NULL,
  `label` varchar(128) NOT NULL,
  `ignorable` varchar(1024) NOT NULL,
  `igno_start` varchar(128) NOT NULL,
  `igno_end` varchar(128) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `xliff_original`
--

CREATE TABLE `xliff_original` (
  `original_id` int(11) NOT NULL,
  `source` varchar(1024) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `md5hash` varchar(32) NOT NULL,
  `general` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `xliff_translate`
--

CREATE TABLE `xliff_translate` (
  `translate_id` int(11) NOT NULL,
  `general` int(11) NOT NULL,
  `original` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `language` int(3) NOT NULL,
  `translatet` varchar(1024) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `xliff_upload`
--

CREATE TABLE `xliff_upload` (
  `uplaod_id` int(11) NOT NULL,
  `upload_file` varchar(255) NOT NULL,
  `upload_dir` varchar(255) NOT NULL,
  `upload_size` bigint(20) UNSIGNED NOT NULL,
  `upload_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `upload_user` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `bans`
--
ALTER TABLE `bans`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `history_id` (`history_id`),
  ADD KEY `translate_id` (`translate_id`),
  ADD KEY `uuid` (`uuid`),
  ADD KEY `username` (`username`);
ALTER TABLE `history` ADD FULLTEXT KEY `old_string` (`old_string`);
ALTER TABLE `history` ADD FULLTEXT KEY `new_string` (`new_string`);

--
-- Indizes für die Tabelle `language`
--
ALTER TABLE `language`
  ADD PRIMARY KEY (`lng_id`);

--
-- Indizes für die Tabelle `secure`
--
ALTER TABLE `secure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_secure_hash` (`secure`,`hash`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `users_blacklist`
--
ALTER TABLE `users_blacklist`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `xliff_general`
--
ALTER TABLE `xliff_general`
  ADD PRIMARY KEY (`general_id`),
  ADD KEY `general_id` (`general_id`),
  ADD KEY `uuid` (`uuid`),
  ADD KEY `filename` (`filename`(333)),
  ADD KEY `renpyid` (`renpyid`(333));

--
-- Indizes für die Tabelle `xliff_original`
--
ALTER TABLE `xliff_original`
  ADD PRIMARY KEY (`original_id`),
  ADD KEY `original_id` (`original_id`),
  ADD KEY `general` (`general`),
  ADD KEY `md5hash` (`md5hash`),
  ADD KEY `uuid` (`uuid`);

--
-- Indizes für die Tabelle `xliff_translate`
--
ALTER TABLE `xliff_translate`
  ADD PRIMARY KEY (`translate_id`),
  ADD KEY `translate_id` (`translate_id`),
  ADD KEY `general` (`general`),
  ADD KEY `original` (`original`),
  ADD KEY `uuid` (`uuid`);
ALTER TABLE `xliff_translate` ADD FULLTEXT KEY `translatet` (`translatet`);

--
-- Indizes für die Tabelle `xliff_upload`
--
ALTER TABLE `xliff_upload`
  ADD PRIMARY KEY (`uplaod_id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `bans`
--
ALTER TABLE `bans`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `history`
--
ALTER TABLE `history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `language`
--
ALTER TABLE `language`
  MODIFY `lng_id` int(2) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `secure`
--
ALTER TABLE `secure`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `users_blacklist`
--
ALTER TABLE `users_blacklist`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `xliff_general`
--
ALTER TABLE `xliff_general`
  MODIFY `general_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `xliff_original`
--
ALTER TABLE `xliff_original`
  MODIFY `original_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `xliff_translate`
--
ALTER TABLE `xliff_translate`
  MODIFY `translate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `xliff_upload`
--
ALTER TABLE `xliff_upload`
  MODIFY `uplaod_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
