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
-- Table structure for table `anego_comments_blog`
--

CREATE TABLE IF NOT EXISTS `anego_comments_blog` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `element_id` int(11) NOT NULL,
  `user` varchar(80) NOT NULL,
  `date` int(11) NOT NULL,
  `comment` text NOT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_elements_blog`
--

CREATE TABLE IF NOT EXISTS `anego_elements_blog` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext NOT NULL,
  `style` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_elements_blog_entry`
--

CREATE TABLE IF NOT EXISTS `anego_elements_blog_entry` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `blog_id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `entry` text NOT NULL,
  `comments` int(11) NOT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_aloha`
--

CREATE TABLE IF NOT EXISTS `anego_pages_aloha` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext NOT NULL,
  `style` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_element_eng`
--

CREATE TABLE IF NOT EXISTS `anego_pages_element_eng` (
  `page_id` int(11) NOT NULL,
  `element_id` int(11) NOT NULL,
  `module_id` varchar(100) NOT NULL,
  `position` int(11) NOT NULL,
  KEY `page_id` (`page_id`,`element_id`),
  KEY `page_id_2` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_element_ger`
--

CREATE TABLE IF NOT EXISTS `anego_pages_element_ger` (
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

CREATE TABLE IF NOT EXISTS `anego_pages_eng` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL DEFAULT '',
  `info` varchar(150) NOT NULL DEFAULT '',
  `date` int(13) NOT NULL DEFAULT '0',
  `parent_idx` int(11) NOT NULL DEFAULT '0',
  `content` longtext NOT NULL,
  `content_prepared` text NOT NULL,
  `file` varchar(255) NOT NULL,
  `visibility` tinyint(4) NOT NULL DEFAULT '1',
  `position` smallint(6) NOT NULL DEFAULT '0',
  `subpoint` tinyint(4) NOT NULL DEFAULT '0',
  `nolink` tinyint(4) NOT NULL DEFAULT '0',
  `image` varchar(100) NOT NULL DEFAULT '',
  `image_selected` varchar(100) NOT NULL DEFAULT '',
  `menu` enum('MAIN','MINOR') NOT NULL DEFAULT 'MAIN',
  `defImg` varchar(255) DEFAULT NULL,
  `hoverImg` varchar(255) DEFAULT NULL,
  `activeImg` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_forms`
--

CREATE TABLE IF NOT EXISTS `anego_pages_forms` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `page_pos` smallint(11) NOT NULL,
  `value` longtext NOT NULL,
  `style` varchar(255) NOT NULL,
  PRIMARY KEY (`idx`),
  KEY `page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_gallery`
--

CREATE TABLE IF NOT EXISTS `anego_pages_gallery` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext NOT NULL,
  `style` varchar(255) NOT NULL,
  `preview_width` smallint(6) NOT NULL,
  `preview_height` smallint(6) NOT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_gallerypicture`
--

CREATE TABLE IF NOT EXISTS `anego_pages_gallerypicture` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `gallery_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `position` int(11) NOT NULL,
  `filename` varchar(250) NOT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_ger`
--

CREATE TABLE IF NOT EXISTS `anego_pages_ger` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL DEFAULT '',
  `info` varchar(150) NOT NULL DEFAULT '',
  `date` int(13) NOT NULL DEFAULT '0',
  `parent_idx` int(11) NOT NULL DEFAULT '0',
  `content` longtext NOT NULL,
  `content_prepared` text NOT NULL,
  `file` varchar(255) NOT NULL,
  `visibility` tinyint(4) NOT NULL DEFAULT '1',
  `position` smallint(6) NOT NULL DEFAULT '0',
  `subpoint` tinyint(4) NOT NULL DEFAULT '0',
  `nolink` tinyint(4) NOT NULL DEFAULT '0',
  `image` varchar(100) NOT NULL DEFAULT '',
  `image_selected` varchar(100) NOT NULL DEFAULT '',
  `menu` enum('MAIN','MINOR') NOT NULL DEFAULT 'MAIN',
  `defImg` varchar(255) DEFAULT NULL,
  `hoverImg` varchar(255) DEFAULT NULL,
  `activeImg` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_picture`
--

CREATE TABLE IF NOT EXISTS `anego_pages_picture` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext NOT NULL,
  `style` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_richtext`
--

CREATE TABLE IF NOT EXISTS `anego_pages_richtext` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext NOT NULL,
  `style` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_seperator`
--

CREATE TABLE IF NOT EXISTS `anego_pages_seperator` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext NOT NULL,
  `style` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_settings_eng`
--

CREATE TABLE IF NOT EXISTS `anego_settings_eng` (
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_settings_ger`
--

CREATE TABLE IF NOT EXISTS `anego_settings_ger` (
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
