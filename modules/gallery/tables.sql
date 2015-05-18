CREATE TABLE IF NOT EXISTS `anego_image_sizes` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `width` smallint(6) NOT NULL,
  `height` smallint(6) NOT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

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
  `style` varchar(255) NULL default null,
  `original_default_size_id` int(11) NOT NULL DEFAULT '1',
  `preview_default_size_id` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `test_pages_gallerypicture`
--

CREATE TABLE IF NOT EXISTS `anego_pages_gallerypicture` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `gallery_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `filename` varchar(250) DEFAULT NULL,
  `orig_cropx` smallint(6) DEFAULT NULL,
  `orig_cropy` smallint(6) DEFAULT NULL,
  `orig_cropw` smallint(6) DEFAULT NULL,
  `orig_croph` smallint(6) DEFAULT NULL,
  `prev_cropx` smallint(6) DEFAULT NULL,
  `prev_cropy` smallint(6) DEFAULT NULL,
  `prev_cropw` smallint(6) DEFAULT NULL,
  `prev_croph` smallint(6) DEFAULT NULL,
  `prev_w` smallint(6) DEFAULT NULL,
  `prev_h` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;



-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_picture`
--

CREATE TABLE IF NOT EXISTS `anego_pages_picture` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext NOT NULL,
  `style` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;