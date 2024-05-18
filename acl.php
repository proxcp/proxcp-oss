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
    if(strpos(Input::get('formid'), 'addip') !== false) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'ipaddress' => array(
                'required' => true,
                'ip' => true
            )
        ));
        if($validation->passed()) {
            $db->insert('vncp_acl', array(
                'user_id' => $user->data()->id,
                'ipaddress' => Input::get('ipaddress')
            ));
            $log->log('Added ' . Input::get('ipaddress') . ' to user ACL', 'general', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }else if(strpos(Input::get('formid'), 'rmip') !== false) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'ip_remove' => array(
                'required' => true,
                'ip' => true
            )
        ));
        if($validation->passed()) {
            $valip = $db->get('vncp_acl', array('ipaddress', '=', Input::get('ip_remove')));
            $valip_d = $valip->first();
            if($valip_d->user_id == $user->data()->id) {
                $db->delete('vncp_acl', array('ipaddress', '=', Input::get('ip_remove')));
                $log->log('Removed ' . Input::get('ip_remove') . ' from user ACL', 'general', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
            }else{
                $errors = 'Invalid IP address.';
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
$aclsetting = $db->get('vncp_settings', array('item', '=', 'user_acl'))->first()->value;
$getacl = $db->get('vncp_acl', array('user_id', '=', $user->data()->id));
$gotacl = $getacl->all();
$constants = false;
if(defined('constant') || defined('constant-fw')) {
    $constants = true;
}
$enable_firewall = escape($db->get('vncp_settings', array('item', '=', 'enable_firewall'))->first()->value);
$enable_forward_dns = escape($db->get('vncp_settings', array('item', '=', 'enable_forward_dns'))->first()->value);
$enable_reverse_dns = escape($db->get('vncp_settings', array('item', '=', 'enable_reverse_dns'))->first()->value);
$enable_notepad = escape($db->get('vncp_settings', array('item', '=', 'enable_notepad'))->first()->value);
$enable_status = escape($db->get('vncp_settings', array('item', '=', 'enable_status'))->first()->value);
$isAdmin = $user->hasPermission('admin');

$L = new Language($user->data()->language);
$L = $L->load();
if(!$L) {
	$log->log('Could not load language ' . $user->data()->language, 'error', 2, $user->data()->username, $_SERVER['REMOTE_ADDR']);
	die('Language "'.$user->data()->language.'" not found.');
}

echo $twig->render('acl.tpl', [
  'appname' => $appname,
  'aclsetting' => $aclsetting,
  'errors' => $errors,
  'currentIP' => $_SERVER['REMOTE_ADDR'],
  'formID' => 'addip_'.getRandomString(10),
  'formID2' => 'rmip_'.getRandomString(10),
  'gotacl' => $gotacl,
  'constants' => $constants,
  'username' => $user->data()->username,
  'enable_firewall' => $enable_firewall,
  'enable_forward_dns' => $enable_forward_dns,
  'enable_reverse_dns' => $enable_reverse_dns,
  'enable_notepad' => $enable_notepad,
  'enable_status' => $enable_status,
  'isAdmin' => $isAdmin,
  'adminBase' => Config::get('admin/base'),
  'pagename' => 'Access Control',
  'L' => $L
]);

echo '</div></div>
</div>
<input type="hidden" value="'.Session::get('user').'" id="user" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>window.jQuery || document.write(\'<script src="js/vendor/jquery-1.11.2.min.js"><\/script>\')</script>
<script src="js/vendor/bootstrap.min.js"></script>
<script src="//cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="js/main.js"></script>
<script src="js/buttons.js"></script>
<script src="js/io.js"></script>
<script type="text/javascript">$(\'#useracltable\').DataTable();</script>
</body>
</html>';
?>
