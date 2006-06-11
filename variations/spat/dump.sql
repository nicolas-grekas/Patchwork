-- phpMyAdmin SQL Dump
-- version 2.6.1-pl3
-- http://www.phpmyadmin.net
-- 
-- Serveur: localhost
-- Généré le : Mercredi 10 Août 2005 à 17:15
-- Version du serveur: 4.1.10
-- Version de PHP: 5.0.4
-- 
-- Base de données: `inscriptions`
-- 

-- --------------------------------------------------------

-- 
-- Structure de la table `data_payment`
-- 

CREATE TABLE `data_payment` (
  `inscription_id` int(11) NOT NULL default '0',
  `amount` varchar(255) NOT NULL default '',
  `payment_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `payment_method_id` int(11) NOT NULL default '0',
  `payment_method_info` varchar(255) NOT NULL default '',
  KEY `payment_method_id` (`payment_method_id`),
  KEY `inscription_id` (`inscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 
-- Contenu de la table `data_payment`
-- 

        

