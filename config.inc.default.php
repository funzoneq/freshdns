<?php
if (session_id() == "") session_start();

/*****************************************************/

define('PATH', 			'./');
define('CLASSES',		PATH.'class/');
define('VERSION',		'1.0.3');

/*****************************************************/

$config['DB']['username']					= 'username';
$config['DB']['password']					= 'password';	
$config['DB']['master_dsn']					= 'mysql:dbname=pdns;host=127.0.0.1';
$config['DB']['slave_dsns']					= array('mysql:dbname=pdns;host=127.0.0.1','mysql:dbname=pdns;host=127.0.0.1'); // DO NOT USE UNLESS YOU KNOW WHAT YOU ARE DOING!
$config['DB']['use_replication']				= 0;	// DO NOT USE UNLESS YOU KNOW WHAT YOU ARE DOING!

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
include_once(CLASSES.'class.PDO.php');
include_once(CLASSES.'class.manager.php');
include_once(CLASSES.'class.login.php');
include_once(CLASSES.'class.JSON.php');

/*****************************************************/

$config['database'] = new PDO_DB();
$config['database']->setVars($config['DB']['username'], $config['DB']['password'], $config['DB']['master_dsn'], $config['DB']['slave_dsns'], $config['DB']['use_replication']);
$config['database']->initiate();
?>
