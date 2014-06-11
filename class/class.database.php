<?php
abstract class database
{	
	abstract function initiate ();
	
	abstract function query_slave($query);
	
	abstract function query_master($query);
	
	abstract function error ();
	
	abstract function fetch_array($query);
	
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
?>