<?php
class login
{
	private $database;
	
	function __construct ($database)
	{
		if (session_id() == "") session_start();
		
		$this->database = $database;
	}
	
	function isLoggedIn ()
	{
		if(isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['level']))
		{
			$query = "SELECT id FROM users WHERE username='".$this->database->escape_string($_SESSION['username'])."' AND
			password='".$this->database->escape_string($_SESSION['password'])."' AND
			level='".$this->database->escape_string($_SESSION['level'])."' AND id='".$this->database->escape_string($_SESSION['userId'])."'
			AND active='1'";
			$query = $this->database->query_slave($query);
			if($this->database->num_rows($query)!=1)
            {
            	throw new Exception ("FakeLoginFound");
				return false;
            }else
			{
				return true;
			}
		}else
		{
			return false;
		}
	}
	
	function login ($username, $password)
	{
		$query = "SELECT id, fullname, level, password FROM users WHERE username='".$this->database->escape_string($username)."' AND password='".md5($this->database->escape_string($password))."' AND active='1'";
		$query = $this->database->query_slave($query);
		
		if($this->database->num_rows($query)!=1)
		{
			throw new Exception ("NoUserFound");
		}else
		{
			$record = $this->database->fetch_array($query);
			
			$_SESSION['userId']	= $record['id'];
			$_SESSION['username']	= $record['fullname'];
			$_SESSION['level']	= $record['level'];
			$_SESSION['username']	= $username;
			$_SESSION['password']	= $record['password'];
			
			return true;
		}
	}
	
	function logout ()
	{
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/');
		}
		
		session_destroy();
	}
}
?>
