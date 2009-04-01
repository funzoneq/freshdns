<?php
$domainId = '2181';
$content = 'hosted.by.example.com';
$iprange = '127.0.0.0'; // we anticipate a /24

/* DO NOT EDIT BELOW THIS LINE! */
include_once("../config.inc.php");

$manager = new Manager($config['database']);

$arpa = array_reverse(explode(".", $iprange));
unset($arpa[0]);
$name = implode(".", $arpa);

for($i=0; $i<256; $i++)
{
	$manager->addRecord($domainId, $i.'.'.$name, 'PTR', $content, 3600, 0, 0);
}
?>
