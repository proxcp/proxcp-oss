<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once(dirname(__FILE__, 2) . '/lib/MagicCrypt.php');
require_once(dirname(__FILE__, 2) . '/lib/phpmailer/src/Exception.php');
require_once(dirname(__FILE__, 2) . '/lib/phpmailer/src/PHPMailer.php');
require_once(dirname(__FILE__, 2) . '/lib/phpmailer/src/SMTP.php');
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
function escape($string) {
	return htmlentities($string, ENT_QUOTES, 'UTF-8');
}

if(php_sapi_name() == 'cli') {
	$oldCount = $argv[1];
	$newCount = $argv[2];
	$counter = $argv[3];

	$load = file_get_contents(dirname(__FILE__, 2) . '/config.js');
	$pos_to_email = strrpos($load, "cron_to_email");
	$pos_to_email = substr($load, $pos_to_email);
	$to_email = explode("'", $pos_to_email)[1];
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
		$query = mysqli_query($con, "SELECT * FROM vncp_settings WHERE 1");
		if(!$query) {
			die('200');
		}else{
			$current = array();
			while($settings = mysqli_fetch_array($query)) {
				$current[$settings['item']] = $settings['value'];
			}
			if($current['mail_type'] == 'sysmail') {
				$to = $to_email;
				$subject = escape($current['app_name']).' Cron';
				$message = escape($current['app_name']).' cron job run successfully

Old count: '.$oldCount.'
New count: '.$newCount.'

Deleted: '.$counter;
				$headers = 'From: ' . escape($current['from_email']) . "\r\n" .
							'Reply-To: ' . escape($current['from_email']) . "\r\n" .
							'X-Mailer: PHP/' . phpversion();
				mail($to, $subject, $message, $headers);
			}else{
				$mail = new PHPMailer(true);
				try{
					$mail->isSMTP();
					$mail->Host = escape($current['smtp_host']);
					$mail->SMTPAuth = true;
					$mail->Username = escape($current['smtp_username']);
					$mail->Password = escape(decryptValue($current['smtp_password']));
					if($current['smtp_type'] == 'ssltls')
						$mail->SMTPSecure = 'ssl';
					else if($current['smtp_type'] == 'starttls')
						$mail->SMTPSecure = 'tls';
					$mail->Port = (int)escape($current['smtp_port']);
					$mail->setFrom(escape($current['from_email']), escape($current['from_email_name']));
					$mail->addAddress($to_email);
					$mail->addReplyTo(escape($current['from_email']), escape($current['from_email_name']));
					$mail->isHTML(false);
					$mail->Subject = escape($current['app_name']).' Cron';
					$mail->Body = escape($current['app_name']).' cron job run successfully

Old count: '.$oldCount.'
New count: '.$newCount.'

Deleted: '.$counter;
					$mail->send();
				}catch(Exception $e) {
					die('Message could not be sent. Please check your SMTP settings. Mailer Error: ' . $mail->ErrorInfo);
				}
			}
		}
	}
}
?>
