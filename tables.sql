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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

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


--
-- Table structure for table `anego_pages_mailer`
--

CREATE TABLE IF NOT EXISTS `anego_pages_mailer` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `subject` text NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `mailtemplate` text NOT NULL,
  `hourlimit` smallint(6) NOT NULL DEFAULT '60',
  `formhtml` text NOT NULL,
  `successmessage` text NOT NULL,
  `numsent_total` int(11) NOT NULL DEFAULT '0',
  `numsent_lasthour` int(11) NOT NULL DEFAULT '0',
  `currenthour` int(11) NOT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;


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
  `url` varchar(255) NOT NULL,
  `info` varchar(150) NOT NULL DEFAULT '',
  `date` int(13) NOT NULL DEFAULT '0',
  `parent_idx` int(11) NOT NULL DEFAULT '0',
  `content` longtext NOT NULL,
  `content_prepared` text NOT NULL,
  `file` varchar(255) NOT NULL,
  `visibility` tinyint(4) NOT NULL DEFAULT '1',
  `position` smallint(6) NOT NULL DEFAULT '0',
  `nolink` tinyint(4) NOT NULL DEFAULT '0',
  `menu` enum('MAIN','MINOR') NOT NULL DEFAULT 'MAIN',
  `defimg` varchar(255) DEFAULT NULL,
  `hoverimg` varchar(255) DEFAULT NULL,
  `activeimg` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;


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

CREATE TABLE IF NOT EXISTS `anego_image_sizes` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `width` smallint(6) NOT NULL,
  `height` smallint(6) NOT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `test_image_sizes`
--

INSERT INTO `anego_image_sizes` (`idx`, `name`, `width`, `height`) VALUES
(1, 'Screen', 1024, 768),
(2, 'Medium Square', 150, 150),
(3, 'Tiny Square', 50, 50);

-- --------------------------------------------------------

--
-- Table structure for table `test_pages_gallery`
--

CREATE TABLE IF NOT EXISTS `anego_pages_gallery` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext NOT NULL,
  `style` varchar(255) NOT NULL,
  `original_default_size_id` int(11) NOT NULL DEFAULT '1',
  `preview_default_size_id` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `test_pages_gallerypicture`
--

CREATE TABLE IF NOT EXISTS `anego_pages_gallerypicture` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `gallery_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `position` int(11) NOT NULL,
  `filename` varchar(250) NOT NULL,
  `orig_cropx` smallint(6) NOT NULL,
  `orig_cropy` smallint(6) NOT NULL,
  `orig_cropw` smallint(6) NOT NULL,
  `orig_croph` smallint(6) NOT NULL,
  `prev_cropx` smallint(6) NOT NULL,
  `prev_cropy` smallint(6) NOT NULL,
  `prev_cropw` smallint(6) NOT NULL,
  `prev_croph` smallint(6) NOT NULL,
  `prev_w` smallint(6) NOT NULL,
  `prev_h` smallint(6) NOT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_ger`
--

CREATE TABLE IF NOT EXISTS `anego_pages_ger` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL,
  `info` varchar(150) NOT NULL DEFAULT '',
  `date` int(13) NOT NULL DEFAULT '0',
  `parent_idx` int(11) NOT NULL DEFAULT '0',
  `content` longtext NOT NULL,
  `content_prepared` text NOT NULL,
  `file` varchar(255) NOT NULL,
  `visibility` tinyint(4) NOT NULL DEFAULT '1',
  `position` smallint(6) NOT NULL DEFAULT '0',
  `nolink` tinyint(4) NOT NULL DEFAULT '0',
  `menu` enum('MAIN','MINOR') NOT NULL DEFAULT 'MAIN',
  `defimg` varchar(255) DEFAULT NULL,
  `hoverimg` varchar(255) DEFAULT NULL,
  `activeimg` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;


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



--
-- Table structure for table `anego_pages_product`
--

CREATE TABLE IF NOT EXISTS `anego_pages_product` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `products_idx` int(11) NOT NULL,
  `page_idx` int(11) NOT NULL,
  `element_idx` int(11) NOT NULL,
  `title` text NOT NULL,
  `description` text NOT NULL,
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_products`
--

CREATE TABLE IF NOT EXISTS `anego_pages_products` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext NOT NULL,
  `style` varchar(255) DEFAULT NULL,
  `productswidth` int(11) NOT NULL,
  `productwidth` smallint(6) NOT NULL,
  `productheight` smallint(6) NOT NULL,
  `producthorispacing` smallint(6) NOT NULL,
  `productvertispacing` smallint(6) NOT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;




/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
