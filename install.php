<?php
switch($_GET['p'])
{
	default:
		echo '<form method="post" action="install.php?p=do_install">
		<table>
		  <tr>
		    <td>Database type:</td>
			<td><select name="db_type">
			<option value="mysql">MySQL</option>
			<option value="postgresql">PostgreSQL</option>
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
	
	case "do_install":
		echo 'Copy the following <b>over</b> the original in config.inc.php<br /><br />
		
		<b>Database settings</b>
		
		<textarea name="configsettings" cols="150" rows="15">
$config[\''.$_POST['db_type'].'\'][\'use\']						= true;
$config[\''.$_POST['db_type'].'\'][\'username\']					= \''.$_POST['db_user'].'\';
$config[\''.$_POST['db_type'].'\'][\'password\']					= \''.$_POST['db_pass'].'\';	
$config[\''.$_POST['db_type'].'\'][\'database\']					= \''.$_POST['db_base'].'\';
$config[\''.$_POST['db_type'].'\'][\'master_host\']					= \''.$_POST['db_host'].'\';
$config[\''.$_POST['db_type'].'\'][\'slave_hosts\']					= array(\''.$_POST['db_host'].'\',\''.$_POST['db_host'].'\'); // DO NOT USE UNLESS YOU KNOW WHAT YOU ARE DOING!
$config[\''.$_POST['db_type'].'\'][\'use_replication\']				= \'0\';	// DO NOT USE UNLESS YOU KNOW WHAT YOU ARE DOING!
</textarea>
		
		<textarea name="configsettings2" cols="150" rows="10">
$config[\'DNS\'][\'ns0\']						= \''.$_POST['ns0'].'\';
$config[\'DNS\'][\'ns1\']						= \''.$_POST['ns1'].'\';
$config[\'DNS\'][\'ns2\']						= \''.$_POST['ns2'].'\';
$config[\'DNS\'][\'hostmaster\']					= \''.$_POST['hostmaster'].'\';
		</textarea>
		
		Done? Save and upload the editted file. <a href="install.php?p=install_db">Next step</a>';
	break;
	
	case "install_db":
		include_once("config.inc.php");
		include_once("./class/class.install.php");
		
		$install = new install ($config['database']);
		$install->pdns_sql();
		$install->padmin_sql();
		
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
		include_once("./class/class.install.php");
		
		try
		{
			$_SESSION['level'] = 10;
			
			$manager = new manager ($config['database']);
			$install = new install ($config['database']);
			$userId = $manager->addUser ($_POST['username'], md5($_POST['password']), $_POST['fullname'], $_POST['email'], $_POST['description'], 10, 1);
			
			// LET'S FIND SOME ZONELESS DOMAINS!
			$records = $install->zonelessdomains();
			foreach($records AS $r)
			{
				// ADD A ZONE FOR THAT SILLY DOMAIN
				$manager->addZone($r['id'], $userId, "");
			}
			
			session_destroy();
			
			echo "Admin account added. <b><font color=\"red\">Please remove install.php and ./class/class.install.php from the webserver!</font></b><br /><br />
			Installation done!";
		}catch(Exception $ex)
		{
			echo $ex->getMessage();
		}
	break;
}
?>
