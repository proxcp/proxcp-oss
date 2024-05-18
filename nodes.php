<?php
require_once('vendor/autoload.php');
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

$appname = $db->get('vncp_settings', array('item', '=', 'app_name'))->first()->value;
$statussetting = $db->get('vncp_settings', array('item', '=', 'enable_status'))->first()->value;

$nodesdata = array();
$results = $db->get('vncp_nodes', array('id', '!=', 0));
$data = $results->all();
$noLogin = false;
foreach($data as $n) {
  $noLogin = false;
  try{
    $pxAPI = new PVE2_API($n->hostname, $n->username, $n->realm, decryptValue($n->password));
  }catch(Exception $e) {
		echo "ProxCP Exception:<br><br>";
		echo $e->getMessage();
	}
  if(!$pxAPI->login()) $noLogin = true;
  if($noLogin == false) {
    $nodes = $pxAPI->get('/nodes');
    $nIndex = 0;
    for($r = 0; $r < count($nodes); $r++) {
      if($nodes[$r]['node'] == $n->name) {
        $nIndex = $r;
        break;
      }
    }
    $percent = round((float)$nodes[$nIndex]['cpu'] * 100) . '%';
    if($nodes[0]['uptime'] != 0) {
      $nodesdata[] = array(
        'noLogin' => false,
        'status' => 'online',
        'name' => $nodes[$nIndex]['node'],
        'cpu' => $n->cpu,
        'percent' => $percent,
        'uptime' => read_time($nodes[$nIndex]['uptime'])
      );
    }else{
      $nodesdata[] = array(
        'noLogin' => false,
        'status' => 'offline',
        'name' => $nodes[$nIndex]['node'],
        'cpu' => $n->cpu
      );
    }
  }else{
    $nodesdata[] = array(
      'noLogin' => true,
      'name' => $n->name,
      'cpu' => $n->cpu
    );
  }
}

$enable_firewall = escape($db->get('vncp_settings', array('item', '=', 'enable_firewall'))->first()->value);
$enable_forward_dns = escape($db->get('vncp_settings', array('item', '=', 'enable_forward_dns'))->first()->value);
$enable_reverse_dns = escape($db->get('vncp_settings', array('item', '=', 'enable_reverse_dns'))->first()->value);
$enable_notepad = escape($db->get('vncp_settings', array('item', '=', 'enable_notepad'))->first()->value);
$isAdmin = $user->hasPermission('admin');
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

echo $twig->render('nodes.tpl', [
  'appname' => $appname,
  'statussetting' => $statussetting,
  'nodes' => $nodesdata,
  'adminBase' => Config::get('admin/base'),
  'enable_firewall' => $enable_firewall,
  'enable_forward_dns' => $enable_forward_dns,
  'enable_reverse_dns' => $enable_reverse_dns,
  'enable_notepad' => $enable_notepad,
  'enable_status' => $statussetting,
  'isAdmin' => $isAdmin,
  'constants' => $constants,
  'username' => $user->data()->username,
  'aclsetting' => $aclsetting,
  'pagename' => 'Node Status',
  'L' => $L
]);

echo '<input type="hidden" value="'.Session::get('user').'" id="user" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>window.jQuery || document.write(\'<script src="js/vendor/jquery-1.11.2.min.js"><\/script>\')</script>
<script src="js/vendor/bootstrap.min.js"></script>
<script src="//cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="js/main.js"></script>
<script src="js/buttons.js"></script>
<script src="js/io.js"></script>
<script type="text/javascript">$("#userstatustable").DataTable();</script>
</body>
</html>';
?>
