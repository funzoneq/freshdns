#!/usr/bin/php -q
<?php
ob_start();

$mysql_host 	= "localhost"; 	/* ENTER YOUR HOSTNAME */
$mysql_db 		= "pdns";		/* ENTER THE DATABASE NAME */
$mysql_user 	= "pdns";		/* ENTER THE MYSQL USERNAME */
$mysql_pass 	= "";			/* ENTER THE MYSQL PASSWORD */

mysql_connect("localhost", $mysql_user, $mysql_pass) or die (mysql_error());
mysql_select_db($mysql_db) or die (mysql_error());

/* LET'S DEFINE SOME ARRAY'S */
$domains = array();
$recipient = array();
$transport = array();

/* LET'S GET ALL DOMAINS */
$query = "SELECT name FROM domains WHERE name <> '' GROUP BY name ORDER BY name";
$query = mysql_query($query) or die (mysql_error());
while($record = mysql_fetch_assoc($query))
{
	$domains[] = $record['name']." OK";
	$recipient[] = "@".$record['name']." OK";
	$transport[] = $record['name']." smtp:[mail.".$record['name']."]";
}

/* LET'S SAVE THE OUTPUT */
$output = '';

/* SAVE THE RELAY DOMAINS */
file_put_contents("/etc/postfix/relay_domains", implode("\n", $domains));

/* LET'S MAKE POSTFIX UNDERSTAND THIS FILE */
exec("postmap /etc/postfix/relay_domains", $output);

/* SAVE THE RELAY RECIPIENTS */
file_put_contents("/etc/postfix/relay_recipient", implode("\n", $recipient));

/* LET'S MAKE POSTFIX UNDERSTAND THIS FILE */
exec("postmap /etc/postfix/relay_recipient", $output);

/* SAVE THE TRANSPORT */
file_put_contents("/etc/postfix/transport", implode("\n", $transport));

/* LET'S MAKE POSTFIX UNDERSTAND THIS FILE */
exec("postmap /etc/postfix/transport", $output);

/* LET'S RELOAD POSTFIX */
exec("/etc/init.d/postfix reload", $output);

/* COLLECT THE OUTPUT */
$content = ob_get_clean();
?>