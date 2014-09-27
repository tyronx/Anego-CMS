-- Host: localhost
-- Generation Time: Oct 07, 2011 at 04:05 PM
-- Server version: 5.5.10
-- PHP Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_element_eng`
--

CREATE TABLE IF NOT EXISTS `anego_pages_element` (
  `page_id` int(11) NOT NULL,
  `element_id` int(11) NOT NULL,
  `module_id` varchar(100) NOT NULL,
  `position` int(11) NOT NULL,
  KEY `page_id` (`page_id`,`element_id`),
  KEY `page_id_2` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_eng`
--

CREATE TABLE IF NOT EXISTS `anego_pages` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL DEFAULT '',
  `url` varchar(255) DEFAULT NULL,
  `info` varchar(150) NOT NULL DEFAULT '',
  `date` int(13) NOT NULL DEFAULT '0',
  `parent_idx` int(11) NOT NULL DEFAULT '0',
  `content` longtext DEFAULT NULL,
  `content_prepared` text DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `visibility` tinyint(4) NOT NULL DEFAULT '1',
  `position` smallint(6) NOT NULL DEFAULT '0',
  `nolink` tinyint(4) NOT NULL DEFAULT '0',
  `menu` enum('MAIN','MINOR') NOT NULL DEFAULT 'MAIN',
  `defimg` varchar(255) DEFAULT NULL,
  `hoverimg` varchar(255) DEFAULT NULL,
  `activeimg` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;


--
-- Table structure for table `anego_settings`
--

CREATE TABLE IF NOT EXISTS `anego_settings` (
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
   `lastmodified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------
