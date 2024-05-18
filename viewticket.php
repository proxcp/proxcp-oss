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

if(isset($_GET['id']) && !empty($_GET['id'])) {
  $tid = $_GET['id'];
  $getTicket = Whmcs_Api($whmcs_url, array(
    'username' => $whmcs_id,
    'password' => $whmcs_key,
    'responsetype' => 'json',
    'action' => 'GetTicket',
    'ticketnum' => $tid,
    'repliessort' => 'DESC'
  ));
  if($getTicket['result'] != 'success') {
    Redirect::to('index');
  }else{
    if($getTicket['userid'] != $user->data()->id || $getTicket['status'] == 'Closed') {
      Redirect::to('index');
    }
  }
}else{
  Redirect::to('index');
}

if($enable_whmcs == 'true' && Input::exists()) {
  if(Token::check(Input::get('token'))) {
    $validate = new Validate();
    $validation = $validate->check($_POST, array(
      'tid' => array(
        'required' => true,
        'numonly' => true
      ),
      'replymsg' => array(
        'required' => true,
        'min' => 2,
        'max' => 5000
      )
    ));
    if($validation->passed()) {
      $ticketReply = Whmcs_Api($whmcs_url, array(
        'username' => $whmcs_id,
        'password' => $whmcs_key,
        'responsetype' => 'json',
        'action' => 'AddTicketReply',
        'ticketid' => Input::get('tid'),
        'message' => Input::get('replymsg'),
        'clientid' => $user->data()->id
      ));
      if($ticketReply['result'] != 'success') {
        $errors = $ticketReply['result'];
      }else{
        Redirect::to('index');
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

echo $twig->render('viewticket.tpl', [
  'appname' => $appname,
  'tid' => $tid,
  'errors' => $errors,
  'enable_whmcs' => $enable_whmcs,
  'getTicket' => $getTicket,
  'token' => Token::generate(),
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
  'pagename' => 'View Ticket',
  'L' => $L
]);

echo '<input type="hidden" value="'. Session::get('user') .'" id="user" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>window.jQuery || document.write(\'<script src="js/vendor/jquery-1.11.2.min.js"><\/script>\')</script>
<script src="js/vendor/bootstrap.min.js"></script>
<script src="js/main.js"></script>
<script src="js/buttons.js"></script>
<script src="js/io.js"></script>
</body>
</html>';
?>
