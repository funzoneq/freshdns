<?php
// ALL BROWSER CACHE MUST DIE!
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

include_once('./config.inc.php');

$login = new login($config['database']);
$json = new Services_JSON();
$manager = new manager($config['database']);

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

			echo '<script type="text/javascript" language="JavaScript1.2">loginForm();</script>';

			include('./templates/footer.tpl.php');
			break;

		case "doLogin":
			try
			{
				$login->login($_POST['username'], $_POST['password']);

				$return = array("status" => "success", "text" => "Welcome, you have been logged in.", "reload" => "yes");
				echo $json->encode($return);
			}catch(Exception $ex)
			{
				$return = array("status" => "failed", "text" => $ex->getMessage());
				echo $json->encode($return);
			}
			break;

		case "phpinfo":
			phpinfo();
			break;
	}
}else
{
	switch ($_GET['p'])
	{
		default:
		case "frontpage":
			include('./templates/header.tpl.php');

			//echo '<div id="list"></div>';

			if(!isset($_GET['q']))
			{
				echo '<script type="text/javascript" language="JavaScript1.2">list(\'A\');</script>';
			}else
			{
				echo '<script type="text/javascript" language="JavaScript1.2">liveSearchStart();</script>';
			}

			include('./templates/footer.tpl.php');
			break;

		case "livesearch":
			$records = $manager->searchDomains($_GET['q']);
			echo $json->encode($records);
			break;

		case "letterlist":
			$records = $manager->getListByLetter(strtolower($_GET['letter']));
			echo $json->encode($records);
			break;

		case "ownerslist":
			$records = $manager->getListByOwner($_GET['userId']);
			echo $json->encode($records);
			break;

		case "deleteZone":
			if(!$_GET['domainId'])
			{
				$return = array("status" => "failed", "text" => "There was no domainId recieved");
				echo $json->encode($return);
			}else
			{
				try
				{
					$manager->removeAllRecords($_GET['domainId']);
					$manager->removeZoneByDomainId($_GET['domainId']);
					$manager->removeDomain($_GET['domainId']);

					$return = array("status" => "success", "text" => "The zone has been deleted.");
					echo $json->encode($return);
				}catch(Exception $ex)
				{
					$return = array("status" => "failed", "text" => $ex->getMessage());
					echo $json->encode($return);
				}
			}
			break;

		case "getDomainInfo":
			if(!$_GET['domainId'])
			{
				$return = array("status" => "failed", "text" => "There was no domainId recieved");
				echo $json->encode($return);
				exit;
			}

			$domain = $manager->getDomain($_GET['domainId']);
			$records = $manager->getAllRecords($_GET['domainId']);

			$return = array('domain' => $domain, 'records' => $records);

			echo $json->encode($return);

			unset($domain, $records);
			break;

		case "saveRecord":
			try
			{
				$manager->updateRecord($_POST['recordId'], $_POST['recordId'], $_POST['domainId'], $_POST['name'], $_POST['type'], $_POST['content'], $_POST['ttl'], $_POST['prio'], time());

				$return = array("status" => "success", "text" => "The record has been updated.");
				echo $json->encode($return);
			}catch (Exception $ex)
			{
				$return = array("status" => "failed", "text" => $ex->getMessage());
				echo $json->encode($return);
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

				$return = array("status" => "success", "text" => "All records have been updated.");
				echo $json->encode($return);
			}catch (Exception $ex)
			{
				$return = array("status" => "failed", "text" => $ex->getMessage());
				echo $json->encode($return);
			}
			break;

		case "removeRecord":
			try
			{
				$manager->removeRecord($_GET['recordId'], $_GET['domainId']);

				$return = array("status" => "success", "text" => "The record has been deleted.");
				echo $json->encode($return);
			}catch (Exception $ex)
			{
				$return = array("status" => "failed", "text" => $ex->getMessage());
				echo $json->encode($return);
			}
			break;

		case "newRecord":
			try
			{
				$manager->addRecord ($_POST['domainId'], $_POST['name'], $_POST['type'], $_POST['content'], $_POST['ttl'], $_POST['prio'], $_POST['changeDate']);

				$return = array("status" => "success", "text" => "The record has been added.");
				echo $json->encode($return);
			}catch (Exception $ex)
			{
				$return = array("status" => "failed", "text" => $ex->getMessage());
				echo $json->encode($return);
			}
			break;

		case "transferDomain":
			try
			{
				$manager->transferDomain($_POST['domainId'], $_POST['owner']);

				$return = array("status" => "success", "text" => "The domain has been transfered.");
				echo $json->encode($return);
			}catch (Exception $ex)
			{
				$return = array("status" => "failed", "text" => $ex->getMessage());
				echo $json->encode($return);
			}
			break;

		case "getOwners":
			try
			{
				$return = $manager->getAllOwners();
				echo $json->encode($return);
			}catch (Exception $ex)
			{
				$return = array("status" => "failed", "text" => $ex->getMessage());
				echo $json->encode($return);
			}
			break;

		case "getTemplates":
			$return = array();

			foreach($config['DNS']['templates'] AS $name => $values)
			{
				$return[] = $name;
			}

			echo $json->encode($return);
			break;

		case "newDomain":
			try
			{
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
				$manager->setSoaSerial ($domainId, $config['DNS']['ns0'], $config['DNS']['hostmaster'], $manager->createNewSoaSerial());

				$return = array("status" => "success", "text" => "The domain has been added.");
				echo $json->encode($return);
			}catch (Exception $ex)
			{
				$return = array("status" => "failed", "text" => $ex->getMessage());
				echo $json->encode($return);
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
			echo $json->encode($return);
			break;

		case "deleteUser":
			try
			{
				$manager->removeUserData($_POST['userId']);
				$manager->removeUser($_POST['userId']);

				$return = array("status" => "success", "text" => "All user data has been deleted.");
				echo $json->encode($return);
			}catch (Exception $ex)
			{
				$return = array("status" => "failed", "text" => $ex->getMessage());
				echo $json->encode($return);
			}
			break;

		case "getUser":
			echo $json->encode($manager->getUser($_POST['userId']));
			break;

		case "editUser":
			try
			{
				$manager->updateUser($_POST['userId'], $_POST['userId'], $_POST['username'], $_POST['password'], $_POST['fullname'], $_POST['email'], $_POST['description'], $_POST['level'], $_POST['active'], $_POST['maxdomains']);

				$return = array("status" => "success", "text" => "The user has been editted.");
				echo $json->encode($return);
			}catch (Exception $ex)
			{
				$return = array("status" => "failed", "text" => $ex->getMessage());
				echo $json->encode($return);
			}
			break;

		case "addUser":
			try
			{
				$manager->addUser($_POST['username'], md5($_POST['password']), $_POST['fullname'], $_POST['email'], $_POST['description'], $_POST['level'], $_POST['active'], $_POST['maxdomains']);

				$return = array("status" => "success", "text" => "The user has been added.");
				echo $json->encode($return);
			}catch (Exception $ex)
			{
				$return = array("status" => "failed", "text" => $ex->getMessage());
				echo $json->encode($return);
			}
			break;

		case "phpinfo":
			phpinfo();
			break;

		case "logout":
			$login->logout();
			echo '<script>window.location.href="index.php";</script>';
			break;
	}
}

unset($json, $manager);
?>
