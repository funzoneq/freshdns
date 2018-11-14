<?php
class install 
{
	private $database;
	
	function __construct($database)
	{
		$this->database = $database;
	}
	
	function pdns_sql ()
	{
		$query = array();
		$query[] = "CREATE TABLE IF NOT EXISTS domains (
		 id		 INT auto_increment,
		 name		 VARCHAR(255) NOT NULL,
		 master		 VARCHAR(20) DEFAULT NULL,
		 last_check	 INT DEFAULT NULL,
		 type		 VARCHAR(6) NOT NULL,
		 notified_serial INT DEFAULT NULL, 
		 account         VARCHAR(40) DEFAULT NULL,
		 primary key (id)
		)type=InnoDB;";
		
		$query[] = "CREATE UNIQUE INDEX name_index ON domains(name);";
		
		$query[] = "CREATE TABLE IF NOT EXISTS records (
		  id              INT auto_increment,
		  domain_id       INT DEFAULT NULL,
		  name            VARCHAR(255) DEFAULT NULL,
		  type            VARCHAR(6) DEFAULT NULL,
		  content         VARCHAR(255) DEFAULT NULL,
		  ttl             INT DEFAULT NULL,
		  prio            INT DEFAULT NULL,
		  change_date     INT DEFAULT NULL,
		  primary key(id)
		)type=InnoDB;";
		
		$query[] = "CREATE INDEX rec_name_index ON records(name);";
		$query[] = "CREATE INDEX nametype_index ON records(name,type);";
		$query[] = "CREATE INDEX domain_id ON records(domain_id);";
		
		$query[] = "CREATE TABLE IF NOT EXISTS supermasters (
		  ip VARCHAR(25) NOT NULL, 
		  nameserver VARCHAR(255) NOT NULL, 
		  account VARCHAR(40) DEFAULT NULL
		);";
		
		foreach($query AS $Q)
		{
			@$this->database->query_master($Q);
		}
	}
	
	function padmin_sql ()
	{
		$query = array();
		
		$query[] = "CREATE TABLE IF NOT EXISTS `record_owners` (
		  `id` int(11) NOT NULL auto_increment,
		  `user_id` int(11) NOT NULL default '0',
		  `record_id` int(11) NOT NULL default '0',
		  PRIMARY KEY  (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		
		$query[] = "CREATE TABLE IF NOT EXISTS `users` (
		  `id` int(11) NOT NULL auto_increment,
		  `username` varchar(16) NOT NULL default '',
		  `password` TEXT NOT NULL,
		  `fullname` varchar(255) NOT NULL default '',
		  `email` varchar(255) NOT NULL default '',
		  `description` text NOT NULL,
		  `level` tinyint(3) NOT NULL default '0',
		  `active` tinyint(1) NOT NULL default '0',
		  PRIMARY KEY  (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		
		$query[] = "CREATE TABLE IF NOT EXISTS `zones` (
		  `id` int(11) NOT NULL auto_increment,
		  `domain_id` int(11) NOT NULL default '0',
		  `owner` int(11) NOT NULL default '0',
		  `comment` text,
		  PRIMARY KEY  (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
    
		$query[] = "ALTER TABLE `users` ADD `maxdomains` SMALLINT( 5 ) NOT NULL;";

		$query[] = "CREATE TABLE IF NOT EXISTS `template` (
		  `templateId` int(11) NOT NULL AUTO_INCREMENT,
		  `userId` int(11) NOT NULL,
		  `name` varchar(255) NOT NULL,
		  PRIMARY KEY (`templateId`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

		$query[] = "CREATE TABLE IF NOT EXISTS `template_records` (
		  `recordId` int(11) NOT NULL AUTO_INCREMENT,
		  `templateId` int(11) NOT NULL,
		  `name` varchar(255) NOT NULL,
		  `type` varchar(50) NOT NULL,
		  `content` varchar(255) NOT NULL,
		  `prio` smallint(5) NOT NULL,
		  `ttl` int(11) NOT NULL,
		  PRIMARY KEY (`recordId`),
		  KEY `templateId` (`templateId`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		
		foreach($query AS $Q)
		{
			$this->database->query_master($Q) or die ($this->database->error());
		}
	}
	
	function zonelessdomains ()
	{
		$query = "SELECT d.id FROM domains d
		LEFT OUTER JOIN zones z ON z.domain_id=d.id;";
		$query = $this->database->query_master($query) or die ($this->database->error());
		
		if($this->database->num_rows($query)==0)
		{
			return '';
		}else
		{
			while($record=$this->database->fetch_array($query))
			{
				$return[] = $record;
			}
		
			return $return;
		}
	}
}
