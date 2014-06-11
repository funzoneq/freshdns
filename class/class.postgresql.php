<?php
// THEORATICALLY THIS SHOULD WORK?!

class postgresql extends database {
	private $master;
	private $slave;
	private $NRslaveQ;
	private $NRmasterQ;
	private $PGserver;
	private $masterPGserver;
	private $PGservers;
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
		$this->disconnect_pg();
		unset($this->PGserver, $this->masterPGserver, $this->NRmasterQ, $this->NRslaveQ);
		unset($this->username, $this->password, $this->database, $this->replication);
	}
	
	/*****************************************************/
	
	function initiate ()
	{
		// PICK RANDOM READER AND SAVE IT (PERSISTENT)
		if(!isset($_SESSION['PGserver'])){
			$aantal = count($this->PGservers)-1;
			$_SESSION['PGserver'] = rand(0,$aantal);
		}
		
		// SET MYSQL SERVER (SLAVE)
		$this->PGserver = $this->PGservers[$_SESSION['PGserver']];
		
		// CONNECT TO MYSQL
		$this->setstats();
		if(!$this->connect_to_pg()){
			throw new Exception("No mysql connection was made");
		}
	}
	
	function connect_to_pg (){
		// CONNECT TO A SLAVE
		$this->slave = @pg_connect("host=".$this->masterPGserver." dbname=".$this->database." user=".$this->username." password=".$this->password) or die (pg_last_error());
		
		if(!$this->slave){
			throw new Exception("No slave connection");
			return FALSE;
		}else{
			return @pg_options($this->master);
		}
	}
	
	function connect_to_pg_master(){
		if($this->replication==1)
		{
			// CONNECT TO THE MASTER
			$this->master = @pg_connect("host=".$this->masterPGserver." dbname=".$this->database." user=".$this->username." password=".$this->password) or die (pg_last_error());
		
			if(!$this->master){
				throw new Exception("No master connection");
				return FALSE;
			}else{
				return @pg_options($this->master);
			}
		}else
		{
			// ONLY CONNECT ONCE!
			$this->master = $this->slave;
		}
	}
	
	function disconnect_pg (){
		@pg_close($this->master);
		@pg_close($this->slave);
	}
	
	/*****************************************************/
	
	function query_slave($query){
		if(!$this->slave){
			$this->connect_to_pg();
		}
		
		$this->NRslaveQ++;
		$query = pg_query($query, $this->slave);
		return $query;
	}
	
	function query_master($query){
		if(!$this->master){
			$this->connect_to_pg_master();
		}
		
		$this->NRmasterQ++;
		$query = pg_query($query, $this->master);
		return $query;
	}
	
	function error ()
	{
		return pg_last_error();
	}
	
	function fetch_array($query)
	{
		return pg_fetch_array($query);
	}
	
	function num_rows ($query)
	{
		return pg_num_rows($query);
	}
	
	function escape_string ($string)
	{
		return pg_escape_string($string);
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
		$this->masterPGserver = $master;
	}
	
	function setSlaveHosts ($slaves = array())
	{
		$this->PGservers = $slaves;
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
