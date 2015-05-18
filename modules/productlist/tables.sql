--
-- Table structure for table `anego_pages_product`
--

CREATE TABLE IF NOT EXISTS `anego_pages_product` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `products_idx` int(11) DEFAULT NULL,
  `page_idx` int(11) DEFAULT NULL,
  `element_idx` int(11) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `anego_pages_products`
--

CREATE TABLE IF NOT EXISTS `anego_pages_products` (
  `idx` int(11) NOT NULL AUTO_INCREMENT,
  `value` longtext DEFAULT NULL,
  `style` varchar(255) DEFAULT NULL,
  `productswidth` int(11) DEFAULT NULL,
  `productwidth` smallint(6) DEFAULT NULL,
  `productheight` smallint(6) DEFAULT NULL,
  `producthorispacing` smallint(6) DEFAULT NULL,
  `productvertispacing` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`idx`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
