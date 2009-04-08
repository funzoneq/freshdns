ALTER TABLE `users` ADD `maxdomains` SMALLINT( 5 ) NOT NULL;

CREATE TABLE IF NOT EXISTS `template` (
  `templateId` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`templateId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `template_records` (
  `recordId` int(11) NOT NULL AUTO_INCREMENT,
  `templateId` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `content` varchar(255) NOT NULL,
  `prio` smallint(5) NOT NULL,
  `ttl` int(11) NOT NULL,
  PRIMARY KEY (`recordId`),
  KEY `templateId` (`templateId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
