<?php
include_once("config.inc.php");

$fallbackHostname = "fallback.example.com";

$query = "SELECT *
FROM `records`
WHERE `type` = 'MX'
GROUP BY name
ORDER BY prio DESC";
$query = $config['database']->query_slave($query) or die ($config['database']->error());
while($record = $config['database']->fetch_array($query))
{
	try{
		$manager->addRecord ($record['domain_id'], $record['name'], $record['type'], $fallbackHostname, $record['ttl'], ($record['prio']+10), time());
	}catch (Exception $ex)
	{
		echo $ex->getMessage();
	}
	
	echo $record['name']."\n";
}
?>
