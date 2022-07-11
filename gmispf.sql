-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le :  sam. 18 juil. 2020 à 13:22
-- Version du serveur :  5.7.17
-- Version de PHP :  5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `gmispf`
--

-- --------------------------------------------------------

--
-- Structure de la table `document`
--

CREATE TABLE `document` (
  `Idrapport` int(10) UNSIGNED NOT NULL,
  `Libelle` tinytext NOT NULL,
  `Datereq` date NOT NULL,
  `Lieu` tinytext NOT NULL,
  `Installation` tinytext NOT NULL,
  `remplacer` tinytext NOT NULL,
  `Observation` varchar(30) NOT NULL,
  `etat` tinytext NOT NULL,
  `Niveau` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `historique`
--

CREATE TABLE `historique` (
  `Idrapport` int(10) UNSIGNED NOT NULL,
  `Libelle` tinytext NOT NULL,
  `Datereq` date NOT NULL,
  `Lieu` tinytext NOT NULL,
  `Installation` tinytext NOT NULL,
  `remplacer` tinytext NOT NULL,
  `Observation` varchar(30) NOT NULL,
  `etat` tinytext NOT NULL,
  `Niveau` tinytext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `rapport`
--

CREATE TABLE `rapport` (
  `idrapport` int(10) UNSIGNED NOT NULL,
  `Daterap` date NOT NULL,
  `Libelle` varchar(60) NOT NULL,
  `Lieu` varchar(50) NOT NULL,
  `Installation` tinytext NOT NULL,
  `Remplacer` varchar(60) NOT NULL,
  `etat` tinytext NOT NULL,
  `Niveau` tinytext NOT NULL,
  `Observation` varchar(60) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `Iduser` tinyint(3) UNSIGNED NOT NULL,
  `Username` varchar(25) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Fonction` tinytext NOT NULL,
  `mdp` varchar(30) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`Iduser`, `Username`, `Email`, `Fonction`, `mdp`) VALUES
(15, 'Kamguia', 'patntiwa19@gmailcom', 'Prestataire', 'SDPP36'),
(16, 'Fotso ', 'Fotso@net.cm', 'Agent', '0000');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`Idrapport`);

--
-- Index pour la table `historique`
--
ALTER TABLE `historique`
  ADD PRIMARY KEY (`Idrapport`);

--
-- Index pour la table `rapport`
--
ALTER TABLE `rapport`
  ADD PRIMARY KEY (`idrapport`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`Iduser`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `rapport`
--
ALTER TABLE `rapport`
  MODIFY `idrapport` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `Iduser` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
