-- phpMyAdmin SQL Dump
-- version 4.1.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 10, 2015 at 04:48 PM
-- Server version: 5.5.38-1~dotdeb.0
-- PHP Version: 5.4.38

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `aptimob`
--

-- --------------------------------------------------------

--
-- Table structure for table `aptimob_country_codes`
--

CREATE TABLE IF NOT EXISTS `aptimob_country_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(5) COLLATE latin1_german1_ci NOT NULL,
  `name` varchar(40) COLLATE latin1_german1_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `aptimob_domains`
--

CREATE TABLE IF NOT EXISTS `aptimob_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE latin1_german1_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Table structure for table `aptimob_groups`
--

CREATE TABLE IF NOT EXISTS `aptimob_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` varchar(20) COLLATE latin1_german1_ci NOT NULL,
  `description` varchar(40) COLLATE latin1_german1_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `aptimob_messages`
--

CREATE TABLE IF NOT EXISTS `aptimob_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` int(11) NOT NULL,
  `refId` int(11) NOT NULL,
  `senderId` int(11) NOT NULL,
  `to` text COLLATE latin1_german1_ci NOT NULL,
  `from` varchar(80) COLLATE latin1_german1_ci NOT NULL,
  `subject` text COLLATE latin1_german1_ci NOT NULL,
  `timestamp` int(11) NOT NULL,
  `body` text COLLATE latin1_german1_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `senderId` (`senderId`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=87 ;

-- --------------------------------------------------------

--
-- Table structure for table `aptimob_message_recipient`
--

CREATE TABLE IF NOT EXISTS `aptimob_message_recipient` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `messageId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=123 ;

-- --------------------------------------------------------

--
-- Table structure for table `aptimob_skills`
--

CREATE TABLE IF NOT EXISTS `aptimob_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` int(11) NOT NULL,
  `domain` int(11) NOT NULL,
  `code` varchar(40) COLLATE latin1_german1_ci NOT NULL,
  `description` varchar(160) COLLATE latin1_german1_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=156 ;

-- --------------------------------------------------------

--
-- Table structure for table `aptimob_skill_categories`
--

CREATE TABLE IF NOT EXISTS `aptimob_skill_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain` int(11) NOT NULL,
  `code` varchar(20) COLLATE latin1_german1_ci NOT NULL,
  `description` varchar(160) COLLATE latin1_german1_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=96 ;

-- --------------------------------------------------------

--
-- Table structure for table `aptimob_users`
--

CREATE TABLE IF NOT EXISTS `aptimob_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain` int(11) NOT NULL,
  `username` varchar(60) COLLATE latin1_german1_ci NOT NULL,
  `hash` varchar(192) COLLATE latin1_german1_ci NOT NULL,
  `firstName` varchar(40) COLLATE latin1_german1_ci NOT NULL,
  `lastName` varchar(60) COLLATE latin1_german1_ci NOT NULL,
  `available` tinyint(1) NOT NULL,
  `address` varchar(80) COLLATE latin1_german1_ci NOT NULL,
  `postalCode` varchar(10) COLLATE latin1_german1_ci NOT NULL,
  `city` varchar(80) COLLATE latin1_german1_ci NOT NULL,
  `country` smallint(6) NOT NULL,
  `cellPhone` varchar(16) COLLATE latin1_german1_ci NOT NULL,
  `phone` varchar(16) COLLATE latin1_german1_ci NOT NULL,
  `taxCode` varchar(24) COLLATE latin1_german1_ci NOT NULL,
  `lon` double NOT NULL,
  `lat` double NOT NULL,
  `gpsTimestamp` int(11) NOT NULL,
  `gcmRegId` text COLLATE latin1_german1_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `domain` (`domain`),
  KEY `username_2` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=45 ;

-- --------------------------------------------------------

--
-- Table structure for table `aptimob_user_domain`
--

CREATE TABLE IF NOT EXISTS `aptimob_user_domain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `domainId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `aptimob_user_group`
--

CREATE TABLE IF NOT EXISTS `aptimob_user_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=72 ;

-- --------------------------------------------------------

--
-- Table structure for table `aptimob_user_skill`
--

CREATE TABLE IF NOT EXISTS `aptimob_user_skill` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `skillId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  KEY `userId_2` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_german1_ci AUTO_INCREMENT=175 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
