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
		$query["create domains"] = "CREATE TABLE IF NOT EXISTS domains (
		 id		 INT auto_increment,
		 name		 VARCHAR(255) NOT NULL,
		 master		 VARCHAR(20) DEFAULT NULL,
		 last_check	 INT DEFAULT NULL,
		 type		 VARCHAR(6) NOT NULL,
		 notified_serial INT DEFAULT NULL, 
		 account         VARCHAR(40) DEFAULT NULL,
		 primary key (id)
		) Engine=InnoDB;";
		
		$query["create index on domains"] = "CREATE UNIQUE INDEX  name_index ON domains(name);";
		
		$query["create records"] = "CREATE TABLE IF NOT EXISTS records (
		  id              INT auto_increment,
		  domain_id       INT DEFAULT NULL,
		  name            VARCHAR(255) DEFAULT NULL,
		  type            VARCHAR(6) DEFAULT NULL,
		  content         VARCHAR(255) DEFAULT NULL,
		  ttl             INT DEFAULT NULL,
		  prio            INT DEFAULT NULL,
		  change_date     INT DEFAULT NULL,
		  primary key(id)
		) Engine=InnoDB;";
		
		$query["create index on records 1"] = "CREATE INDEX rec_name_index ON records(name);";
		$query["create index on records 2"] = "CREATE INDEX nametype_index ON records(name,type);";
		$query["create index on records 3"] = "CREATE INDEX domain_id ON records(domain_id);";
		
		$query["create supermasters"] = "CREATE TABLE IF NOT EXISTS supermasters (
		  ip VARCHAR(25) NOT NULL, 
		  nameserver VARCHAR(255) NOT NULL, 
		  account VARCHAR(40) DEFAULT NULL
		);";
		$this->run_queries_with_log($query);
	}

	function run_queries_with_log($query) {
		foreach($query AS $name => $Q)
		{
			try {
				echo "<li>$name";
				$this->database->queryMaster($Q);
				echo " - success</li>";
			}catch(Exception $ex) {
				echo " - failed:<br><pre>$ex</pre></li>";
			}
		}
	}
	
	function padmin_sql ()
	{
		$query = array();
		
		$query["create record_owners"] = "CREATE TABLE IF NOT EXISTS `record_owners` (
		  `id` int(11) NOT NULL auto_increment,
		  `user_id` int(11) NOT NULL default '0',
		  `record_id` int(11) NOT NULL default '0',
		  PRIMARY KEY  (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		
		$query["create users"] = "CREATE TABLE IF NOT EXISTS `users` (
		  `id` int(11) NOT NULL auto_increment,
		  `username` varchar(16) NOT NULL default '',
		  `password` TEXT NOT NULL,
		  `fullname` varchar(255) NOT NULL default '',
		  `email` varchar(255) NOT NULL default '',
		  `description` text NOT NULL,
		  `level` tinyint(3) NOT NULL default '0',
		  `active` tinyint(1) NOT NULL default '0',
		  `u2fdata` text not null default '',
		  PRIMARY KEY  (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		
		$query["create zones"] = "CREATE TABLE IF NOT EXISTS `zones` (
		  `id` int(11) NOT NULL auto_increment,
		  `domain_id` int(11) NOT NULL default '0',
		  `owner` int(11) NOT NULL default '0',
		  `comment` text,
		  PRIMARY KEY  (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
    
		$query["add column maxdomains to users"] = "ALTER TABLE `users` ADD `maxdomains` SMALLINT( 5 ) NOT NULL;";
		$query["1.0.4 change password field type"] = "alter table users modify password  text not null default '';";
		$query["1.0.5 add u2fdata to users"] = "alter table users add u2fdata text not null default '';";
		$query["1.0.5 add unique key on username"] = "CREATE UNIQUE INDEX username_index ON users (username);";

		$query["create template"] = "CREATE TABLE IF NOT EXISTS `template` (
		  `templateId` int(11) NOT NULL AUTO_INCREMENT,
		  `userId` int(11) NOT NULL,
		  `name` varchar(255) NOT NULL,
		  PRIMARY KEY (`templateId`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

		$query["create template_records"] = "CREATE TABLE IF NOT EXISTS `template_records` (
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
		
		$this->run_queries_with_log($query);
	}
	
	function zonelessdomains ()
	{
		$query = "SELECT d.id FROM domains d
		LEFT OUTER JOIN zones z ON z.domain_id=d.id where z.domain_id IS NULL;";
		return $this->database->fetchAll($query, [  ]);
	}
}
