--
-- Table structure for table `anego_pages_seperator`
--

CREATE TABLE IF NOT EXISTS `anego_pages_seperator` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext NOT NULL,
  `style` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
