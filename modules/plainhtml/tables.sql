--
-- Table structure for table `anego_pages_richtext`
--

CREATE TABLE IF NOT EXISTS `anego_pages_richtext` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext NOT NULL,
  `style` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
