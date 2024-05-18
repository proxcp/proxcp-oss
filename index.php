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
$log = new Logger();

$enable_whmcs = $db->get('vncp_settings', array('item', '=', 'enable_whmcs'))->first()->value;
$whmcs_url = $db->get('vncp_settings', array('item', '=', 'whmcs_url'))->first()->value;
$whmcs_id = $db->get('vncp_settings', array('item', '=', 'whmcs_id'))->first()->value;
$whmcs_key = $db->get('vncp_settings', array('item', '=', 'whmcs_key'))->first()->value;
if($enable_whmcs == 'true' && Input::exists()) {
	if(Token::check(Input::get('token'))) {
		$validate = new Validate();
		$validation = $validate->check($_POST, array(
			'ticketdept' => array(
				'required' => true,
				'numonly' => true,
				'min-num' => 1
			),
			'ticketsubject' => array(
				'required' => true,
				'min' => 3,
				'max' => 100
			),
			'ticketmsg' => array(
				'required' => true,
				'min' => 2,
				'max' => 5000
			)
		));
		if($validation->passed()) {
			$openTicket = Whmcs_Api($whmcs_url, array(
				'username' => $whmcs_id,
				'password' => $whmcs_key,
				'responsetype' => 'json',
				'action' => 'OpenTicket',
				'deptid' => Input::get('ticketdept'),
				'subject' => Input::get('ticketsubject'),
				'message' => Input::get('ticketmsg'),
				'clientid' => $user->data()->id,
				'priority' => 'Medium',
				'admin' => false
			));
			if($openTicket['result'] != 'success') {
				$errors = $openTicket['result'];
			}
		}else{
			$errors = '';
			foreach($validation->errors() as $error) {
				$errors .= $error . '<br />';
			}
		}
	}
}

