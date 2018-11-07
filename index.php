<?php
// ALL BROWSER CACHE MUST DIE!
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

include_once('./config.inc.php');
include_once('./class/class.U2F.php');

$login = new login($config['database']);
$json = new Services_JSON();
$manager = new manager($config['database']);

$scheme = isset($_SERVER['HTTPS']) ? "https://" : "http://";
$u2f = new u2flib_server\U2F($scheme . $_SERVER['HTTP_HOST']);

try {
	$login->isLoggedIn();
}catch(Exception $ex)
{
	$login->logout();
	header("Location: index.php");
	exit;
}

if(!$login->isLoggedIn())
// THE USER IS NOT LOGGED IN
{
	switch($_GET['p'])
	{
		default:
			include('./templates/header.tpl.php');

			echo '<div id="body">
			<div id="info"><table>
			  <tr>
				<td rowspan="2" width="70"><img id="infoimg" src="./images/info.png" alt="Welcome!" /></td>
				<td><b>Welcome to FreshDNS<span id="infoHead"></span></b></td>
			  </tr>
			  <tr>
				<td rowspan="2">FreshDNS is a webbased, PHP and AJAX powered DNS-administration tool for powerDNS</td>
			  </tr>
			</table></div>
			<div class="leeg">&nbsp;</div>
			<div id="login"></div></div>';

			echo "<script src='./js/u2f-api.js'></script>";
			echo '<script type="text/javascript" language="JavaScript1.2">loginForm();</script>';

			include('./templates/footer.tpl.php');
			break;

		case "doLogin":
			try
			{
				$return = $login->login($_POST['username'], $_POST['password']);
				$json->print_json($return);
			}catch(Exception $ex)
			{
				$json->print_exception($ex);
			}
			break;

		case "checkU2f":
			try {
				$login->checkU2fSignature($_POST['username'], $_POST['auth']);
				$return = array("status" => "success", "text" => "Welcome, you have been logged in.", "reload" => "yes");
				$json->print_json($return);
			}catch(Exception $ex)
			{
				$json->print_exception($ex);
			}
			break;


	}
}else
{
	switch ($_GET['p'])
	{
		default:
		case "frontpage":
			include('./templates/header.tpl.php');

			if(!isset($_GET['q']))
			{
				//echo '<script type="text/javascript" language="JavaScript1.2">list(\'\');</script>';
				echo '<script> ownersList(myUserId) </script>';
			}else
			{
				echo '<script type="text/javascript" language="JavaScript1.2">liveSearchStart();</script>';
			}

			echo "<script src='./js/u2f-api.js'></script>";
			include('./templates/footer.tpl.php');
			break;

		case "livesearch":
			$records = $manager->searchDomains($_GET['q']);
			$json->print_json($records);
			break;

		case "letterlist":
			$records = $manager->getListByLetter(strtolower($_GET['letter']));
			$json->print_json($records);
			break;

		case "ownerslist":
			$records = $manager->getListByOwner($_GET['userId']);
			$json->print_json($records);
			break;

		case "deleteZone":
			if(!$_POST['domainId'])
			{
				$return = array("status" => "failed", "text" => "There was no domainId recieved");
				$json->print_json($return);
			}else
			{
				try
				{
					$manager->removeAllRecords($_POST['domainId']);
					$manager->removeZoneByDomainId($_POST['domainId']);
					$manager->removeDomain($_POST['domainId']);

					$return = array("status" => "success", "text" => "The zone has been deleted.");
					$json->print_json($return);
				}catch(Exception $ex)
				{
					$json->print_exception($ex);
				}
			}
			break;

		case "getDomainInfo":
			if(!$_GET['domainId'])
			{
				$return = array("status" => "failed", "text" => "There was no domainId recieved");
				$json->print_json($return);
				exit;
			}

			$domain = $manager->getDomain($_GET['domainId']);
			$records = $manager->getAllRecords($_GET['domainId']);

			$return = array('domain' => $domain, 'records' => $records);

			$json->print_json($return);

			unset($domain, $records);
			break;

		case "saveRecord":
			try
			{
				$manager->updateRecord($_POST['recordId'], $_POST['recordId'], $_POST['domainId'], $_POST['name'], $_POST['type'], $_POST['content'], $_POST['ttl'], $_POST['prio'], time());
				
				touch("/opt/powerdns_copy/last_change");
				
				$return = array("status" => "success", "text" => "The record has been updated.");
				$json->print_json($return);
			}catch (Exception $ex)
			{
				$json->print_exception($ex);
			}
			break;

		case "saveAllRecords":
			try
			{
				foreach($_POST['id'] AS $rowId => $recordId)
				{
					$manager->updateRecord ($recordId, $recordId, $_POST['domainId'], $_POST['name'][$rowId], $_POST['type'][$rowId], $_POST['content'][$rowId], $_POST['ttl'][$rowId], $_POST['prio'][$rowId], time(), false);
				}

				$manager->updateSoaSerial($_POST['domainId']);

				touch("/opt/powerdns_copy/last_change");

				$return = array("status" => "success", "text" => "All records have been updated.");
				$json->print_json($return);
			}catch (Exception $ex)
			{
				$json->print_exception($ex);
			}
			break;

		case "removeRecord":
			try
			{
				$manager->removeRecord($_POST['recordId'], $_POST['domainId']);
				touch("/opt/powerdns_copy/last_change");

				$return = array("status" => "success", "text" => "The record has been deleted.");
				$json->print_json($return);
			}catch (Exception $ex)
			{
				$json->print_exception($ex);
			}
			break;

		case "newRecord":
			try
			{
				$manager->addRecord ($_POST['domainId'], $_POST['name'], $_POST['type'], $_POST['content'], $_POST['ttl'], $_POST['prio'], $_POST['changeDate']);
				touch("/opt/powerdns_copy/last_change");

				$return = array("status" => "success", "text" => "The record has been added.");
				$json->print_json($return);
			}catch (Exception $ex)
			{
				$json->print_exception($ex);
			}
			break;

		case "transferDomain":
			try
			{
				$manager->transferDomain($_POST['domainId'], $_POST['owner']);

				$return = array("status" => "success", "text" => "The domain has been transfered.");
				$json->print_json($return);
			}catch (Exception $ex)
			{
				$json->print_exception($ex);
			}
			break;

		case "getOwners":
			try
			{
				$return = $manager->getAllOwners();
				$json->print_json($return);
			}catch (Exception $ex)
			{
				$json->print_exception($ex);
			}
			break;

		case "getTemplates":
			$return = array();

			foreach($config['DNS']['templates'] AS $name => $values)
			{
				$return[] = $name;
			}

			$json->print_json($return);
			break;

		case "newDomain":
			try
			{
				$manager->database->beginTransaction();
				$domainId = $manager->addDomain(trim($_POST['domain']), $_POST['master'], $_POST['lastCheck'], $_POST['type'], $_POST['notifiedSerial'], $_POST['account']);
				$manager->addZone($domainId, $_POST['owner'], "");

				foreach($config['DNS']['templates'][$_POST['template']] AS $record)
				{
					$record['name']		= str_replace("{#DOMAIN#}",		$_POST['domain'],				$record['name']);
					$record['content']	= str_replace("{#DOMAIN#}",		$_POST['domain'],				$record['content']);
					$record['content']	= str_replace("{#NS0#}",		$config['DNS']['ns0'],			$record['content']);
					$record['content']	= str_replace("{#NS1#}",		$config['DNS']['ns1'],			$record['content']);
					$record['content']	= str_replace("{#NS2#}",		$config['DNS']['ns2'],			$record['content']);
					$record['content']	= str_replace("{#WEBIP#}",		$_POST['webIP'],				$record['content']);
					$record['content']	= str_replace("{#MAILIP#}",		$_POST['mailIP'],				$record['content']);
					$record['content']	= str_replace("{#HOSTMASTER#}", $config['DNS']['hostmaster'],	$record['content']);
					$record['content']	= str_replace("{#SOACODE#}",	$manager->createNewSoaSerial(),	$record['content']);

					$manager->addRecord ($domainId, $record['name'], $record['type'], $record['content'], $record['ttl'], $record['prio'], $record['changeDate']);
				}

				// IT'S A NEW DOMAIN, RESET THE SOA TO 00
				$manager->setSoaSerial ($domainId, $config['DNS']['ns0'], $config['DNS']['hostmaster'], $manager->createNewSoaSerial(), 3600, 1800, 3600000, 172800);

				$manager->database->commit();
				$return = array("status" => "success", "text" => "The domain has been added.");
				$json->print_json($return);
			}catch (Exception $ex)
			{
				$manager->database->rollBack();
				$json->print_exception($ex);
			}
			break;

		case "newDomains":
			$domains = explode("\n", $_POST['domains']);
			$succes = array();
			$failed = array();

			foreach($domains AS $domain)
			{
				try
				{
					$domainId = $manager->addDomain(trim($domain), $_POST['master'], $_POST['lastCheck'], $_POST['type'], $_POST['notifiedSerial'], $_POST['account']);
					$manager->addZone($domainId, $_POST['owner'], "");

					foreach($config['DNS']['templates'][$_POST['template']] AS $record)
					{
						$record['name']		= str_replace("{#DOMAIN#}",		$domain,						$record['name']);
						$record['content']	= str_replace("{#DOMAIN#}",		$domain,						$record['content']);
						$record['content']	= str_replace("{#NS0#}",		$config['DNS']['ns0'],			$record['content']);
						$record['content']	= str_replace("{#NS1#}",		$config['DNS']['ns1'],			$record['content']);
						$record['content']	= str_replace("{#NS2#}",		$config['DNS']['ns2'],			$record['content']);
						$record['content']	= str_replace("{#WEBIP#}",		$_POST['webIP'],				$record['content']);
						$record['content']	= str_replace("{#MAILIP#}",		$_POST['mailIP'],				$record['content']);
						$record['content']	= str_replace("{#HOSTMASTER#}", $config['DNS']['hostmaster'],	$record['content']);
						$record['content']	= str_replace("{#SOACODE#}",	$manager->createNewSoaSerial(),	$record['content']);

						$manager->addRecord ($domainId, $record['name'], $record['type'], $record['content'], $record['ttl'], $record['prio'], $record['changeDate']);
					}

					$succes[] = "The domain ".$domain." has been added.";

				}catch (Exception $ex)
				{
					$failed[] = "Failed: The domain ".$domain." hasn't been added: ".$ex->getMessage()."\n";
				}
			}

			$return = array("status" => "success", "text" => implode("\n", $succes)."\n".implode("\n", $failed));
			$json->print_json($return);
			break;

		case "deleteUser":
			try
			{
				$manager->removeUserData($_POST['userId']);
				$manager->removeUser($_POST['userId']);

				$return = array("status" => "success", "text" => "All user data has been deleted.");
				$json->print_json($return);
			}catch (Exception $ex)
			{
				$json->print_exception($ex);
			}
			break;

		case "getUser":
			$json->print_json($manager->getUser($_POST['userId']));
			break;

		case "editUser":
			try
			{
				$manager->updateUser($_POST['userId'], $_POST['userId'], $_POST['username'], $_POST['password'], $_POST['fullname'], $_POST['email'], $_POST['description'], $_POST['level'], $_POST['active'], $_POST['maxdomains'], $_POST['u2fdata']);

				$return = array("status" => "success", "text" => "The user has been editted.");
				$json->print_json($return);
			}catch (Exception $ex)
			{
				$json->print_exception($ex);
			}
			break;

		case "addUser":
			try
			{
				$manager->addUser($_POST['username'], md5($_POST['password']), $_POST['fullname'], $_POST['email'], $_POST['description'], $_POST['level'], $_POST['active'], $_POST['maxdomains']);

				$return = array("status" => "success", "text" => "The user has been added.");
				$json->print_json($return);
			}catch (Exception $ex)
			{
				$json->print_exception($ex);
			}
			break;

		case "logout":
			$login->logout();
			echo '<script>window.location.href="index.php";</script>';
			break;
	}
}

unset($json, $manager);
?>
