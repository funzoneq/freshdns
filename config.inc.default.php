<?php
if (session_id() == "") session_start();

/*****************************************************/

define('PATH', 			'./');
define('CLASSES',		PATH.'class/');
define('VERSION',		'1.0.3');

/*****************************************************/

$config['mysql']['use']							= true;
$config['mysql']['username']					= 'username';
$config['mysql']['password']					= 'password';	
$config['mysql']['database']					= 'pdns';
$config['mysql']['master_host']					= 'localhost';
$config['mysql']['slave_hosts']					= array('localhost','localhost'); // DO NOT USE UNLESS YOU KNOW WHAT YOU ARE DOING!
$config['mysql']['use_replication']				= 0;	// DO NOT USE UNLESS YOU KNOW WHAT YOU ARE DOING!


/*****************************************************/

$config['postgresql']['use']					= false;
$config['postgresql']['username']				= 'username';
$config['postgresql']['password']				= 'password';	
$config['postgresql']['database']				= 'pdns';
$config['postgresql']['master_host']			= 'localhost';
$config['postgresql']['slave_hosts']			= array('localhost','localhost'); // DO NOT USE UNLESS YOU KNOW WHAT YOU ARE DOING!
$config['postgresql']['use_replication']		= 0;	// DO NOT USE UNLESS YOU KNOW WHAT YOU ARE DOING!

/*****************************************************/

$config['DNS']['ns0']							= 'ns0.example.com';
$config['DNS']['ns1']							= 'ns1.example.com';
$config['DNS']['ns2']							= 'ns2.example.com';
$config['DNS']['hostmaster']					= 'hostmaster@example.com';

/*****************************************************/

$config['DNS']['templates']['standardRecords'] = array( # DO NOT CHANGE, UNLESS YOU KNOW WHAT YOU ARE DOING!
array("name" => "{#DOMAIN#}",			"type" => "SOA",		"content" => "{#NS0#} {#HOSTMASTER#} {#SOACODE#}",	"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",			"type" => "NS",			"content" => "{#NS0#}",								"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",			"type" => "NS",			"content" => "{#NS1#}",								"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",			"type" => "NS",			"content" => "{#NS2#}",								"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",			"type" => "A",			"content" => "{#WEBIP#}",							"prio" => "0",	"ttl" => "3600"),
array("name" => "localhost.{#DOMAIN#}", "type" => "A",          "content" => "127.0.0.1",                           "prio" => "0",  "ttl" => "3600"),
array("name" => "*.{#DOMAIN#}",			"type" => "A",			"content" => "{#WEBIP#}",							"prio" => "0",	"ttl" => "3600"),
array("name" => "www.{#DOMAIN#}",		"type" => "A",			"content" => "{#WEBIP#}",							"prio" => "0",	"ttl" => "3600"),
array("name" => "mail.{#DOMAIN#}",		"type" => "A",			"content" => "{#MAILIP#}",							"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",			"type" => "MX",			"content" => "mail.{#DOMAIN#}",						"prio" => "10", "ttl" => "3600"));

$config['DNS']['templates']['minimal'] = array( # DO NOT CHANGE, UNLESS YOU KNOW WHAT YOU ARE DOING!
array("name" => "{#DOMAIN#}",			"type" => "SOA",		"content" => "{#NS0#} {#HOSTMASTER#} {#SOACODE#}",	"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",			"type" => "NS",			"content" => "{#NS0#}",								"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",			"type" => "NS",			"content" => "{#NS1#}",								"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",			"type" => "NS",			"content" => "{#NS2#}",								"prio" => "0",	"ttl" => "3600"),
array("name" => "*.{#DOMAIN#}",			"type" => "A",			"content" => "{#WEBIP#}",							"prio" => "0",	"ttl" => "3600"),
array("name" => "{#DOMAIN#}",			"type" => "MX",			"content" => "mail.{#DOMAIN#}",						"prio" => "10", "ttl" => "3600"));