$appname = $db->get('vncp_settings', array('item', '=', 'app_name'))->first()->value;
$vmtable = array(
	'lxc' => array(),
	'kvm' => array()
);
$noLogin = false;
$results = $db->get('vncp_lxc_ct', array('user_id', '=', $user->data()->id));
$data = $results->all();
if(count($data) > 0) {
	$firstNode = $data[0]->node;
	$node_results = $db->get('vncp_nodes', array('name', '=', $firstNode));
	$node_data = $node_results->first();
	try{
		$pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
	}catch(Exception $e) {
		echo "ProxCP Exception:<br><br>";
		echo $e->getMessage();
	}
	if(!$pxAPI->login()) $noLogin = true;
}
for($i = 0; $i < count($data); $i++) {
	if($data[$i]->node != $firstNode) {
		$firstNode = $data[$i]->node;
		$node_results = $db->get('vncp_nodes', array('name', '=', $firstNode));
		$node_data = $node_results->first();
		try{
			$pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
		}catch(Exception $e) {
			echo "ProxCP Exception:<br><br>";
			echo $e->getMessage();
		}
		if(!$pxAPI->login()) $noLogin = true;
	}
	if($noLogin == true) {
		$log->log('Could not reach node ' . $node_data->hostname, 'error', 2, $user->data()->username, $_SERVER['REMOTE_ADDR']);
		$vmtable['lxc'][$i] = array(
			'noLogin' => true
		);
	}else if($data[$i]->suspended == 0 && $noLogin == false) {
		$vminfo = $pxAPI->get('/pools/'.$data[$i]->pool_id);
		$info = $pxAPI->get('/nodes/'.$data[$i]->node.'/lxc/'.$vminfo['members'][0]['vmid'].'/status/current');
		$vmtable['lxc'][$i] = array(
			'noLogin' => false,
			'suspended' => false,
			'status' => $info['status'],
			'name' => $info['name'],
			'ip' => escape($data[$i]->ip),
			'os' => escape($data[$i]->os),
			'maxmem' => read_bytes_size($info['maxmem'], 0),
			'maxdisk' => read_bytes_size($info['maxdisk'], 0),
			'cpus' => $info['cpus'],
			'hbid' => escape($data[$i]->hb_account_id)
		);
	}else if($data[$i]->suspended == 1){
		$vminfo = $pxAPI->get('/pools/'.$data[$i]->pool_id);
		$info = $pxAPI->get('/nodes/'.$data[$i]->node.'/lxc/'.$vminfo['members'][0]['vmid'].'/status/current');
		$vmtable['lxc'][$i] = array(
			'noLogin' => false,
			'suspended' => true,
			'status' => $info['status'],
			'name' => $info['name'],
			'ip' => escape($data[$i]->ip),
			'os' => escape($data[$i]->os),
			'maxmem' => read_bytes_size($info['maxmem'], 0),
			'maxdisk' => read_bytes_size($info['maxdisk'], 0),
			'cpus' => $info['cpus']
		);
	}
}
$results = $db->get('vncp_kvm_ct', array('user_id', '=', $user->data()->id));
$data = $results->all();
if(count($data) > 0) {
	$firstNode = $data[0]->node;
	$node_results = $db->get('vncp_nodes', array('name', '=', $firstNode));
	$node_data = $node_results->first();
	try{
		$pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
	}catch(Exception $e) {
		echo "ProxCP Exception:<br><br>";
		echo $e->getMessage();
	}
	if(!$pxAPI->login()) $noLogin = true;
}
for($i = 0; $i < count($data); $i++) {
	if($data[$i]->node != $firstNode) {
		$firstNode = $data[$i]->node;
		$node_results = $db->get('vncp_nodes', array('name', '=', $firstNode));
		$node_data = $node_results->first();
		try{
			$pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
		}catch(Exception $e) {
			echo "ProxCP Exception:<br><br>";
			echo $e->getMessage();
		}
		if(!$pxAPI->login()) $noLogin = true;
	}
	if($noLogin == true) {
		$log->log('Could not reach node ' . $node_data->hostname, 'error', 2, $user->data()->username, $_SERVER['REMOTE_ADDR']);
		$vmtable['kvm'][$i] = array(
			'noLogin' => true
		);
	}else if($data[$i]->suspended == 0 && $noLogin == false) {
		$vminfo = $pxAPI->get('/pools/'.$data[$i]->pool_id);
		if(count($vminfo['members']) == 1) {
			$info = $pxAPI->get('/nodes/'.$data[$i]->node.'/qemu/'.$vminfo['members'][0]['vmid'].'/status/current');
			$vmtable['kvm'][$i] = array(
				'noLogin' => false,
				'suspended' => false,
				'status' => $info['status'],
				'name' => $info['name'],
				'ip' => escape($data[$i]->ip),
				'os' => escape($data[$i]->os),
				'maxmem' => read_bytes_size($info['maxmem'], 0),
				'maxdisk' => read_bytes_size($info['maxdisk'], 0),
				'cpus' => $info['cpus'],
				'hbid' => escape($data[$i]->hb_account_id),
				'from_template' => $data[$i]->from_template
			);
		}else{
			for($j = 0; $j < count($vminfo['members']); $j++) {
				if($vminfo['members'][$j]['name'] == $data[$i]->cloud_hostname) {
					$info = $pxAPI->get('/nodes/'.$data[$i]->node.'/qemu/'.$vminfo['members'][$j]['vmid'].'/status/current');
					$vmtable['kvm'][$i] = array(
						'noLogin' => false,
						'suspended' => false,
						'status' => $info['status'],
						'name' => $info['name'],
						'ip' => escape($data[$i]->ip),
						'os' => escape($data[$i]->os),
						'maxmem' => read_bytes_size($info['maxmem'], 0),
						'maxdisk' => read_bytes_size($info['maxdisk'], 0),
						'cpus' => $info['cpus'],
						'hbid' => escape($data[$i]->hb_account_id),
						'from_template' => $data[$i]->from_template
					);
				}
			}
		}
	}else if($data[$i]->suspended == 1) {
		$vminfo = $pxAPI->get('/pools/'.$data[$i]->pool_id);
		$info = $pxAPI->get('/nodes/'.$data[$i]->node.'/qemu/'.$vminfo['members'][0]['vmid'].'/status/current');
		$vmtable['kvm'][$i] = array(
			'noLogin' => false,
			'suspended' => true,
			'status' => $info['status'],
			'name' => $info['name'],
			'ip' => escape($data[$i]->ip),
			'os' => escape($data[$i]->os),
			'maxmem' => read_bytes_size($info['maxmem'], 0),
			'maxdisk' => read_bytes_size($info['maxdisk'], 0),
			'cpus' => $info['cpus']
		);
	}
}
$cloud_accounts = escape($db->get('vncp_settings', array('item', '=', 'cloud_accounts'))->first()->value);
$hasCloud = false;
$cl_data = array();
if($cloud_accounts != 'false') {
	$cl_result = $db->get('vncp_kvm_cloud', array('user_id', '=', $user->data()->id));
	$cl_data = $cl_result->all();
	if(count($cl_data) > 0) {
		$hasCloud = true;
	}
}
$content = $db->get('vncp_kvm_isos', array('content', '=', 'iso'));
$contentr = $content->all();
$kvmisos_data = $db->get('vncp_kvm_isos_custom', array('user_id', '=', $user->data()->id))->all();
$kvmisos_custom = array();
for($i == 0; $i < count($kvmisos_data); $i++) {
	if($kvmisos_data[$i]->status == 'active') {
		$kvmisos_custom[] = $kvmisos_data[$i];
	}
}
$kvmisos_custom_location = 'local';
if(count($contentr) > 0) {
	$kvmisos_custom_location = explode(':', $contentr[0]->volid)[0];
}
$getDepts = '';
$getInvoices = '';
$getTickets = '';
if($enable_whmcs == 'true') {
	$getDepts = Whmcs_Api($whmcs_url, array(
		'username' => $whmcs_id,
		'password' => $whmcs_key,
		'responsetype' => 'json',
		'action' => 'GetSupportDepartments',
		'ignore_dept_assignments' => true
	));
	$getInvoices = Whmcs_Api($whmcs_url, array(
		'username' => $whmcs_id,
		'password' => $whmcs_key,
		'responsetype' => 'json',
		'action' => 'GetInvoices',
		'userid' => $user->data()->id,
		'status' => 'Unpaid'
	));
	$getTickets = Whmcs_Api($whmcs_url, array(
		'username' => $whmcs_id,
		'password' => $whmcs_key,
		'responsetype' => 'json',
		'action' => 'GetTickets',
		'clientid' => $user->data()->id,
		'status' => 'All Active Tickets',
		'ignore_dept_assignments' => true
	));
}

