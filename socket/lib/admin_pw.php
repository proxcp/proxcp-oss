<?php
function make($string, $salt = '') {
    return hash('sha256', $string . $salt);
}
function salt() {
    return uniqid(mt_rand(), true);
}

if(php_sapi_name() == 'cli') {
	$newpassword = $argv[1];
	$userid = $argv[2];

  $load = file_get_contents(dirname(__FILE__, 2) . '/config.js');
	$pos_sqlhost = strrpos($load, "sqlHost");
	$pos_sqlhost = substr($load, $pos_sqlhost);
	$sqlhost = explode("'", $pos_sqlhost)[1];
	$pos_sqluser = strrpos($load, "sqlUser");
	$pos_sqluser = substr($load, $pos_sqluser);
	$sqluser = explode("'", $pos_sqluser)[1];
	$pos_sqlpw = strrpos($load, "sqlPassword");
	$pos_sqlpw = substr($load, $pos_sqlpw);
	$sqlpw = explode("'", $pos_sqlpw)[1];
	$pos_sqldb = strrpos($load, "sqlDB");
	$pos_sqldb = substr($load, $pos_sqldb);
	$sqldb = explode("'", $pos_sqldb)[1];

	$salt = salt();
	$pw = make($newpassword, $salt);

	$con = mysqli_connect($sqlhost, $sqluser, $sqlpw);
	mysqli_select_db($con, $sqldb);
	if(!$con) {
	    die('100');
	}else{
		$query = mysqli_query($con, "UPDATE `vncp_users` SET `password` = '".$pw."', `salt` = '".$salt."' WHERE `id` = ".$userid);
		mysqli_close($con);
	}
}
?>
