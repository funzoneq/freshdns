<?php
switch($_GET['p'])
{
	default:
		echo '<form method="post" action="install.php?p=build_config">
		<table>
		  <tr>
		    <td>Database type:</td>
			<td><select name="db_type">
			<option value="mysql">MySQL</option>
			<option value="pgsql">PostgreSQL</option>
			</select></td>
		  </tr>
		  <tr>
		    <td>Database host:</td>
			<td><input type="text" name="db_host" value="localhost"></td>
		  </tr>
		  <tr>
		    <td>Database username:</td>
			<td><input type="text" name="db_user" value="pdns"></td>
		  </tr>
		  <tr>
		    <td>Database password:</td>
			<td><input type="text" name="db_pass" value=""></td>
		  </tr>
		  <tr>
		    <td>Database database:</td>
			<td><input type="text" name="db_base" value="pdns"></td>
		  </tr>
		  <tr>
		    <td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
		  <tr>
		    <td>Nameserver 1:</td>
			<td><input type="text" name="ns0" value="ns0.example.com"></td>
		  </tr>
		  <tr>
		    <td>Nameserver 2:</td>
			<td><input type="text" name="ns1" value="ns1.example.com"></td>
		  </tr>
		  <tr>
		    <td>Nameserver 3:</td>
			<td><input type="text" name="ns2" value="ns2.example.com"></td>
		  </tr>
		  <tr>
		    <td>Hostmaster:</td>
			<td><input type="text" name="hostmaster" value="hostmaster@example.com"></td>
		  </tr>
		  <tr>
		    <td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
		  <tr>
		    <td><input type="submit" name="submit" value="Install"></td>
			<td>&nbsp;</td>
		  </tr>
		</table></form>';
	break;
	
	case "build_config":
		include_once("./class/class.PDO.php");
		$dsn = $_POST['db_type'] . ':dbname=' . $_POST['db_base'] . ';host='.$_POST['db_host'];
		try {
			$db = new PDO_DB($_POST['db_user'], $_POST['db_pass'], $dsn, array($dsn), false);
			echo "<h3>Database connection successful</h3>";
		} catch(Exception $ex) {
			echo "<h3>Connecting to the database failed</h3><p>Please check your inputs and try again (Used DSN: <code>$dsn</code>)</p>";
			echo "<pre>$ex</pre>";
			exit;
		}

		echo 'Copy the following <b>over</b> the original in config.inc.php<br /><br />
		
		<h3>Database settings</h3>
		
		<textarea name="configsettings" cols="150" rows="15">
$config[\'DB\'][\'use\']						= true;
$config[\'DB\'][\'username\']					= \''.$_POST['db_user'].'\';
$config[\'DB\'][\'password\']					= \''.$_POST['db_pass'].'\';
$config[\'DB\'][\'master_dsn\']					= \''.$_POST['db_dsn'].'\';
$config[\'DB\'][\'slave_dsns\']					= array(\''.$_POST['db_dsn'].'\'); // DO NOT USE UNLESS YOU KNOW WHAT YOU ARE DOING!
$config[\'DB\'][\'use_replication\']				= 0;	// DO NOT USE UNLESS YOU KNOW WHAT YOU ARE DOING!
</textarea>
		
		<textarea name="configsettings2" cols="150" rows="10">
$config[\'DNS\'][\'ns0\']						= \''.$_POST['ns0'].'\';
$config[\'DNS\'][\'ns1\']						= \''.$_POST['ns1'].'\';
$config[\'DNS\'][\'ns2\']						= \''.$_POST['ns2'].'\';
$config[\'DNS\'][\'hostmaster\']					= \''.$_POST['hostmaster'].'\';
		</textarea>
		
		<h4>Done? Save and upload the edited file. <a href="install.php?p=install_db">Next step</a></h4>';
	break;
	
	case "install_db":
		require_once("config.inc.php");
		require_once("./class/class.PDO.php");
		require_once("./class/class.install.php");
		
		try {
			$config['database'] = new PDO_DB($config['DB']['username'], $config['DB']['password'], $config['DB']['master_dsn'], $config['DB']['slave_dsns'], $config['DB']['use_replication']);
		} catch(Exception $ex) {
			echo "<h3>Connecting to the database failed</h3><p>Please check your inputs and try again (Used DSN: <code>$dsn</code>)</p>";
			echo "<pre>$ex</pre>";
			exit;
		}
		try {
			$install = new install ($config['database']);
			$install->pdns_sql();
			$install->padmin_sql();
		} catch(Exception $ex) {
			echo "<h3>Database Update Failed</h3>";
			echo "<pre>$ex</pre>";
		}

		echo '<h4>Done. <a href="install.php?p=add_admin_form">Next step</a></h4>';
		break;
	case "add_admin_form":
		include_once("config.inc.php");
		include_once("./class/class.PDO.php");
		
		$db = new PDO_DB($config['DB']['username'], $config['DB']['password'], $config['DB']['master_dsn'], $config['DB']['slave_dsns'], $config['DB']['use_replication']);

		if (count($db->fetchAll("SELECT username FROM users WHERE `level` >= 10;", [ ])) > 0) {
			echo "<h3>An admin user does already exist. Refusing to continue with the installer.</h3>";
			exit;
		}
		echo '<form method="post" action="install.php?p=do_add_admin">
		<table>
		  <tr>
		    <td>Username:</td>
			<td><input type="text" name="username" value=""></td>
		  </tr>
		  <tr>
		    <td>Password:</td>
			<td><input type="password" name="password" value=""></td>
		  </tr>
		  <tr>
		    <td>Name:</td>
			<td><input type="text" name="fullname" value=""></td>
		  </tr>
		  <tr>
		    <td>E-mail address:</td>
			<td><input type="text" name="email" value=""></td>
		  </tr>
		  <tr>
		    <td>Description:</td>
			<td><input type="text" name="description" value=""></td>
		  </tr>
		  <tr>
		    <td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
		  <tr>
		    <td><input type="submit" name="submit" value="Create admin account"></td>
			<td>&nbsp;</td>
		  </tr>
		</table></form>';
	break;
	
	case "do_add_admin":
		include_once("config.inc.php");
		include_once("./class/class.PDO.php");
		include_once("./class/class.manager.php");
		include_once("./class/class.install.php");
		
		$db = new PDO_DB($config['DB']['username'], $config['DB']['password'], $config['DB']['master_dsn'], $config['DB']['slave_dsns'], $config['DB']['use_replication']);

		if (count($db->fetchAll("SELECT username FROM users WHERE `level` >= 10;", [ ])) > 0) {
			echo "<h3>An admin user does already exist. Refusing to continue with the installer.</h3>";
			exit;
		}
		try
		{
			$_SESSION['level'] = 10;
			
			$manager = new manager ($db);
			$install = new install ($db);
			$userId = $manager->addUser ($_POST['username'], $_POST['password'], $_POST['fullname'], $_POST['email'], $_POST['description'], 10, 1, 0);
			echo "Admin account added. <br>";
		}catch(Exception $ex)
		{
			echo "<h3>Failed to create admin user</h3>".$ex->getMessage();
			exit;
		}
		try
		{
			// LET'S FIND SOME ZONELESS DOMAINS!
			$records = $install->zonelessdomains();
			foreach($records AS $r)
			{
				echo "<li>Adding new admin account to domain #".$r['id'];
				// ADD A ZONE FOR THAT SILLY DOMAIN
				$manager->addZone($r['id'], $userId, "");
			}
			
			session_destroy();
			
		}catch(Exception $ex)
		{
			echo "<h3>Failed to add zones to unassigned domains</h3>".$ex->getMessage();
			exit;
		}
		echo "<li><b><font color=\"red\">Please remove install.php and ./class/class.install.php from the webserver!</font></b><br /><br />
		Installation done!";
	break;
}
?>