$enable_firewall = escape($db->get('vncp_settings', array('item', '=', 'enable_firewall'))->first()->value);
$enable_forward_dns = escape($db->get('vncp_settings', array('item', '=', 'enable_forward_dns'))->first()->value);
$enable_reverse_dns = escape($db->get('vncp_settings', array('item', '=', 'enable_reverse_dns'))->first()->value);
$enable_notepad = escape($db->get('vncp_settings', array('item', '=', 'enable_notepad'))->first()->value);
$enable_status = escape($db->get('vncp_settings', array('item', '=', 'enable_status'))->first()->value);
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

echo $twig->render('home-left.tpl', [
	'appname' => $appname,
	'errors' => $errors,
	'vmtable' => $vmtable,
	'cloud_accounts' => $cloud_accounts,
	'hasCloud' => $hasCloud,
	'cl_data' => $cl_data,
	'kvmisos' => $contentr,
	'enable_whmcs' => $enable_whmcs,
	'getDepts' => $getDepts,
	'getInvoices' => $getInvoices,
	'token' => Token::generate(),
	'getTickets' => $getTickets,
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
	'pagename' => 'Dashboard',
	'kvmisos_custom' => $kvmisos_custom,
	'kvmisos_custom_location' => $kvmisos_custom_location,
	'L' => $L
]);
if(isset($GLOBALS['proxcp_branding']) && !empty($GLOBALS['proxcp_branding'])) echo $GLOBALS['proxcp_branding'];
$enable_panel_news = escape($db->get('vncp_settings', array('item', '=', 'enable_panel_news'))->first()->value);
$news = '';
if($enable_panel_news == 'true') {
	$news = news_render($db);
}
$result = $db->limit_get_desc('vncp_users_ip_log', array('client_id', '=', $user->data()->id), '1');
$data = $result->first();
$support_ticket_url = escape($db->get('vncp_settings', array('item', '=', 'support_ticket_url'))->first()->value);
$user_iso_upload = escape($db->get('vncp_settings', array('item', '=', 'user_iso_upload'))->first()->value);
$max_upload_size = ini_get('upload_max_filesize');
$hasKVM_ISO = false;
foreach($vmtable['kvm'] as $kvm) {
	if($kvm['suspended'] == false) {
		$hasKVM_ISO = true;
		break;
	}
}
if($hasCloud == true) {
	$hasKVM_ISO = true;
}
$user_isos = $db->get('vncp_kvm_isos_custom', array('user_id', '=', $user->data()->id))->all();
echo $twig->render('home-right.tpl', [
	'enable_panel_news' => $enable_panel_news,
	'news' => $news,
	'appname' => $appname,
	'data' => $data,
	'enable_whmcs' => $enable_whmcs,
	'support_ticket_url' => $support_ticket_url,
	'user_iso_upload' => $user_iso_upload,
	'hasKVM_ISO' => $hasKVM_ISO,
	'max_upload_size' => $max_upload_size,
	'user_isos' => $user_isos,
	'L' => $L
]);
echo '</div>
</div>
</div>
<input type="hidden" value="'. Session::get('user') .'" id="user" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>window.jQuery || document.write(\'<script src="js/vendor/jquery-1.11.2.min.js"><\/script>\')</script>
<script src="js/vendor/bootstrap.min.js"></script>
<script src="js/vendor/bootstrap-slider.min.js"></script>
<script src="js/main.js"></script>
<script src="js/buttons.js"></script>
<script src="js/io.js"></script>';
if($hasCloud == true) {
	echo '<script src="js/cloud.js"></script>';
	echo '<script src="js/slider.js"></script>';
}
if($user_iso_upload == 'true' && $hasKVM_ISO == true) {
	echo '<script src="js/uploads.js"></script>';
}
echo '</body>
</html>';
?>
