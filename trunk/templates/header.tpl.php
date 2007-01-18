<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" dir="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <title>FreshDNS</title>
	<script type="text/javascript" language="JavaScript1.2">var baseurl = '';</script>
	<script type="text/javascript" language="JavaScript1.2" src="./js/prototype.js"> </script>
	<script type="text/javascript" language="JavaScript1.2" src="./js/freshdns.js"> </script>
	<script type="text/javascript">
	var liveSearchRoot = baseurl;
	var liveSearchRootSubDir = "/";
	<?
	echo "var userlevel='".$_SESSION['level']."';\n";
	?>
	</script>
	<link rel="stylesheet" href="./images/style.css" type="text/css" />
</head>

<body>

<div id="query"></query>

<div id="header">
	<div id="logo"><img src="./images/logo.png" alt="FreshDNS" /></div>
	
	<div id="headingtext">
		<div id="title">FreshDNS</div>
	</div>
</div>

<div id="navbar">
	<div id="letters"><a href="javascript:list('[0-9]');">0-9</a> <a href="javascript:list('A');">A</a> <a href="javascript:list('B');">B</a> <a href="javascript:list('C');">C</a>
	<a href="javascript:list('D');">D</a> <a href="javascript:list('E');">E</a> <a href="javascript:list('F');">F</a> <a href="javascript:list('G');">G</a> <a href="javascript:list('H');">H</a>
	<a href="javascript:list('I');">I</a> <a href="javascript:list('J');">J</a> <a href="javascript:list('K');">K</a> <a href="javascript:list('L');">L</a> <a href="javascript:list('M');">M</a>
	<a href="javascript:list('N');">N</a> <a href="javascript:list('O');">O</a> <a href="javascript:list('P');">P</a> <a href="javascript:list('Q');">Q</a> <a href="javascript:list('R');">R</a>
	<a href="javascript:list('S');">S</a> <a href="javascript:list('T');">T</a> <a href="javascript:list('U');">U</a> <a href="javascript:list('V');">V</a> <a href="javascript:list('W');">W</a>
	<a href="javascript:list('X');">X</a> <a href="javascript:list('Y');">Y</a> <a href="javascript:list('Z');">Z</a> <a href="javascript:newDomain();">NEW</a> <a href="javascript:userAdmin();">USERS</a></div>
	<div id="search"><form style="margin:0px;" name="searchform" method="get" action="index.php" id="searchform">
	<input id="livesearch" name="q" type="text" onkeypress="liveSearchStart()" value="<?=$_GET['q']?>"></form></div>
</div>

<div id="body">
