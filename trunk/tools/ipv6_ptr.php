<?php
$prefix = "2001:888:2000:0013::/64";	// You're assigned IPv6 prefix
$stdptr = 'ipv6.example.com';			// The default FQDN we return when reverse DNS is looked up

$config['DNS']['templates']['ipv6ptr'] = array( # DO NOT CHANGE, UNLESS YOU KNOW WHAT YOU ARE DOING!
array("name" => "{#DOMAIN#}",		"type" => "SOA",	"content" => "{#NS0#} {#HOSTMASTER#} {#SOACODE#}",	"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",		"type" => "NS",		"content" => "{#NS0#}",								"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",		"type" => "NS",		"content" => "{#NS1#}",								"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",		"type" => "NS",		"content" => "{#NS2#}",								"prio" => "0",	"ttl" => "3600"),
array("name" => "*.{#DOMAIN#}",		"type" => "PTR",	"content" => "{#STDPTR#}",							"prio" => "0",  "ttl" => "3600"));

/* DO NOT EDIT BELOW THIS LINE! */
include_once("../config.inc.php");

$manager = new Manager($config['database']);

function generateIPv6ARPA ($prefix)
{
	$prefix = explode("::", $prefix);
	$prefix = $prefix[0];

	$parts = explode(":", $prefix);

	foreach($parts AS $key => $part)
	{
		while(strlen($part) < 4)
		{
			$part = '0'.$part;
		}
	
		$parts[$key] = $part;
	}

	$reverse = implode("", $parts);

	$arpa = "ip6.arpa";

	for($i=0; $i<strlen($reverse); $i++)
	{
		$char = substr($reverse, $i, 1);
		$arpa = $char.'.'.$arpa;
	}

	return $arpa;
}

$name = generateIPv6ARPA($prefix);

try {
	$domainId = $manager->addDomain ($name, '', 0, 'NATIVE', 0, '');
	$manager->addZone($domainId, 1, "");
	
	foreach($config['DNS']['templates']['ipv6ptr'] AS $record)
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
}catch (Exception $ex)
{
	echo $ex->getMessage();
}
?>
