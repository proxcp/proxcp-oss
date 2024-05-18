<?php
require_once(dirname(__FILE__, 2) . '/lib/pve2_api.class.php');
require_once(dirname(__FILE__, 2) . '/lib/MagicCrypt.php');
use org\magiclen\magiccrypt\MagicCrypt;

function loadEncryptionKeyFromConfig() {
	$load = file_get_contents(dirname(__FILE__, 2) . '/config.js');
	$pos = strrpos($load, "vncp_secret_key");
	$pos = substr($load, $pos);
	$pos = explode("'", $pos)[1];
	$key = explode('.', $pos)[0];
	$iv = explode('.', $pos)[1];
	return array($key, $iv);
}
function decryptValue($ciphertext) {
	$load = loadEncryptionKeyFromConfig();
	$key = $load[0];
	$iv = $load[1];
	$mc = new MagicCrypt($key, 256, $iv);
	return $mc->decrypt($ciphertext);
}

function getVMIDs($con, $all_pools, $all_nodes) {
	if(count($all_pools) > 0 && count($all_nodes) > 0 && count($all_pools) == count($all_nodes)) {
		$vmids = array();
		for($i = 0; $i < count($all_pools); $i++) {
			$query = mysqli_query($con, "SELECT * FROM vncp_nodes WHERE name = '".mysqli_real_escape_string($con, $all_nodes[$i])."' LIMIT 1");
			if(!$query) {
				die('100');
			}else{
				$node_data = mysqli_fetch_array($query);
				$pxAPI = new PVE2_API($node_data['hostname'], $node_data['username'], $node_data['realm'], decryptValue($node_data['password']));
				$pxAPI->login();
				$pxdata = $pxAPI->get('/pools/'.$all_pools[$i]);
				array_push($vmids, $pxdata['members'][0]['vmid']);
			}
		}
		if(count($vmids) == count($all_pools)) {
			return $vmids;
		}
	}
	return false;
}
function getTraffic($con, $vmids, $all_nodes, $all_types) {
	if(count($vmids) > 0 && count($all_nodes) > 0 && count($vmids) == count($all_nodes)) {
		$netouts = array();
		for($i = 0; $i < count($vmids); $i++) {
			$query = mysqli_query($con, "SELECT * FROM vncp_nodes WHERE name = '".mysqli_real_escape_string($con, $all_nodes[$i])."' LIMIT 1");
			if(!$query) {
				die('200');
			}else{
				$node_data = mysqli_fetch_array($query);
				$pxAPI = new PVE2_API($node_data['hostname'], $node_data['username'], $node_data['realm'], decryptValue($node_data['password']));
				$pxAPI->login();
				$pxdata = $pxAPI->get('/nodes/'.$all_nodes[$i].'/'.$all_types[$i].'/'.$vmids[$i].'/status/current');
				array_push($netouts, $pxdata['netout']);
			}
		}
		if(count($netouts) == count($vmids)) {
			return $netouts;
		}
	}
	return false;
}

if(php_sapi_name() == 'cli') {
	$all_pools = json_decode($argv[1]);
	$all_nodes = json_decode($argv[2]);
	$all_types = json_decode($argv[3]);
	$all_current = json_decode($argv[4]);

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

	$con = mysqli_connect($sqlhost, $sqluser, $sqlpw);
	mysqli_select_db($con, $sqldb);
	if(!$con) {
	    die('300');
	}else{
		$vmids = getVMIDs($con, $all_pools, $all_nodes);
		if($vmids) {
			$netouts = getTraffic($con, $vmids, $all_nodes, $all_types);
			if($netouts) {
				for($i = 0; $i < count($netouts); $i++) {
					$new_current = '';
					if(((int)$netouts[$i] == 0) && ((int)$all_current[$i] == 0)) {
						$new_current = 0;
					}else if(((int)$netouts[$i] == 0) && ((int)$all_current[$i] != 0)) {
						$new_current = (int)$all_current[$i];
					}else if((int)$netouts[$i] > (int)$all_current[$i]) {
						$new_current = (int)$all_current[$i] + ((int)$netouts[$i] - (int)$all_current[$i]);
					}else if((int)$netouts[$i] < (int)$all_current[$i]) {
						$new_current = (int)$all_current[$i] + (int)$netouts[$i];
					}else{
						// equal
						$new_current = (int)$all_current[$i];
					}
					$query = mysqli_query($con, "UPDATE vncp_bandwidth_monitor SET current = ".(int)$new_current." WHERE pool_id = '".mysqli_real_escape_string($con, $all_pools[$i])."'");
					if(!$query) {
						die('400');
					}
				}
			}else{
				die('500');
			}
		}else{
			die('600');
		}
		mysqli_close($con);
	}
}
?>
