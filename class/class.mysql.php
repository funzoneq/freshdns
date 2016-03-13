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
                // CONNECT TO A SLAVE
                $this->slave = mysqli_connect($this->SS_MySQLserver, $this->username, $this->password) or die (mysql_error());
                
                if(!$this->slave){
                        throw new Exception("No slave connection");
                        return FALSE;
                }elseif(!mysqli_select_db ($this->slave, $this->database)){
                        throw new Exception("Failed to select the database");
                        return FALSE;
                }else{
                        return mysqli_get_host_info($this->slave);
                }
        }
        
        function connect_to_mysql_master(){
                if($this->replication==1)
                {
                        // CONNECT TO THE MASTER
                        $this->master = mysqli_connect($this->SS_masterMySQLserver, $this->username, $this->password) or die (mysql_error());
                
                        if(!$this->master){
                                return FALSE;
                        }elseif(!mysql_select_db ($this->master,$this->database)){
                                return FALSE;
                        }else{
                                return @mysql_get_host_info($this->master);
                        }
                }else
                {
                        // ONLY CONNECT ONCE!
                        $this->master = $this->slave;
                }
        }
        
        function disconnect_mysql (){
                @mysqli_close($this->master);
                @mysqli_close($this->slave);
        }
        
        /*****************************************************/
        
        function query_slave($query)
        {
                if(!$this->slave){
                        $this->connect_to_mysql();
                }
                
                $this->NRslaveQ++;
                $query = mysqli_query($this->slave, $query);
                return $query;
        }
        
        function query_master($query)
        {
                if(!$this->master){
                        $this->connect_to_mysql_master();
                }
                
                $this->NRmasterQ++;
                $query = mysqli_query($this->master,$query);
                return $query;
        }
        
        function error ()
        {
                return mysqli_error();
        }
        
        function fetch_array($query)
        {
                return mysqli_fetch_array($query);
        }
        
        function num_rows ($query)
        {
                return mysqli_num_rows($query);
        }
        
        function escape_string ($string)
        {
                return mysqli_real_escape_string($this->slave, $string);
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
                $this->NRmasterQ         = '0';
                $this->NRslaveQ  = '0';
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
