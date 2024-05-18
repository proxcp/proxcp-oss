<?php
require_once('core/functions.php');
function make($string, $salt = '') {
	return hash('sha256', $string . $salt);
}
function salt() {
	return uniqid(mt_rand(), true);
}
$step = (isset($_GET['step']) && $_GET['step'] != '') ? $_GET['step'] : '';
switch($step) {
	case '1':
		if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agree'])) {
			header('Location: install?step=2');
			exit;
		}
		if($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['agree'])) {
			die("You must agree to the license to continue with installation.");
		}
		break;
	case '2':
		clearstatcache();
		$pre_error = '';
		if(phpversion() < '7.2') {
			$pre_error .= 'You need to use PHP 7.2 or above for this application.<br />';
		}
		if(ini_get('session.auto_start')) {
			$pre_error .= 'This application will not work with session.auto_start enabled.<br />';
		}
		if(ini_get('display_errors')) {
			$pre_error .= 'This application requires display_errors to be Off.<br />';
		}
		if(!extension_loaded('mysqli')) {
			$pre_error .= 'MySQLi extension needs to be loaded for this application.<br />';
		}
		if(!defined('PDO::ATTR_DRIVER_NAME')) {
			$pre_error .= 'PDO extension needs to be loaded for this application.<br />';
		}
		if(!extension_loaded('gd')) {
			$pre_error .= 'GD extension needs to be loaded for this application.<br />';
		}
		if(!extension_loaded('curl')) {
			$pre_error .= 'CURL extension needs to be loaded for this application.<br />';
		}
		if(!is_writable('core/init.php')) {
			$pre_error .= 'core/init.php needs to be writable for installation.';
		}
		if(!is_writable('js/io.js')) {
			$pre_error .= 'js/io.js needs to be writable for installation.';
		}
		if(!is_writable('templates_c')) {
			$pre_error .= 'templates_c directory needs to be writable for installation.';
		}
		$sas = (ini_get('session_auto_start')) ? 'On' : 'Off';
		$do = (ini_get('display_errors')) ? 'On' : 'Off';
		$mysqle = extension_loaded('mysqli') ? 'On' : 'Off';
		$pdoe = defined('PDO::ATTR_DRIVER_NAME') ? 'On' : 'Off';
		$gde = extension_loaded('gd') ? 'On' : 'Off';
		$curle = extension_loaded('curl') ? 'On' : 'Off';
		$perms = is_writable('core/init.php') ? 'Writable' : 'Unwritable';
		$perms2 = is_writable('js/io.js') ? 'Writable' : 'Unwritable';
		$perms3 = is_writable('templates_c') ? 'Writable' : 'Unwritable';
		$c_phpv = (phpversion() >= '7.2') ? 'Good' : 'Bad';
		$c_sas = (!ini_get('session_auto_start')) ? 'Good' : 'Bad';
		$c_do = (!ini_get('display_errors')) ? 'Good' : 'Bad';
		$c_mysqle = extension_loaded('mysqli') ? 'Good' : 'Bad';
		$c_pdoe = defined('PDO::ATTR_DRIVER_NAME') ? 'Good' : 'Bad';
		$c_gde = extension_loaded('gd') ? 'Good' : 'Bad';
		$c_curl = extension_loaded('curl') ? 'Good' : 'Bad';
		$c_perms = is_writable('core/init.php') ? 'Good' : 'Bad';
		$c_perms2 = is_writable('js/io.js') ? 'Good' : 'Bad';
		$c_perms3 = is_writable('templates_c') ? 'Good' : 'Bad';
		$form = '<form role="form" action="install?step=2" method="POST">
                        <fieldset>
                        	<h2>ProxCP Installation <small>v ' . getVersion()[0] . '</small></h2>
                        	<h6 align="center">' . getVersion()[1] . '</h6>
                        	<hr class="pulse" />
                        	<div class="row">
                        		<div class="col-md-6">
                        			<p>PHP Version >= 7.2</p>
                        			<p>PHP INI session_auto_start</p>
                        			<p>PHP INI display_errors</p>
                        			<p>PHP MySQLi Extension</p>
                        			<p>PHP PDO Extension</p>
                        			<p>PHP GD Extension</p>
															<p>PHP CURL Extension</p>
                        			<p>core/init.php Writable</p>
															<p>js/io.js Writable</p>
															<p>templates_c Writable</p>
                        		</div>
                        		<div class="col-md-3">
                        			<p>' . phpversion() . '</p>
                        			<p>' . $sas . '</p>
                        			<p>' . $do . '</p>
                        			<p>' . $mysqle . '</p>
                        			<p>' . $pdoe . '</p>
                        			<p>' . $gde . '</p>
                        			<p>' . $curle . '</p>
                        			<p>' . $perms . '</p>
															<p>' . $perms2 . '</p>
															<p>' . $perms3 . '</p>
                        		</div>
                        		<div class="col-md-3">
                        			<p>' . $c_phpv . '</p>
                        			<p>' . $c_sas . '</p>
                        			<p>' . $c_do . '</p>
                        			<p>' . $c_mysqle . '</p>
                        			<p>' . $c_pdoe . '</p>
                        			<p>' . $c_gde . '</p>
                        			<p>' . $c_curl . '</p>
                        			<p>' . $c_perms . '</p>
															<p>' . $c_perms2 . '</p>
															<p>' . $c_perms3 . '</p>
                        		</div>
                        	</div>
                        	<hr class="pulse" />
                        	<div class="row">
                        		<div class="col-xs-8 col-sm-8 col-md-8">
                        			<input type="submit" class="btn btn-lg btn-success btn-block" value="Continue" />
                        		</div>
                        		<div class="col-xs-4 col-sm-4 col-md-4">
                        			<a href="install?step=2" class="btn btn-lg btn-success btn-block">Refresh</a>
                        		</div>
                        	</div>
                        </fieldset>
                        <input type="hidden" name="pre_error" id="pre_error" value="' . $pre_error . '" />
                    </form>';
		if($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['pre_error'] != '') {
			die($_POST['pre_error']);
		}
		if($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['pre_error'] == '') {
			header('Location: install?step=3');
			exit;
		}
		break;
	case '3':
		$form = '<form role="form" action="install?step=3" method="POST">
                        <fieldset>
                        	<h2>ProxCP Installation <small>v ' . getVersion()[0] . '</small></h2>
                        	<h6 align="center">' . getVersion()[1] . '</h6>
                        	<hr class="pulse" />
                        	<div class="form-group">
                        		<input type="text" name="database_host" id="database_host" class="form-control input-lg" placeholder="Database Host" />
                        	</div>
                        	<div class="form-group">
                        		<input type="text" name="database_name" id="database_name" class="form-control input-lg" placeholder="Database Name" />
                        	</div>
                        	<div class="form-group">
                        		<input type="text" name="database_username" id="database_username" class="form-control input-lg" placeholder="Database Username" />
                        	</div>
                        	<div class="form-group">
                        		<input type="password" name="database_password" id="database_password" class="form-control input-lg" placeholder="Database Password" />
                        	</div>
                        	<div class="form-group">
                        		<input type="email" name="admin_email" id="admin_email" class="form-control input-lg" placeholder="Admin Email" />
                        	</div>
                        	<div class="form-group">
                        		<input type="password" name="admin_password" id="admin_password" class="form-control input-lg" placeholder="Admin Password" />
                        	</div>
													<div class="form-group">
                        		<input type="text" name="socket_domain" id="socket_domain" class="form-control input-lg" placeholder="ProxCP Daemon URL (https://app.domain.com:8000)" />
                        	</div>
                        	<hr class="pulse" />
                        	<div class="row">
                        		<div class="col-xs-12 col-sm-12 col-md-12">
                        			<input type="submit" name="submit" class="btn btn-lg btn-success btn-block" value="Install" />
                        		</div>
                        	</div>
                        </fieldset>
                    </form>';
		if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == "Install") {
			$database_host = isset($_POST['database_host']) ? $_POST['database_host'] : '';
			$database_name = isset($_POST['database_name']) ? $_POST['database_name'] : '';
			$database_username = isset($_POST['database_username']) ? $_POST['database_username'] : '';
			$database_password = isset($_POST['database_password']) ? $_POST['database_password'] : '';
			$admin_email = isset($_POST['admin_email']) ? $_POST['admin_email'] : '';
			$admin_email = strtolower($admin_email);
			$admin_password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
			$socket_domain = isset($_POST['socket_domain']) ? $_POST['socket_domain'] : '';

			if(empty($admin_email) || empty($admin_password) || empty($database_host) || empty($database_username) || empty($database_name) || empty($socket_domain)) {
				die('All fields are required. Please re-enter.');
			}else{
				$f = fopen('core/init.php', 'w');
				$f2 = fopen('js/io.js', 'w');
				$overall = '$GLOBALS[\'config\']';
				$vars = array('$hash', '$hashCheck', '$user');
				$io_inf = "var socket = io.connect('".$socket_domain."', { secure:true });var user = $('#user').val();socket.on('connect', function() {console.log('Connected to socket');$('#socket_error').css('visibility', 'hidden');$('#socket_error').css('padding', '0px');$('#socket_error').html('');socket.emit('addUserConnection', user);});socket.on('reconnecting', function() {console.log('Lost connection to socket! Attempting to reconnect...');$('#socket_error').css('visibility', 'visible');$('#socket_error').css('padding', '10px');$('#socket_error').html('Cannot connect to socket! All VM functions will fail :(');});";
				$database_inf = "<?php
//////////////////////////////////////////////
//     BEGIN USER CONFIGURATION SECTION     //
//////////////////////////////////////////////

".$overall." = array(
	// DATABASE CONFIGURATION
	'database' => array(
		'type' => 'mysql',
		'host' => '".$database_host."',
		'username' => '".$database_username."',
		'password' => '".$database_password."',
		'db' => '".$database_name."'
	),
	'instance' => array(
		'base' => '".gethost()."', // BASE DOMAIN OF THIS PROXCP INSTALLATION
		'installed' => true, // HAS PROXCP BEEN INSTALLED?
		'l_salt' => '".getRandomString(24)."', // DO NOT CHANGE OR SHARE THESE VALUES - SALT 1
		'v_salt' => '".getRandomString(24)."', // DO NOT CHANGE OR SHARE THESE VALUES - SALT 2
		'vncp_secret_key' => '".bin2hex(openssl_random_pseudo_bytes(32)).".".bin2hex(openssl_random_pseudo_bytes(16))."' // DO NOT CHANGE OR SHARE THESE VALUES - SECRET KEY
	),
	'admin' => array(
		'base' => 'admin' // BASE ADMIN FILE NAME WITHOUT FILE EXTENSION
	),
	// REMEMBER ME LOGIN SETTINGS
	'remember' => array(
		'cookie_name' => 'hash',
		'cookie_expiry' => 604800
	),
	// LOGIN SESSION SETTINGS
	'session' => array(
		'session_name' => 'user',
		'token_name' => 'token'
	)
);

//////////////////////////////////////////////
//      END USER CONFIGURATION SECTION      //
//////////////////////////////////////////////

//////////////////////////////////////////////
//       DO NOT EDIT BELOW THIS LINE        //
//////////////////////////////////////////////";
				if(fwrite($f, $database_inf) > 0) {
					fclose($f);
				}
				if(fwrite($f2, $io_inf) > 0) {
					fclose($f2);
				}
				require_once('vendor/autoload.php');
				require_once('core/autoload.php');
				require_once('core/init.php');
				require_once('core/session.php');
				$connection = mysqli_connect($database_host, $database_username, $database_password);
				mysqli_select_db($connection, $database_name);
				$file = 'sql/install.sql';
				if($sql = file($file)) {
					$query = '';
					for($i = 0; $i < count($sql); $i++) {
						$tsl = trim($sql[$i]);
						if($sql != '' && $tsl != '') {
							$query .= $tsl;
							mysqli_query($connection, $query);
							$err = mysqli_error($connection);
							if(!empty($err)) {
								die('Installation failed. MySQL Error: ' . $err);
							}
							$query = '';
						}
					}
					$salt = salt();
					$salt = mysqli_real_escape_string($connection, $salt);
					$admin_password = make($admin_password, $salt);
					mysqli_query($connection, "INSERT INTO vncp_users SET email='".$admin_email."', username='".$admin_email."', password='".$admin_password."', salt='".$salt."', tfa_enabled=0, tfa_secret='', `group`=2, locked=0, language='en'");
					mysqli_query($connection, "INSERT INTO vncp_notes SET id=1, notes='Welcome!'");
					mysqli_close($connection);
				}
				sleep(3);
				header('Location: install?step=4');
			}
		}
		break;
	case '4':
		require_once('vendor/autoload.php');
		require_once('core/autoload.php');
		require_once('core/init.php');
		require_once('core/session.php');
		$form = '<form role="form" action="" method="POST">
                        <fieldset>
                        	<h2>ProxCP Installation <small>v ' . getVersion()[0] . '</small></h2>
                        	<h6 align="center">' . getVersion()[1] . '</h6>
                        	<hr class="pulse" />
                        	<p>Congratulations! ProxCP has been installed successfully. You can login to the admin account you created and continue setup.</p>
                        	<br />
													<p>ProxCP Socket Key: ' . $GLOBALS['config']['instance']['vncp_secret_key'] . '<br />Copy this key as you will need it to configure the ProxCP socket.</p>
													<br />
                        	<h4>COMPLETE THESE SECURITY STEPS:</h4>
                        	<p>Delete install.php file</p>
													<p>Delete sql/ directory</p>
                        	<p>Change core/init.php permissions <pre>chmod 0444 core/init.php</pre></p>
                        	<p>Change js/io.js permissions <pre>chmod 0644 js/io.js</pre></p>
                        	<p>Optional: change admin.php file name</p>
                        	<hr class="pulse" />
                        	<div class="row">
                        		<div class="col-xs-12 col-sm-12 col-md-12">
                        			<a href="' . gethost() . '" class="btn btn-lg btn-success btn-block">Go to login</a>
                        		</div>
                        	</div>
                        </fieldset>
                    </form>';
		break;
	default:
		$form = '<form role="form" action="install?step=1" method="POST">
                        <fieldset>
                        	<h2>ProxCP Installation <small>v ' . getVersion()[0] . '</small></h2>
                        	<h6 align="center">' . getVersion()[1] . '</h6>
                        	<hr class="pulse" />
                        	<div class="row">
                        		<div class="col-md-12">
                        			<p>Welcome to ProxCP! Run this script to complete installation of the application.</p>
                        			<p>License Agreement</p>
                        			<textarea style="resize:none;" class="form-control" rows="18" disabled>Not applicable...</textarea>
                        		</div>
                        	</div>
                        	<br />
                        	<span class="button-checkbox">
                                <button type="button" class="btn" data-color="info">Agree</button>
                                <input type="checkbox" name="agree" id="agree" class="hidden">
                            </span>
                        	<hr class="pulse" />
                        	<div class="row">
                        		<div class="col-xs-12 col-sm-12 col-md-12">
                        			<input type="submit" class="btn btn-lg btn-success btn-block" value="Begin" />
                        		</div>
                        	</div>
                        </fieldset>
                    </form>';
}
?>
<!doctype html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7" lang=""><![endif]-->
<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8" lang=""><![endif]-->
<!--[if IE 8]><html class="no-js lt-ie9" lang=""><![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang=""> <!--<![endif]-->
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <title>ProxCP - Installation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/font-awesome.min.css" />
    <link rel="stylesheet" href="css/main.css" />
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,300,700' rel='stylesheet' type='text/css' />
    <link rel="icon" type="image/png" href="favicon.ico" />
    <script src="js/vendor/modernizr-2.8.3-respond-1.4.2.min.js"></script>
</head>
<body>
	<?php
	if(!gethttps()) {
		echo '<div id="socket_error" class="socket_error" style="visibility:visible;padding:10px;">Insecure connection (non-HTTPS)!</div>';
	}
	?>
    <!--[if lt IE 8]>
        <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
    <nav class="navbar navbar-default" id="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand"><img src="img/logo.png" class="img-responsive" /></a>
            </div>
                <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                    <ul class="nav navbar-nav navbar-right nav-elem" id="bottom-nav">
                        <li><a href="https://google.com"><i class="fa fa-life-ring"></i> Support</a></li>
                    </ul>
                </div>
        </div>
    </nav>
    <div class="container-full" id="blocks">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3 login-box">
                		<?php echo $form; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.2.min.js"><\/script>')</script>
    <script src="js/vendor/bootstrap.min.js"></script>
    <script src="js/buttons.js"></script>
</body>
</html>
