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

if(php_sapi_name() == 'cli') {
	$overids = json_decode($argv[1]);

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
	    die('100');
	}else{
		for($i = 0; $i < count($overids); $i++) {
      $query = mysqli_query($con, "SELECT * FROM vncp_nodes WHERE name = '".$overids[$i]->node."' LIMIT 1");
      if(!$query) {
        die('200');
      }else{
        $node_data = mysqli_fetch_array($query);
				$pxAPI = new PVE2_API($node_data['hostname'], $node_data['username'], $node_data['realm'], decryptValue($node_data['password']));
				$pxAPI->login();
        $getdata = $pxAPI->get('/pools/'.$overids[$i]->pool_id);
        $vmid = $getdata['members'][0]['vmid'];
        $pxAPI->post('/nodes/'.$overids[$i]->node.'/'.$overids[$i]->ct_type.'/'.$vmid.'/status/stop', array());
        if($overids[$i]->ct_type == 'lxc') {
          $query = mysqli_query($con, "UPDATE vncp_lxc_ct SET suspended=1 WHERE hb_account_id=".$overids[$i]->hb_account_id);
          if(!$query) {
            die('300');
          }
        }else{
          $query = mysqli_query($con, "UPDATE vncp_kvm_ct SET suspended=1 WHERE hb_account_id=".$overids[$i]->hb_account_id);
          if(!$query) {
            die('400');
          }
        }
				$query = mysqli_query($con, "UPDATE vncp_bandwidth_monitor SET suspended=1 WHERE hb_account_id=".$overids[$i]->hb_account_id);
				if(!$query) {
					die('500');
				}
      }
    }
		mysqli_close($con);
	}
}
?>
