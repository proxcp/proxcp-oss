<?php
require_once('vendor/autoload.php');
require_once('core/autoload.php');
require_once('core/init.php');
require_once('core/session.php');

$user = new User();
if(!$user->isLoggedIn()) {
  die('User not authenticated.');
}
if(!isset($_GET['id']) || !isset($_GET['virt'])) {
	die('Verification error.');
}

$db = DB::getInstance();
if($_GET['virt'] == 'lxc') {
	$results = $db->get('vncp_lxc_ct', array('hb_account_id', '=', $_GET['id']));
	$type = 'lxc';
}else if($_GET['virt'] == 'kvm') {
    $results = $db->get('vncp_kvm_ct', array('hb_account_id', '=', $_GET['id']));
    $type = 'kvm';
}
$data = $results->first();

if($data->user_id != $user->data()->id) {
	die('Verification error.');
}

$noLogin = false;
$nodename = $data->node;
$node_results = $db->get('vncp_nodes', array('name', '=', $nodename));
$node_data = $node_results->first();
$pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
if(!$pxAPI->login()) $noLogin = true;
if($noLogin == false) {
    $vminfo = $pxAPI->get('/pools/' . $data->pool_id);
    if(count($vminfo['members']) == 1) {
        $vmid = $vminfo['members'][0]['vmid'];
        if($data->suspended == 0) {
          $console = $pxAPI->post('/access/ticket', array(
            'username' => $data->pool_id . '@pve',
            'password' => decryptValue($data->pool_password)
          ));
          if(!$console['ticket']) {
            echo 'No data received.';
          }else{
            echo '<iframe src="https://'.$node_data->hostname.':8006/novnc/vncconsole.html?vmid='.$vmid.'&username='.urlencode($console['username']).'&host='.$node_data->hostname.'&console='.$type.'&vmname='.$vminfo['members'][0]['name'].'&node='.$node_data->name.'&ticket='.urlencode($console['ticket']).'&csrf='.urlencode($console['CSRFPreventionToken']).'&resize=scale" name="vncconsole-frame" style="overflow:hidden;height:100%;width:100%;position:absolute;top:0px;left:0px;right:0px;bottom:0px;" height="100%" width="100%" frameborder="0" allowfullscreen="true"></iframe>';
          }
        }else{
        	echo 'This VM is suspended. VNC access is not available.';
        }
    }else{
        for($j = 0; $j < count($vminfo['members']); $j++) {
            if($vminfo['members'][$j]['name'] == $data->cloud_hostname) {
                $vmid = $vminfo['members'][$j]['vmid'];
                $vmIndex = $j;
                break;
            }
        }
        if($data->suspended == 0) {
          $console = $pxAPI->post('/access/ticket', array(
            'username' => $data->pool_id . '@pve',
            'password' => decryptValue($data->pool_password)
          ));
          if(!$console['ticket']) {
            echo 'No data received.';
          }else{
            echo '<iframe src="https://'.$node_data->hostname.':8006/novnc/vncconsole.html?vmid='.$vmid.'&username='.urlencode($console['username']).'&host='.$node_data->hostname.'&console='.$type.'&vmname='.$vminfo['members'][$vmIndex]['name'].'&node='.$node_data->name.'&ticket='.urlencode($console['ticket']).'&csrf='.urlencode($console['CSRFPreventionToken']).'&resize=scale" name="vncconsole-frame" style="overflow:hidden;height:100%;width:100%;position:absolute;top:0px;left:0px;right:0px;bottom:0px;" height="100%" width="100%" frameborder="0" allowfullscreen="true"></iframe>';
          }
        }else{
            echo 'This VM is suspended. VNC access is not available.';
        }
    }
}else{
    echo 'Node cannot be reached.';
}
?>
