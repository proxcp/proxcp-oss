<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
    if($user->isLoggedIn()) {
        Redirect::to('index');
    }
}
$db = DB::getInstance();
$log = new Logger();
if(Input::exists()) {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
                'username' => array(
                        'required' => true,
                        'max' => 100,
                        'min' => 5
                )
        ));
        if($validation->passed()) {
            $fetch = $db->get('vncp_settings', array('id', '!=', 0))->all();
            $current = array();
            for($i = 0; $i < count($fetch); $i++) {
              $current[$fetch[$i]->item] = $fetch[$i]->value;
            }
            $reset = new User(Input::get('username'));
            if($reset->exists()){
                $salt = Hash::salt(32);
                $string = getRandomString(8);
                $user->update(array(
                        'password' => Hash::make($string, $salt),
                        'salt' => $salt
                ), $reset->data()->id);
                if($current['mail_type'] == 'sysmail') {
                  $to = $reset->data()->email;
                  $appname = escape($current['app_name']);
                  $subject = $appname.' Password Reset Request';
                  $message = 'Hello ' . $reset->data()->username . ',

  '.$appname.' has received a request to reset your password as it seems that you have forgotten it. Here is your new information:

  Username: ' . $reset->data()->username . '
  Password: ' . $string . '

  Be sure you remember it!

  Regards,
  '.$appname.' Bot
  URL: '.Config::get('instance/base');
                  $from_email = escape($current['from_email']);
                  $headers = 'From: ' . $from_email . "\r\n" .
                      'Reply-To: ' . $from_email . "\r\n" .
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
                    $mail->addAddress($reset->data()->email);
                    $mail->addReplyTo(escape($current['from_email']), escape($current['from_email_name']));
                    $mail->isHTML(false);
                    $mail->Subject = escape($current['app_name']).' Password Reset Request';
                    $mail->Body = 'Hello ' . $reset->data()->username . ',

    '.escape($current['app_name']).' has received a request to reset your password as it seems that you have forgotten it. Here is your new information:

    Username: ' . $reset->data()->username . '
    Password: ' . $string . '

    Be sure you remember it!

    Regards,
    '.escape($current['app_name']).' Bot
    URL: '.Config::get('instance/base');
                    $mail->send();
                  }catch(Exception $e) {
                    $log->log('Mailer error: {' . $mail->ErrorInfo . '}', 'error', 1, $reset->data()->username, $_SERVER['REMOTE_ADDR']);
                    echo 'Message could not be sent. Please contact administrator.';
                  }
                }
                $log->log('User '.$reset->data()->username.' changed password - forgot password.', 'general', 0, $reset->data()->username, $_SERVER['REMOTE_ADDR']);
                Redirect::to('login');
            }else{
                $errors = 'The username you entered is invalid.';
            }
        }else{
            $errors = '';
            foreach($validation->errors as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}

$appname = $db->get('vncp_settings', array('item', '=', 'app_name'))->first()->value;

echo $twig->render('forgotpassword.tpl', [
  'appname' => $appname,
  'gethttps' => gethttps(),
  'errors' => $errors,
  'formToken' => Token::generate(),
  'pagename' => 'Forgot Password'
]);

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
