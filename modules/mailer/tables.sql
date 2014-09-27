

--
-- Table structure for table `anego_pages_mailer`
--

CREATE TABLE IF NOT EXISTS `anego_pages_mailer` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `subject` text DEFAULT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `mailtemplate` text DEFAULT NULL,
  `hourlimit` smallint(6) NOT NULL DEFAULT '60',
  `formhtml` text DEFAULT NULL,
  `successmessage` text NOT NULL,
  `numsent_total` int(11) NOT NULL DEFAULT '0',
  `numsent_lasthour` int(11) NOT NULL DEFAULT '0',
  `currenthour` int(11) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
