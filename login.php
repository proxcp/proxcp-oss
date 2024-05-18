<?php
require_once('vendor/autoload.php');
require_once('core/autoload.php');
require_once('core/init.php');
require_once('core/session.php');
use GeoIp2\Database\Reader;

if(Config::get('instance/installed') == false) {
    Redirect::to('install');
}else{
    $connection = mysqli_connect(Config::get('database/host'), Config::get('database/username'), Config::get('database/password'));
    mysqli_select_db($connection, Config::get('database/db'));
    $user = new User();
    if($user->isLoggedIn()) {
        Redirect::to('index');
    }
}
$db = DB::getInstance();
$log = new Logger();
if(Input::exists() && Input::get('form_name') == 'login_form') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'username' => array(
                'required' => true,
                'max' => 100,
                'min' => 5
            ),
            'password' => array(
                'required' => true
            )
        ));
        if($validation->passed()) {
            $remember = (Input::get('remember_me') === 'on') ? true : false;
            $login = $user->login(strtolower(Input::get('username')), Input::get('password'), $remember);
            if($login) {
                if($user->data()->locked == 1) {
                    $errors = 'This account is disabled.';
                    $log->log('Attempted to login to disabled account.', 'error', 1, Input::get('username'), $_SERVER['REMOTE_ADDR']);
                    $user->logout();
                }else{
                    $user_acl = escape($db->get('vncp_settings', array('item', '=', 'user_acl'))->first()->value);
                    if($user_acl == 'true') {
                        $rip = $db->get('vncp_acl', array('user_id', '=', $user->data()->id));
                        $dip = $rip->all();
                        $vip = 0;
                        for($i = 0; $i < count($dip); $i++) {
                            if($dip[$i]->ipaddress == $_SERVER['REMOTE_ADDR']){
                                $vip = 1;
                                break;
                            }
                        }
                    }else{
                        $vip = 1;
                    }
                    if($vip == 1 || count($dip) == 0) {
                      if($user->data()->tfa_enabled == 1) {
                        $tSalt = $user->data()->salt;
                        $user->logout();
                        $modalForm = '<form role="form" action="" method="POST">
                            <fieldset>
                                <h2>2FA Required</h2>
                                <hr class="pulse" />
                                <div class="form-group">
                                    <input type="password" name="totptoken" id="totptoken" class="form-control input-lg" placeholder="XXXXXX">
                                </div>
                                <hr class="pulse">
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12 col-md-12">
                                        <input type="submit" class="btn btn-lg btn-success btn-block" value="Login">
                                    </div>
                                </div>
                            </fieldset>
                            <input type="hidden" name="username" value="'. strtolower(Input::get('username')) . '" />
                            <input type="hidden" name="c" value="' . encryptValue(Hash::make(Input::get('password'), $tSalt)) . '" />
                            <input type="hidden" name="form_name" value="totp_form" />
                        </form>';
                      }else{
                        $reader = new Reader('core/GeoLite2-City.mmdb');
                        $logged_ip = $_SERVER['REMOTE_ADDR'];
                        $record = $reader->city($logged_ip);
                        $log_ip = $db->insert('vncp_users_ip_log', array(
                            'client_id' => $user->data()->id,
                            'date' => date("Y-m-d H:i:s"),
                            'ip' => $logged_ip,
                            'geoip_loc' => '' . $record->city->name . ', ' . $record->mostSpecificSubdivision->isoCode . ', ' . $record->country->isoCode,
                            'geoip_coords' => '' . $record->location->latitude . ' ' . $record->location->longitude
                        ));
                        Redirect::to('index');
                      }
                    }else{
                        $errors = 'Client IP address mismatch.';
                        $log->log('Client IP address mismatch', 'error', 1, Input::get('username'), $_SERVER['REMOTE_ADDR']);
                        $user->logout();
                    }
                }
            }else{
                $errors = 'An error occurred while attempting to process your login request. An incorrect username or password was entered.';
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('form_name') == 'totp_form') {
  $validate = new Validate();
  $validation = $validate->check($_POST, array(
      'totptoken' => array(
        'required' => true,
        'min' => 6,
        'max' => 6,
        'numonly' => true
      ),
      'username' => array(
        'required' => true,
        'valemail' => true,
        'min' => 4,
        'max' => 100
      ),
      'c' => array(
        'required' => true
      )
  ));
  if($validation->passed()) {
    $login = $user->loginHash(strtolower(Input::get('username')), decryptValue(Input::get('c')));
    if($login) {
      $ga = new vncp_GoogleAuthenticator();
      $checkResult = $ga->verifyCode($user->data()->tfa_secret, Input::get('totptoken'), 2);
      if($checkResult) {
        $reader = new Reader('core/GeoLite2-City.mmdb');
        $logged_ip = $_SERVER['REMOTE_ADDR'];
        $record = $reader->city($logged_ip);
        $log_ip = $db->insert('vncp_users_ip_log', array(
            'client_id' => $user->data()->id,
            'date' => date("Y-m-d H:i:s"),
            'ip' => $logged_ip,
            'geoip_loc' => '' . $record->city->name . ', ' . $record->mostSpecificSubdivision->isoCode . ', ' . $record->country->isoCode,
            'geoip_coords' => '' . $record->location->latitude . ' ' . $record->location->longitude
        ));
        Redirect::to('index');
      }else{
        Redirect::to('logout');
      }
    }else{
      Redirect::to('login');
    }
  }else{
    Redirect::to('login');
  }
}

$ssoemail = '';
if(Input::exists('GET') && isset($_GET['u']) && (Input::get('from') == 'whmcs' || Input::get('from') == 'blesta')) {
	$ssoemail = base64_decode(urldecode(Input::get('u')));
	if($ssoemail == false) {
		$ssoemail = '';
	}else{
		$ssoemail = trim(stripslashes(escape((string)$ssoemail)));
	}
}

$appname = $db->get('vncp_settings', array('item', '=', 'app_name'))->first()->value;
$support_ticket_url = $db->get('vncp_settings', array('item', '=', 'support_ticket_url'))->first()->value;

echo $twig->render('login.tpl', [
  'appname' => $appname,
  'gethttps' => gethttps(),
  'modalForm' => $modalForm,
  'errors' => $errors,
  'formToken' => Token::generate(),
  'support_ticket_url' => $support_ticket_url,
  'pagename' => 'Login',
  'ssoemail' => $ssoemail
]);

if(isset($GLOBALS['proxcp_branding']) && !empty($GLOBALS['proxcp_branding'])) echo $GLOBALS['proxcp_branding'];
echo '</div>
</div>
</div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script>window.jQuery || document.write(\'<script src="js/vendor/jquery-1.11.2.min.js"><\/script>\')</script>
<script src="js/vendor/bootstrap.min.js"></script>
<script src="js/main.js"></script>
<script src="js/buttons.js"></script>
</body>
</html>';
?>
