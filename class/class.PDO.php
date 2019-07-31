<?php

class DBRaw {
	public $value;
	public $operator;
	public function __construct($v, $op = "=") {
		$this->value = $v;
		$this->operator = $op;
	}
}
function DB_Now() { return new DBRaw("NOW()"); }

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
	
	function __construct($username, $password, $master, $slaves = array(), $replication=false)
	{
		$this->username = $username;
		$this->password = $password;
		$this->masterDSN = $master;
		$this->slaveDSNs = $slaves;
		$this->replication = $replication;
		$this->pdoOptions = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];
		if (!$replication) {
			$this->selectedSlaveDSN = $master;
			$this->connectToMysql();
		} else {
			$this->initiate();
		}
	}
	
	function __destruct () {
	}
	
	/*****************************************************/
	
	function initiate ()
	{
		// PICK RANDOM READER AND SAVE IT (PERSISTENT)
		if(!isset($_SESSION['SS_DSN'])){
			$number = count($this->slaveDSNs)-1;
			$_SESSION['SS_DSN'] = rand(0,$number);
		}
		
		// SELECT SERVER (SLAVE)
		$this->selectedSlaveDSN = $this->slaveDSNs[$_SESSION['SS_DSN']];
		
		// CONNECT TO MYSQL
		$this->setstats();
		$this->connectToMysql();
	}
	
	function connectToMysql (){
		// CONNECT TO A SLAVE
		$this->slave = new PDO($this->selectedSlaveDSN, $this->username, $this->password, $this->pdoOptions);
	}
	
	function connectToMysqlMaster(){
		if($this->replication==1)
		{
			// CONNECT TO THE MASTER
			$this->master = new PDO($this->masterDSN, $this->username, $this->password, $this->pdoOptions);
		}else
		{
			// ONLY CONNECT ONCE!
			$this->master = $this->slave;
		}
	}
	
	
	/*****************************************************/
	
	function querySlave($sqlQuery, $parameters)
	{
		if(!$this->slave){
			$this->connectToMysql();
		}
		
		$this->NRslaveQ++;
		$statement = $this->slave->prepare($sqlQuery);
		if ($statement->execute($parameters)) {
			return $statement;
		}
	}
	
	function queryMaster($sqlQuery, $parameters)
	{
		if(!$this->master){
			$this->connectToMysqlMaster();
		}
		
		$this->NRmasterQ++;
		$statement = $this->master->prepare($sqlQuery);
		if ($statement->execute($parameters)) {
			return $statement;
		}
	}
	
	function error ()
	{
		return $this->master->errorCode();
	}
	
	function fetchRow($sqlQuery, $parameters)
	{
		$stmt = $this->querySlave($sqlQuery, $parameters);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	function fetchAll($sqlQuery, $parameters)
	{
		$stmt = $this->querySlave($sqlQuery, $parameters);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	function rowCount ($stmt)
	{
		return $stmt->rowCount();
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
	
	private function buildQueryPart($fields, $joiner, &$queryArgs) {
		$queryParts = [];
		foreach($fields as $k => $v) {
			if ($v instanceof DBRaw) {
				$queryParts[] = "`$k` " . $v->operator . " " . $v->value;
			} else {
				$queryParts[] = "`$k` = ?";
				$queryArgs[] = $v;
			}
		}
		return implode($joiner, $queryParts);
	}

	function updateModel($tableName, $idFields, $fieldsToUpdate, $exactlyOne=true) {
		$query = "UPDATE `$tableName` ";
		$queryArgs = [];
		$query .= " SET " . $this->buildQueryPart($fieldsToUpdate, ", ", $queryArgs);
		$query .= " WHERE " . $this->buildQueryPart($idFields, " AND ", $queryArgs);
		if ($exactlyOne) $query .= " LIMIT 1;";
		$res = $this->queryMaster($query, $queryArgs);
		if ($exactlyOne && $res->rowCount() != 1) throw new Exception("update Model failed - ".$res->rowCount()." matches");
		return $res;
	}
	
	function deleteModel($tableName, $idFields, $exactlyOne=true) {
		$query = "DELETE FROM `$tableName` ";
		$queryArgs = [];
		$query .= " WHERE " . $this->buildQueryPart($idFields, " AND ", $queryArgs);
		if ($exactlyOne) $query .= " LIMIT 1;";
		$res = $this->queryMaster($query, $queryArgs);
		if ($exactlyOne && $res->rowCount() != 1) throw new Exception("delete Model failed - ".$res->rowCount()." matches");
		return $res;
	}
	
	function createModel($tableName, $fieldsToUpdate) {
		$query = "INSERT INTO `$tableName` ";
		$queryArgs = [];
		$query .= " SET " . $this->buildQueryPart($fieldsToUpdate, ", ", $queryArgs);
		$res = $this->queryMaster($query, $queryArgs);
		return $this->master->lastInsertId();
	}
	
	function beginTransaction() {
		if(!$this->master){
			$this->connectToMysqlMaster();
		}
		$this->master->beginTransaction();
	}
	function commit() {
		$this->master->commit();
	}
	function rollBack() {
		$this->master->rollBack();
	}

	function escape($str) {
		return $this->slave->quote($str);
	}
}
