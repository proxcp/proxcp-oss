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
if(Input::exists()) {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'notes' => array(
                'max' => 10000
            )
        ));
        if($validation->passed()) {
            $db->update('vncp_notes', $user->data()->id, array(
                'id' => $user->data()->id,
                'notes' => Input::get('notes')
            ));
            $log->log('Updated user notes.', 'general', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
            Redirect::to('notes');
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}

$appname = $db->get('vncp_settings', array('item', '=', 'app_name'))->first()->value;
$notessetting = $db->get('vncp_settings', array('item', '=', 'enable_notepad'))->first()->value;
$results = $db->get('vncp_notes', array('id', '=', $user->data()->id));
$data = $results->first();
if(empty($data)) {
    $create = $db->insert('vncp_notes', array(
        'id' => $user->data()->id,
        'notes' => 'Welcome!'
    ));
    $results = $db->get('vncp_notes', array('id', '=', $user->data()->id));
    $data = $results->first();
}
$count = strlen($data->notes);
$count = 10000 - $count;

$enable_firewall = escape($db->get('vncp_settings', array('item', '=', 'enable_firewall'))->first()->value);
$enable_forward_dns = escape($db->get('vncp_settings', array('item', '=', 'enable_forward_dns'))->first()->value);
$enable_reverse_dns = escape($db->get('vncp_settings', array('item', '=', 'enable_reverse_dns'))->first()->value);
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

echo $twig->render('notes.tpl', [
  'appname' => $appname,
  'notessetting' => $notessetting,
  'count' => $count,
  'errors' => $errors,
  'notes' => $data->notes,
  'formToken' => Token::generate(),
  'adminBase' => Config::get('admin/base'),
  'enable_firewall' => $enable_firewall,
  'enable_forward_dns' => $enable_forward_dns,
  'enable_reverse_dns' => $enable_reverse_dns,
  'enable_notepad' => $notessetting,
  'enable_status' => $enable_status,
  'isAdmin' => $isAdmin,
  'constants' => $constants,
  'username' => $user->data()->username,
  'aclsetting' => $aclsetting,
  'pagename' => 'Notepad',
  'L' => $L
]);

echo '<input type="hidden" value="'.Session::get('user').'" id="user" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>window.jQuery || document.write(\'<script src="js/vendor/jquery-1.11.2.min.js"><\/script>\')</script>
<script src="js/vendor/bootstrap.min.js"></script>
<script src="js/main.js"></script>
<script src="js/buttons.js"></script>
<script src="js/io.js"></script>
</body>
</html>';
?>
