<?php
class PDO_DB {
	private $master;
	private $slave;
	private $NRslaveQ;
	private $NRmasterQ;
	private $slaveDSNs;
	private $selectedSlaveDSN;
	private $masterDSN;
	private $pdoOptions;
	private $replication;
	private $username;
	private $password;
	
	/*****************************************************/
	
	function __construct () {
		if (session_id() == "") session_start();
		
		// REPLICATION STANDARD OFF
		$this->replication = 0;
		$this->pdoOptions = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];
	}
	
	function __destruct () {
	}
	
	/*****************************************************/
	
	function initiate ()
	{
		// PICK RANDOM READER AND SAVE IT (PERSISTENT)
		if(!isset($_SESSION['SS_DSN'])){
			$aantal = count($this->slaveDSNs)-1;
			$_SESSION['SS_DSN'] = rand(0,$aantal);
		}
		
		// SELECT SERVER (SLAVE)
		$this->selectedSlaveDSN = $this->slaveDSNs[$_SESSION['SS_DSN']];
		
		// CONNECT TO MYSQL
		$this->setstats();
		$this->connect_to_mysql();
	}
	
	function connect_to_mysql (){
		// CONNECT TO A SLAVE
		$this->slave = new PDO($this->selectedSlaveDSN, $this->username, $this->password, $this->pdoOptions);
	}
	
	function connect_to_mysql_master(){
		if($this->replication==1)
		{
			// CONNECT TO THE MASTER
			$this->slave = new PDO($this->masterDSN, $this->username, $this->password, $this->pdoOptions);
		}else
		{
			// ONLY CONNECT ONCE!
			$this->master = $this->slave;
		}
	}
	
	
	/*****************************************************/
	
	function query_slave($query, $parameters)
	{
		if(!$this->slave){
			$this->connect_to_mysql();
		}
		
		$this->NRslaveQ++;
		$statement = $this->slave->prepare($query);
		if ($statement->execute($parameters)) {
			return $statement;
		}
	}
	
	function query_master($query, $parameters)
	{
		if(!$this->master){
			$this->connect_to_mysql_master();
		}
		
		$this->NRmasterQ++;
		$statement = $this->slave->prepare($query);
		if ($statement->execute($parameters)) {
			return $statement;
		}
	}
	
	function error ()
	{
		return $this->master->errorCode();
	}
	
	function fetch_row($query)
	{
		return $query->fetch(PDO::FETCH_ASSOC);
	}
	function fetch_all($query)
	{
		return $query->fetchAll(PDO::FETCH_ASSOC);
	}
	function num_rows ($query)
	{
		return $query->rowCount();
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
	
	function setMasterDSN ($master)
	{
		$this->masterDSN = $master;
	}
	
	function setSlaveDSNs ($slaves = array())
	{
		$this->slaveDSNs = $slaves;
	}
	
	function setReplication ($replication)
	{
		$this->replication = $replication;
	}
	
	function updateModel($tableName, $idFields, $fieldsToUpdate, $exactlyOne=TRUE) {
		$query = "UPDATE `$tableName` ";
		$queryArgs = [];
		$queryParts = [];
		foreach($fieldsToUpdate as $k => $v) {
			$queryParts[] = "`$k` = ?";
			$queryArgs[] = $v;
		}
		$query .= " SET " . implode(", ", $queryParts);
		
		$queryParts = [];
		foreach($idFields as $k => $v) {
			$queryParts[] = "`$k` = ?";
			$queryArgs[] = $v;
		}
		$query .= " WHERE " . implode(" AND ", $queryParts);
		if ($exactlyOne) $query .= " LIMIT 1;";
		$res = $this->query_master($query, $queryArgs);
		if ($exactlyOne && $res->rowCount() != 1) throw new Exception("update Model failed - ".$res->rowCount()." matches");
		return $res;
	}
	
	function deleteModel($tableName, $idFields, $exactlyOne=TRUE) {
		$query = "DELETE FROM `$tableName` WHERE ";
		$queryArgs = [];
		$queryParts = [];
		foreach($idFields as $k => $v) {
			$queryParts[] = "`$k` = ?";
			$queryArgs[] = $v;
		}
		$query .= implode(" AND ", $queryParts);
		if ($exactlyOne) $query .= " LIMIT 1;";
		$res = $this->query_master($query, $queryArgs);
		if ($exactlyOne && $res->rowCount() != 1) throw new Exception("delete Model failed - ".$res->rowCount()." matches");
		return $res;
	}
	
	function createModel($tableName, $fieldsToUpdate) {
		$query = "INSERT INTO `$tableName` SET ";
		$queryArgs = [];
		$queryParts = [];
		foreach($fieldsToUpdate as $k => $v) {
			$queryParts[] = "`$k` = ?";
			$queryArgs[] = $v;
		}
		$query .= implode(", ", $queryParts);
		$res = $this->query_master($query, $queryArgs);
		return $this->master->lastInsertId();
	}
	
	function setVars ($username, $password, $master, $slave = array(), $replication='')
	{
		$this->setUsername($username);
		$this->setPassword($password);
		$this->setMasterDSN($master);
		$this->setSlaveDSNs($slave);
		$this->setReplication($replication);
	}

	function beginTransaction() {
		if(!$this->master){
			$this->connect_to_mysql_master();
		}
		$this->master->beginTransaction();
	}
	function commit() {
		$this->master->commit();
	}
	function rollBack() {
		$this->master->rollBack();
	}
}
