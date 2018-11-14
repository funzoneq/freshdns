<?php
abstract class database
{
	abstract function initiate ();
	
	abstract function query_slave($query);
	
	abstract function query_master($query);
	
	abstract function error ();
	
	/**
	 * fetch one database row at a time, as an associative array
	 */
	abstract function fetch_row($query);

	/**
	 * fetch all result rows at once, as an indexed array of associative arrays
	 */
	abstract function fetch_all($query);
	
	abstract function num_rows ($query);
	
	abstract function escape_string ($string);
	
	abstract function setUsername ($username);
	
	abstract function setPassword ($password);
	
	abstract function setDatabase ($database);
	
	abstract function setMasterHost ($master);
	
	abstract function setSlaveHosts ($slaves = array());
	
	abstract function setReplication ($replication);
	
	abstract function setVars ($username, $password, $database, $master, $slave = array(), $replication='');
}
