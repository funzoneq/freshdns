<?php
abstract class database
{
	abstract function querySlave($query);
	
	abstract function queryMaster($query);
	
	abstract function error ();
	
	/**
	 * fetch one database row at a time, as an associative array
	 */
	abstract function fetchRow($query);

	/**
	 * fetch all result rows at once, as an indexed array of associative arrays
	 */
	abstract function fetchAll($query);
	
	abstract function rowCount ($query);
	
	abstract function escape_string ($string);
	
	abstract function setUsername ($username);
	
	abstract function setPassword ($password);
	
	abstract function setDatabase ($database);
	
	abstract function setMasterHost ($master);
	
	abstract function setSlaveHosts ($slaves = array());
	
	abstract function setReplication ($replication);
	
	abstract function __construct ($username, $password, $database, $master, $slave = array(), $replication='');
}
