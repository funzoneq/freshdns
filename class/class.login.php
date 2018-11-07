<?php
class login
{
	private $database;
	public $token;

	function __construct ($database)
	{
		if (session_id() == "") session_start();
		if (empty($_SESSION['token'])) {
			if (function_exists('mcrypt_create_iv')) {
				$_SESSION['token'] = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
			} else {
				$_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
			}
		}

		$this->database = $database;
	}

	function xsrfCheck() {
		if ($_POST['xsrf_token'] !== $_SESSION['token']) {
			http_response_code(403);
			throw new Exception("XSRF Token missing or mismatch");
		}
	}
	
	function isLoggedIn ()
	{
		if(isset($_SESSION['loggedIn'], $_SESSION['userId'], $_SESSION['username'], $_SESSION['level']))
		{
			$query = "SELECT id FROM users WHERE username=? AND level=? AND id=? AND active='1'";
			$query = $this->database->query_slave($query, [ $_SESSION['username'], $_SESSION['level'], $_SESSION['userId'] ]);
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
		global $u2f;
		$query = "SELECT id, fullname, level, password, u2fdata FROM users WHERE username=? AND password=? AND active='1'";
		$query = $this->database->query_slave($query, [ $username, md5($password) ]);
		
		if($this->database->num_rows($query)!=1)
		{
			throw new Exception ("NoUserFound");
		}else
		{
			$record = $this->database->fetch_row($query);
			$_SESSION['userId']	= $record['id'];
			$_SESSION['fullname']	= $record['fullname'];
			$_SESSION['level']	= $record['level'];
			$_SESSION['username']	= $username;
			$_SESSION['password']	= $record['password'];
			if ($record['u2fdata'] != NULL) {
				$u2fdata = json_decode($record['u2fdata']);
				if ($u2fdata) {
					//var_dump($u2fdata);
					$data = $u2f->getAuthenticateData($u2fdata);

					$_SESSION['authReq'] = json_encode($data);
					return array("status"=>"success","text"=>"Please touch your U2F token...", 'u2f_challenge' => array('challenge'=>$data, 'username'=>$username));
				}
			}
			$_SESSION['loggedIn'] = true;
			
			return array("status" => "success", "text" => "Welcome, you have been logged in.", "reload" => "yes");
		}
	}

	function checkU2fSignature($username, $response) {
		global $u2f;
		$authReq = json_decode($_SESSION['authReq']);
		$_SESSION['authReq'] = NULL;
		if ($username !== $_SESSION['username']) throw new Exception("InvalidRequest");
		$query = "SELECT u2fdata FROM users WHERE username=? AND active='1'";
		$query = $this->database->query_slave($query, [ $username ]);

		if($this->database->num_rows($query)!=1) {
			throw new Exception ("NoUserFound");
		}else{
			$record = $this->database->fetch_row($query);
			if ($record['u2fdata'] != NULL) {
				$u2fdata = json_decode($record['u2fdata']);
				$data = $u2f->doAuthenticate($authReq, $u2fdata, json_decode($response));
				foreach($u2fdata as &$i) {
					if ($i->id == $data->id) $i->counter = $data->counter;
				}
				$this->database->updateModel('users', [ 'username' => $username ],
											[ 'u2fdata' => json_encode($u2fdata) ]);

				$_SESSION['loggedIn'] = true;
				return TRUE;
			}
		}
		throw new Exception("LoginError");
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
