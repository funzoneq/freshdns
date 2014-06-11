<?php
class mysql extends database {
	private $master;
	private $slave;
	private $NRslaveQ;
	private $NRmasterQ;
	private $SS_MySQLserver;
	private $SS_masterMySQLserver;
	private $SS_MySQLservers;
	private $replication;
	private $username;
	private $password;
	private $database;
	
	/*****************************************************/
	
	function __construct () {
		if (session_id() == "") session_start();
		
		// REPLICATION STANDARD OFF
		$this->replication = 0;
	}
	
	function __destruct () {
		$this->disconnect_mysql();
		unset($this->SS_MySQLserver, $this->SS_masterMySQLserver, $this->NRmasterQ, $this->NRslaveQ);
		unset($this->username, $this->password, $this->database, $this->replication);
	}
	
	/*****************************************************/
	
	function initiate ()
	{
		// PICK RANDOM READER AND SAVE IT (PERSISTENT)
		if(!isset($_SESSION['SS_MySQLserver'])){
			$aantal = count($this->SS_MySQLservers)-1;
			$_SESSION['SS_MySQLserver'] = rand(0,$aantal);
		}
		
		// SET MYSQL SERVER (SLAVE)
		$this->SS_MySQLserver = $this->SS_MySQLservers[$_SESSION['SS_MySQLserver']];
		
		// CONNECT TO MYSQL
		$this->setstats();
		if(!$this->connect_to_mysql()){
			throw new Exception("No mysql connection was made");
		}
	}
	
	function connect_to_mysql (){
		$this->slave = new mysqli($this->SS_MySQLserver, $this->username, $this->password, $this->database);

		/* check connection */
		if ($this->slave->connect_errno) {
		    throw new Exception(printf("Connect failed: %s\n", $this->slave->connect_error));
		    return FALSE;
		}

		if (!$this->slave->query("SET a=1")) {
		    throw new Exception(printf("Errormessage: %s\n", $this->slave->error));
			return FALSE;
		}
		
		if(!$this->slave){
			throw new Exception("No slave connection");
			return FALSE;
		}else{
			return $this->slave->host_info;
		}
	}
	
	function connect_to_mysql_master(){
		if($this->replication==1)
		{
			// CONNECT TO THE MASTER
			$this->master = new mysqli($this->SS_MySQLserver, $this->username, $this->password, $this->database);
		
			/* check connection */
			if ($this->master->connect_errno) {
			    throw new Exception(printf("Connect failed: %s\n", $this->master->connect_error));
			    return FALSE;
			}

			if (!$this->master->query("SET a=1")) {
			    throw new Exception(printf("Errormessage: %s\n", $this->master->error));
				return FALSE;
			}
		
			if(!$this->master){
				throw new Exception("No master connection");
				return FALSE;
			}else{
				return $this->master->host_info;
			}
		}else
		{
			// ONLY CONNECT ONCE!
			$this->master = $this->slave;
		}
	}
	
	function disconnect_mysql (){
		$this->master->close();
		$this->slave->close();
	}
	
	/*****************************************************/
	
	function query_slave($query)
	{
		if(!$this->slave){
			$this->connect_to_mysql();
		}
		
		$this->NRslaveQ++;
		$query = $this->slave->query($query);
		return $query;
	}
	
	function query_master($query)
	{
		if(!$this->master){
			$this->connect_to_mysql_master();
		}
		
		$this->NRmasterQ++;
		$query = $this->master->query($query);
		return $query;
	}
	
	function error ()
	{
		return mysql_error();
	}
	
	function fetch_array($query)
	{
		return $query->fetch_array($query);
	}
	
	function num_rows ($query)
	{
		return $query->num_rows($query);
	}
	
	function escape_string ($string)
	{
		return $this->master->real_escape_string($string);
	}
	
	/*****************************************************/
	
	function showstats (){
		$return = array(
		'masterQ' => $this->NRmasterQ,
		'slaveQ' => $this->NRslaveQ
		);
		
		return $return;
	}
	
	function setstats(){
		$this->NRmasterQ 	= '0';
		$this->NRslaveQ 	= '0';
	}
	
	function setUsername ($username)
	{
		$this->username = $username;
	}
	
	function setPassword ($password)
	{
		$this->password = $password;
	}
	
	function setDatabase ($database)
	{
		$this->database = $database;
	}
	
	function setMasterHost ($master)
	{
		$this->SS_masterMySQLserver = $master;
	}
	
	function setSlaveHosts ($slaves = array())
	{
		$this->SS_MySQLservers = $slaves;
	}
	
	function setReplication ($replication)
	{
		$this->replication = $replication;
	}
	
	function setVars ($username, $password, $database, $master, $slave = array(), $replication='')
	{
		$this->setUsername($username);
		$this->setPassword($password);
		$this->setDatabase($database);
		$this->setMasterHost($master);
		$this->setSlaveHosts($slave);
		$this->setReplication($replication);
	}
}
?>