$config['DNS']['templates']['googleapps'] = array( # DO NOT CHANGE, UNLESS YOU KNOW WHAT YOU ARE DOING!
array("name" => "{#DOMAIN#}",			"type" => "SOA",        "content" => "{#NS0#} {#HOSTMASTER#} {#SOACODE#}",  "prio" => "0",  "ttl" => "3600"),
array("name" => "{#DOMAIN#}",           "type" => "NS",         "content" => "{#NS0#}",                             "prio" => "0",  "ttl" => "3600"),
array("name" => "{#DOMAIN#}",           "type" => "NS",         "content" => "{#NS1#}",                             "prio" => "0",  "ttl" => "3600"),
array("name" => "{#DOMAIN#}",           "type" => "NS",         "content" => "{#NS2#}",                             "prio" => "0",  "ttl" => "3600"),
array("name" => "{#DOMAIN#}",           "type" => "A",          "content" => "{#WEBIP#}",                           "prio" => "0",  "ttl" => "3600"),
array("name" => "*.{#DOMAIN#}",         "type" => "A",          "content" => "{#WEBIP#}",                           "prio" => "0",  "ttl" => "3600"),
array("name" => "www.{#DOMAIN#}",       "type" => "A",          "content" => "{#WEBIP#}",                           "prio" => "0",  "ttl" => "3600"),
array("name" => "localhost.{#DOMAIN#}", "type" => "A",          "content" => "127.0.0.1",                           "prio" => "0",  "ttl" => "3600"),
array("name" => "{#DOMAIN#}",           "type" => "MX",         "content" => "ASPMX.L.GOOGLE.COM",                  "prio" => "1", "ttl" => "3600"),
array("name" => "{#DOMAIN#}",           "type" => "MX",         "content" => "ALT1.ASPMX.L.GOOGLE.COM",             "prio" => "5", "ttl" => "3600"),
array("name" => "{#DOMAIN#}",           "type" => "MX",         "content" => "ALT2.ASPMX.L.GOOGLE.COM",             "prio" => "5", "ttl" => "3600"),
array("name" => "{#DOMAIN#}",           "type" => "MX",         "content" => "ASPMX2.GOOGLEMAIL.COM",               "prio" => "10", "ttl" => "3600"),
array("name" => "{#DOMAIN#}",           "type" => "MX",         "content" => "ASPMX3.GOOGLEMAIL.COM",               "prio" => "10", "ttl" => "3600"),
array("name" => "{#DOMAIN#}",           "type" => "MX",         "content" => "ASPMX4.GOOGLEMAIL.COM",               "prio" => "10", "ttl" => "3600"),
array("name" => "{#DOMAIN#}",           "type" => "MX",         "content" => "ASPMX5.GOOGLEMAIL.COM",               "prio" => "10", "ttl" => "3600"));

/*****************************************************/

include_once(CLASSES.'class.database.php');
include_once(CLASSES.'class.mysql.php');
include_once(CLASSES.'class.postgresql.php');
include_once(CLASSES.'class.manager.php');
include_once(CLASSES.'class.login.php');
include_once(CLASSES.'class.xmlcreator.php');
include_once(CLASSES.'class.JSON.php');

/*****************************************************/
		
if ($config['postgresql']['use']==false && $config['mysql']['use']==true)
{
	$config['database'] = new mysql();
	$config['database']->setVars($config['mysql']['username'], $config['mysql']['password'], $config['mysql']['database'], $config['mysql']['master_host'], $config['mysql']['slave_hosts'], $config['mysql']['use_replication']);
	$config['database']->initiate();
}else if ($config['postgresql']['use']==true && $config['mysql']['use']==false)
{
	$config['database'] = new postgresql();
	$config['database']->setVars($config['postgresql']['username'], $config['postgresql']['password'], $config['postgresql']['database'], $config['postgresql']['master_host'], $config['postgresql']['slave_hosts'], $config['postgresql']['use_replication']);
	$config['database']->initiate();
}else
{
	echo 'You have encountered an error: You did not select the right database in config.inc.php';
	exit;
}
?>
