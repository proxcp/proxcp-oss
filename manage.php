<?php
define('constant', TRUE);
require_once('vendor/autoload.php');
use phpseclib\Net\SSH2;
require_once('core/autoload.php');
require_once('core/init.php');
require_once('core/session.php');
if(Config::get('instance/installed') == false) {
    Redirect::to('install');
}else{
    $connection = mysqli_connect(Config::get('database/host'), Config::get('database/username'), Config::get('database/password'));
    mysqli_select_db($connection, Config::get('database/db'));
    $user = new User();
    if(!$user->isLoggedIn()) {
        Redirect::to('login');
    }
}
$db = DB::getInstance();
$log = new Logger();

$appname = $db->get('vncp_settings', array('item', '=', 'app_name'))->first()->value;
$gid = false;
$vid = false;
if(isset($_GET['id']) && isset($_GET['virt'])) {
  $gid = true;
  $vid = true;
}
$isAdmin = $user->hasPermission('admin');
$results = $db->get('vncp_lxc_ct', array('user_id', '=', $user->data()->id));
$data = $results->all();
$vmtable = array(
  'lxc' => array(),
  'kvm' => array()
);
$noLogin = false;
for($i = 0; $i < count($data); $i++) {
  $noLogin = false;
  if($data[$i]->suspended == 0) {
    $nodename = $data[$i]->node;
    $node_results = $db->get('vncp_nodes', array('name', '=', $nodename));
    $node_data = $node_results->first();
    $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
    if(!$pxAPI->login()) $noLogin = true;
    if($noLogin == false) {
      $vminfo = $pxAPI->get('/pools/'.$data[$i]->pool_id);
      $info = $pxAPI->get('/nodes/'.$data[$i]->node.'/lxc/'.$vminfo['members'][0]['vmid'].'/status/current');
      $vmtable['lxc'][] = array(
        'noLogin' => false,
        'hbid' => $data[$i]->hb_account_id,
        'name' => $info['name'],
        'ip' => $data[$i]->ip
      );
    }else{
      $vmtable['lxc'][] = array(
        'noLogin' => true,
        'hbid' => $data[$i]->hb_account_id,
        'ip' => $data[$i]->ip
      );
    }
  }
}
$results = $db->get('vncp_kvm_ct', array('user_id', '=', $user->data()->id));
$data = $results->all();
for($i = 0; $i < count($data); $i++) {
  $noLogin = false;
  if($data[$i]->suspended == 0) {
    $nodename = $data[$i]->node;
    $node_results = $db->get('vncp_nodes', array('name', '=', $nodename));
    $node_data = $node_results->first();
    $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
    if(!$pxAPI->login()) $noLogin = true;
    if($noLogin == false) {
      $vminfo = $pxAPI->get('/pools/'.$data[$i]->pool_id);
      if(count($vminfo['members']) == 1) {
        $info = $pxAPI->get('/nodes/'.$data[$i]->node.'/qemu/'.$vminfo['members'][0]['vmid'].'/status/current');
        $vmtable['kvm'][] = array(
          'noLogin' => false,
          'hbid' => $data[$i]->hb_account_id,
          'name' => $info['name'],
          'ip' => $data[$i]->ip
        );
      }else{
        for($j = 0; $j < count($vminfo['members']); $j++) {
          if($vminfo['members'][$j]['name'] == $data[$i]->cloud_hostname) {
            $info = $pxAPI->get('/nodes/'.$data[$i]->node.'/qemu/'.$vminfo['members'][$j]['vmid'].'/status/current');
            $vmtable['kvm'][] = array(
              'noLogin' => false,
              'hbid' => $data[$i]->hb_account_id,
              'name' => $info['name'],
              'ip' => $data[$i]->ip
            );
          }
        }
      }
    }else{
      $vmtable['kvm'][] = array(
        'noLogin' => true,
        'hbid' => $data[$i]->hb_account_id,
        'ip' => $data[$i]->ip
      );
    }
  }
}
$include_tpl = 'vmtable.tpl';
if(isset($_GET['virt']) && $_GET['virt'] == 'lxc') {
  if(isset($_GET['id'])) {
    $results = $db->get('vncp_lxc_ct', array('hb_account_id', '=', escape($_GET['id'])));
    $data = $results->first();
    if($data->user_id == $user->data()->id) {
      $include_tpl = 'lxcvm.tpl';
    }
  }
}else if(isset($_GET['virt']) && $_GET['virt'] == 'kvm') {
  if(isset($_GET['id'])) {
    $results = $db->get('vncp_kvm_ct', array('hb_account_id', '=', escape($_GET['id'])));
    $data = $results->first();
    if($data->user_id == $user->data()->id) {
      $include_tpl = 'kvmvm.tpl';
    }
  }
}

$enable_firewall = escape($db->get('vncp_settings', array('item', '=', 'enable_firewall'))->first()->value);
$enable_forward_dns = escape($db->get('vncp_settings', array('item', '=', 'enable_forward_dns'))->first()->value);
$enable_reverse_dns = escape($db->get('vncp_settings', array('item', '=', 'enable_reverse_dns'))->first()->value);
$enable_notepad = escape($db->get('vncp_settings', array('item', '=', 'enable_notepad'))->first()->value);
$enable_status = escape($db->get('vncp_settings', array('item', '=', 'enable_status'))->first()->value);
$constants = false;
if(defined('constant') || defined('constant-fw')) {
    $constants = true;
}
$aclsetting = $db->get('vncp_settings', array('item', '=', 'user_acl'))->first()->value;

$L = new Language($user->data()->language);
$L = $L->load();
if(!$L) {
	$log->log('Could not load language ' . $user->data()->language, 'error', 2, $user->data()->username, $_SERVER['REMOTE_ADDR']);
	die('Language "'.$user->data()->language.'" not found.');
}

echo $twig->render('manage.tpl', [
  'appname' => $appname,
  'gid' => $gid,
  'vid' => $vid,
  'base' => Config::get('instance/base'),
  'vmtable' => $vmtable,
  'include_tpl' => $include_tpl,
  'adminBase' => Config::get('admin/base'),
  'enable_firewall' => $enable_firewall,
  'enable_forward_dns' => $enable_forward_dns,
  'enable_reverse_dns' => $enable_reverse_dns,
  'enable_notepad' => $enable_notepad,
  'enable_status' => $enable_status,
  'isAdmin' => $isAdmin,
  'constants' => $constants,
  'username' => $user->data()->username,
  'aclsetting' => $aclsetting,
  'L' => $L
]);

if($include_tpl == 'lxcvm.tpl') {
  require_once('lxcvm.php');
}else if($include_tpl == 'kvmvm.tpl') {
  require_once('kvmvm.php');
}else{
  require_once('vmtable.php');
}

if(isset($GLOBALS['proxcp_branding']) && !empty($GLOBALS['proxcp_branding'])) echo $GLOBALS['proxcp_branding'];
echo '</div>
</div>
</div>
<input type="hidden" value="'.Session::get('user').'" id="user" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>window.jQuery || document.write(\'<script src="js/vendor/jquery-1.11.2.min.js"><\/script>\')</script>
<script src="js/vendor/bootstrap.min.js"></script>
<script src="js/main.js"></script>
<script src="js/buttons.js"></script>
<script src="js/io.js"></script>';
if($_GET['virt'] == 'lxc') {
  echo '<script src="js/lxc.js"></script>';
}else if($_GET['virt'] == 'kvm') {
  echo '<script src="js/kvm.js"></script>';
}
echo '</body>
</html>';
?>
