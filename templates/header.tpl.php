<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" dir="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <title>FreshDNS</title>
	<script>var baseurl = '';</script>
	<script src="./js/prototype.js"></script>
	<script src="vendor/jquery-2.1.4.min.js"></script>
	<script src="./js/freshdns.js"></script>
	<script type="text/javascript">
	var liveSearchRoot = baseurl;
	var liveSearchRootSubDir = "/";
	<?php
	echo "var userlevel='".$_SESSION['level']."';\n";
	echo "var myUserId='".$_SESSION['userId']."';\n";
	echo "var xsrf_token='".$_SESSION['token']."';\n";
	?>
	</script>
	<link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="./images/style.css" type="text/css" />
</head>

<body>

<div id="query"></div>

<div id="header" class="navbar navbar-static-top navbar-default">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?= dirname($_SERVER["PHP_SELF"]) ?>"><img id="logo" src="./images/logo.png" alt="FreshDNS" /> FreshDNS</a>
    </div>


    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <?php if($login->isLoggedIn()){ ?>
      <ul class="nav navbar-nav">
	<li><a href="javascript:newDomain();">New Domain</a></li>
	<li><a href="javascript:bulkNewDomain();">Bulk Create</a></li>
	<?php if($_SESSION['level'] < 10) { ?>
	<?php }else{ ?>
	<li data-navigate-list=""><a href="#">All domains</a> </li>
	<li><a href="javascript:userAdmin();">Users</a> </li>
	<?php } ?>

      </ul>

	<ul class="nav navbar-nav pull-right">
	  <li><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><span class="glyphicon glyphicon-user"></span> <?= $_SESSION[ "username" ]?> <span class="caret"></span></a>
          <ul class="dropdown-menu" role="menu">
	    <li><a href="javascript:editUser(<?php echo $_SESSION['userId']; ?>);">Profile</a> </li>
            <li><a href="index.php?p=logout">Logout</a></li>
          </ul></li>
	</ul>

	<form name="searchform" method="get" action="index.php" id="searchform" class="navbar-form navbar-right" role="search">
        <div class="form-group">
	<input id="livesearch" name="q" type="text" onkeypress="liveSearchStart()" value="<?php echo $_GET['q']; ?>" class="form-control" placeholder="Search">
	</div></form>

	<?php } ?>

    </div>

  </div>
</div>

<div class="container">
<div class="row"><div class="col-sm-12">
	<ul class="pagination" id="letters">
	<?php if($login->isLoggedIn()){ ?>
	<li data-navigate-list="[0-9]"><a href="#">0-9</a></li>
	<?php for($i=0x41;$i<=0x40+26; $i++) { ?> <li data-navigate-list="<?=chr($i)?>"><a href="#"><?= chr($i)?></a></li><?php } ?>
	<?php } ?>
	</ul>

<div id="message"></div>

<div id="body">

