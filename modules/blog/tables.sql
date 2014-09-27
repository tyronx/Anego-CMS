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
