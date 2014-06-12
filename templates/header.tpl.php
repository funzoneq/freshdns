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
	<?php
	echo "var userlevel='".$_SESSION['level']."';\n";
	echo "var myUserId='".$_SESSION['userId']."';\n";
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
	<div id="letters">
	<?php if($login->isLoggedIn()){ ?>
	<a href="javascript:list('[0-9]');">0-9</a> <a href="javascript:list('a');">A</a> <a href="javascript:list('b');">B</a> <a href="javascript:list('c');">C</a>
	<a href="javascript:list('d');">D</a> <a href="javascript:list('e');">E</a> <a href="javascript:list('f');">F</a> <a href="javascript:list('g');">G</a> <a href="javascript:list('h');">H</a>
	<a href="javascript:list('i');">I</a> <a href="javascript:list('j');">J</a> <a href="javascript:list('k');">K</a> <a href="javascript:list('l');">L</a> <a href="javascript:list('m');">M</a>
	<a href="javascript:list('n');">N</a> <a href="javascript:list('o');">O</a> <a href="javascript:list('p');">P</a> <a href="javascript:list('q');">Q</a> <a href="javascript:list('r');">R</a>
	<a href="javascript:list('s');">S</a> <a href="javascript:list('t');">T</a> <a href="javascript:list('u');">U</a> <a href="javascript:list('v');">V</a> <a href="javascript:list('w');">W</a>
	<a href="javascript:list('x');">X</a> <a href="javascript:list('y');">Y</a> <a href="javascript:list('z');">Z</a> <a href="javascript:newDomain();">NEW</a> <a href="javascript:bulkNewDomain();">BULK</a>
	<?php if($_SESSION['level'] < 10) { ?>
	<a href="javascript:editUser(<?php echo $_SESSION['userId']; ?>);">PROFILE</a> 
	<?php }else{ ?>
	<a href="javascript:userAdmin();">USERS</a> 
	<?php } ?>
	<a href="index.php?p=logout">LOGOUT</a>
	<?php }else{ echo "&nbsp;"; } ?></div>
	<div id="search">
	<?php if($login->isLoggedIn()){ ?>
	<form style="margin:0px;" name="searchform" method="get" action="index.php" id="searchform">
	<input id="livesearch" name="q" type="text" onkeypress="liveSearchStart()" value="<?php echo $_GET['q']; ?>"></form>
	<?php } ?></div>
</div>

<div id="body">
