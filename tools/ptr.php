<?php
$stdptr = 'hosted.by.example.com';
$iprange = '127.0.0.0'; // we anticipate a /24

$config['DNS']['templates']['ipv4ptr'] = array( # DO NOT CHANGE, UNLESS YOU KNOW WHAT YOU ARE DOING!
array("name" => "{#DOMAIN#}",		"type" => "SOA",	"content" => "{#NS0#} {#HOSTMASTER#} {#SOACODE#}",	"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",		"type" => "NS",		"content" => "{#NS0#}",								"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",		"type" => "NS",		"content" => "{#NS1#}",								"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",		"type" => "NS",		"content" => "{#NS2#}",								"prio" => "0",	"ttl" => "3600"));

/* DO NOT EDIT BELOW THIS LINE! */
include_once("../config.inc.php");

$manager = new Manager($config['database']);

$arpa = array_reverse(explode(".", $iprange));
unset($arpa[0]);
$name = implode(".", $arpa);

try {
	$domainId = $manager->addDomain ($name, '', 0, 'NATIVE', 0, '');
	$manager->addZone($domainId, 1, "");
	
	foreach($config['DNS']['templates']['ipv4ptr'] AS $record)
	{
		$record['name']		= str_replace("{#DOMAIN#}",		$_POST['domain'],				$record['name']);
		$record['content']	= str_replace("{#DOMAIN#}",		$_POST['domain'],				$record['content']);
		$record['content']	= str_replace("{#NS0#}",		$config['DNS']['ns0'],			$record['content']);
		$record['content']	= str_replace("{#NS1#}",		$config['DNS']['ns1'],			$record['content']);
		$record['content']	= str_replace("{#NS2#}",		$config['DNS']['ns2'],			$record['content']);
		$record['content']	= str_replace("{#HOSTMASTER#}", $config['DNS']['hostmaster'],	$record['content']);
		$record['content']	= str_replace("{#SOACODE#}",	$manager->createNewSoaSerial(),	$record['content']);
		$record['content']	= str_replace("{#STDPTR#}",		$stdptr,						$record['content']);
		
		$manager->addRecord ($domainId, $record['name'], $record['type'], $record['content'], $record['ttl'], $record['prio'], $record['changeDate']);
	}

	for($i=0; $i<256; $i++)
	{
		$manager->addRecord($domainId, $i.'.'.$name, 'PTR', $stdptr, 3600, 0, 0);
	}
}catch (Exception $ex)
{
	echo $ex->getMessage();
}
?>
