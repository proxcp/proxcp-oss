<?php
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
    if(!$user->hasPermission('admin')) {
        Redirect::to('index');
    }
}
$db = DB::getInstance();
$log = new Logger();
if(Input::exists() && Input::get('action') == 'nodes') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'hostname' => array(
                'required' => true,
                'max' => 50,
                'unique_hostname' => true
            ),
            'username' => array(
                'required' => true,
                'max' => 10
            ),
            'password' => array(
                'required' => true
            ),
            'realm' => array(
                'required' => true,
                'max' => 3
            ),
            'port' => array(
                'required' => true,
                'max-num' => 65535,
                'min-num' => 1,
                'numonly' => true
            ),
            'name' => array(
                'required' => true,
                'max' => 50,
                'unique_hostname' => true
            ),
            'location' => array(
                'required' => true,
                'max' => 50
            ),
            'cpu' => array(
                'required' => true,
                'max' => 50
            ),
            'backup' => array(
                'required' => true,
                'max' => 25
            )
        ));
        if($validation->passed()) {
            $current_node_count = count($db->get('vncp_nodes', array('id', '!=', 0))->all());
            if($current_node_count < $GLOBALS['node_limit']) {
              $db->insert('vncp_nodes', array(
                  'hostname' => Input::get('hostname'),
                  'username' => Input::get('username'),
                  'password' => encryptValue(Input::get('password')),
                  'realm' => Input::get('realm'),
                  'port' => Input::get('port'),
                  'name' => Input::get('name'),
                  'location' => Input::get('location'),
                  'asn' => '11111',
                  'cpu' => Input::get('cpu'),
                  'mailing_enabled' => 0,
                  'backup_store' => Input::get('backup')
              ));
              $log->log('Added new node ' . Input::get('hostname'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
            }else{
              $errors = 'Too many nodes. Upgrade your license for more.';
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'edit_node') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'username' => array(
                'required' => true,
                'max' => 10
            ),
            'realm' => array(
                'required' => true,
                'max' => 3
            ),
            'port' => array(
                'required' => true,
                'max-num' => 65535,
                'min-num' => 1,
                'numonly' => true
            ),
            'location' => array(
                'required' => true,
                'max' => 50
            ),
            'cpu' => array(
                'required' => true,
                'max' => 50
            ),
            'backup' => array(
                'required' => true,
                'max' => 25
            ),
            'nid' => array(
              'numonly' => true,
              'min-num' => 1,
              'required' => true
            )
        ));
        if($validation->passed()) {
            $pwfield = escape(Input::get('password'));
            if(!empty($pwfield) && isset($pwfield)) {
              $db->update('vncp_nodes', escape(Input::get('nid')), array(
                'username' => escape(Input::get('username')),
                'password' => encryptValue(escape(Input::get('password'))),
                'realm' => escape(Input::get('realm')),
                'port' => (int)escape(Input::get('port')),
                'location' => escape(Input::get('location')),
                'asn' => '11111',
                'cpu' => escape(Input::get('cpu')),
                'mailing_enabled' => 0,
                'backup_store' => escape(Input::get('backup'))
              ));
            }else{
              $db->update('vncp_nodes', escape(Input::get('nid')), array(
                'username' => escape(Input::get('username')),
                'realm' => escape(Input::get('realm')),
                'port' => (int)escape(Input::get('port')),
                'location' => escape(Input::get('location')),
                'asn' => '11111',
                'cpu' => escape(Input::get('cpu')),
                'mailing_enabled' => 0,
                'backup_store' => escape(Input::get('backup'))
              ));
            }
            $log->log('Edited node ' . escape(Input::get('nid')), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
            Redirect::to('admin?action=nodes');
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'users' && Input::get('form_name') == 'new_user_form') {
  $validate = new Validate();
  $validation = $validate->check($_POST, array(
      'email' => array(
          'required' => true,
          'max' => 100,
          'unique' => true,
          'valemail' => true
      ),
      'group' => array(
          'numonly' => true,
          'min-num' => 1,
          'max-num' => 2
      )
  ));
  if($validation->passed()) {
      $plaintext_user_password = getRandomString(10);
      $user_salt = Hash::salt(32);
      $default_language = escape($db->get('vncp_settings', array('item', '=', 'default_language'))->first()->value);
      $db->insert('vncp_users', array(
          'email' => strtolower(Input::get('email')),
          'username' => strtolower(Input::get('email')),
          'password' => Hash::make($plaintext_user_password, $user_salt),
          'salt' => $user_salt,
          'tfa_enabled' => 0,
          'tfa_secret' => '',
          'group' => (int)Input::get('group'),
          'locked' => 0,
          'language' => $default_language
      ));
      $log->log('Added new user ' . Input::get('email'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
      $userCreatedSuccess = true;
  }else{
      $errors = '';
      foreach($validation->errors() as $error) {
          $errors .= $error . '<br />';
      }
  }
}else if(Input::exists() && Input::get('action') == 'users' && Input::get('form_name') == "change_username_form") {
  $validate = new Validate();
  $validation = $validate->check($_POST, array(
    'which_user' => array(
      'required' => true,
      'valemail' => true,
      'max' => 100,
      'min' => 4
    ),
    'username' => array(
      'required' => true,
      'valemail' => true,
      'max' => 100,
      'min' => 4,
      'unique' => true
    )
  ));
  if($validation->passed()) {
    $userExists = $db->get('vncp_users', array('username', '=', Input::get('which_user')));
    $userExists = $userExists->all();
    if(count($userExists) == 1) {
      $db->update('vncp_users', $userExists[0]->id, array(
        'username' => Input::get('username'),
        'email' => Input::get('username')
      ));
      $log->log('Changed user '.$userExists[0]->id.' username from '.Input::get('which_user').' to '.Input::get('username'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
    }else{
      $errors = 'Selected user is ambiguous.';
    }
  }else{
    $errors = '';
    foreach($validation->errors() as $error) {
        $errors .= $error . '<br />';
    }
  }
}else if(Input::exists() && Input::get('action') == 'log') {
  if(Token::check(Input::get('token'))) {
    $validate = new Validate();
    $validation = $validate->check($_POST, array(
      'logtype' => array(
        'required' => true,
        'max' => 7,
        'min' => 5
      ),
      'purgedate' => array(
        'required' => true,
        'min' => 8,
        'max' => 10
      )
    ));
    if($validation->passed() && Input::get('logtype') != 'default') {
      $log->purge(Input::get('logtype'), Input::get('purgedate'));
      $log->log('Purged ' . Input::get('logtype') . ' log entries before ' . Input::get('purgedate'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
      $logPurgedSuccess = true;
    }else{
      $errors = '';
      foreach($validation->errors() as $error) {
        $errors .= $error . '<br />';
      }
    }
  }
}else if(Input::exists() && Input::get('action') == 'settings' && Input::get('whatform') == 'general_settings') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'app_name' => array(
                'required' => true,
                'min' => 1,
                'max' => 100
            ),
            'enable_firewall' => array(
                'required' => true,
                'strbool' => true
            ),
            'enable_forward_dns' => array(
                'required' => true,
                'strbool' => true
            ),
            'enable_reverse_dns' => array(
                'required' => true,
                'strbool' => true
            ),
            'enable_notepad' => array(
                'required' => true,
                'strbool' => true
            ),
            'enable_status' => array(
                'required' => true,
                'strbool' => true
            ),
            'enable_panel_news' => array(
                'required' => true,
                'strbool' => true
            ),
            'support_ticket_url' => array(
                'required' => true,
                'min' => 10,
                'max' => 100
            ),
            'user_acl' => array(
                'required' => true,
                'strbool' => true
            ),
            'cloud_accounts' => array(
                'required' => true,
                'strbool' => true
            ),
            'vm_ipv6' => array(
                'required' => true,
                'strbool' => true
            ),
            'private_networking' => array(
                'required' => true,
                'strbool' => true
            ),
            'secondary_ips' => array(
                'required' => true,
                'strbool' => true
            ),
            'panel_news' => array(
                'max' => 280
            ),
            'whmurl' => array(
                'max' => 100
            ),
            'whmusername' => array(
                'max' => 100
            ),
            'whmapitoken' => array(
                'max' => 100
            ),
            'fdnslimit' => array(
                'numonly' => true,
                'min-num' => 1
            ),
            'fdnsblacklist' => array(
              'max' => 280
            ),
            'fdnsnameservers' => array(
              'max' => 280
            ),
            'ipv6lim' => array(
                'numonly' => true,
                'min-num' => 1
            ),
            'ipv6limsubnet' => array(
                'numonly' => true,
                'min-num' => 1
            ),
            'vmbackups' => array(
                'required' => true,
                'strbool' => true
            ),
            'backuplim' => array(
                'numonly' => true,
                'min-num' => 1
            ),
            'bw_auto_suspend' => array(
              'required' => true,
              'strbool' => true
            ),
            'enable_whmcs' => array(
              'required' => true,
              'strbool ' => true
            ),
            'whmcs_url' => array(
              'max' => 100
            ),
            'whmcs_id' => array(
              'max' => 100
            ),
            'whmcs_key' => array(
              'max' => 100
            ),
            'ipv6_mode' => array(
              'min' => 6,
              'max' => 6
            ),
            'default_language' => array(
              'required' => true,
              'min' => 7,
              'max' => 7
            ),
            'user_iso_upload' => array(
              'required' => true,
              'strbool' => true
            )
        ));
        if($validation->passed()) {
            $db->update('vncp_settings', 1, array('value' => Input::get('app_name')));
            $db->update('vncp_settings', 2, array('value' => Input::get('enable_firewall')));
            $db->update('vncp_settings', 3, array('value' => Input::get('enable_forward_dns')));
            $db->update('vncp_settings', 4, array('value' => Input::get('enable_reverse_dns')));
            $db->update('vncp_settings', 5, array('value' => Input::get('enable_notepad')));
            $db->update('vncp_settings', 6, array('value' => Input::get('enable_status')));
            $db->update('vncp_settings', 7, array('value' => Input::get('enable_panel_news')));
            $db->update('vncp_settings', 8, array('value' => Input::get('support_ticket_url')));
            $db->update('vncp_settings', 9, array('value' => Input::get('user_acl')));
            $db->update('vncp_settings', 10, array('value' => Input::get('cloud_accounts')));
            $db->update('vncp_settings', 11, array('value' => Input::get('vm_ipv6')));
            $db->update('vncp_settings', 12, array('value' => Input::get('private_networking')));
            $db->update('vncp_settings', 13, array('value' => Input::get('secondary_ips')));
            $db->update('vncp_settings', 14, array('value' => strip_tags(Input::get('panel_news'), '<br><a><p><ul><ol><li><strong>')));
            $db->update('vncp_settings', 15, array('value' => Input::get('whmurl')));
            $db->update('vncp_settings', 16, array('value' => Input::get('whmusername')));
            $db->update('vncp_settings', 17, array('value' => Input::get('whmapitoken')));
            $db->update('vncp_settings', 20, array('value' => Input::get('vmbackups')));
            $db->update('vncp_settings', 25, array('value' => Input::get('bw_auto_suspend')));
            $db->update('vncp_settings', 26, array('value' => Input::get('enable_whmcs')));
            $db->update('vncp_settings', 27, array('value' => Input::get('whmcs_url')));
            $db->update('vncp_settings', 28, array('value' => Input::get('whmcs_id')));
            $db->update('vncp_settings', 29, array('value' => Input::get('whmcs_key')));
            $db->update('vncp_settings', 30, array('value' => Input::get('ipv6mode')));
            $db->update('vncp_settings', 32, array('value' => explode('.', Input::get('default_language'))[0]));
            $db->update('vncp_settings', 33, array('value' => Input::get('user_iso_upload')));
            if(Input::get('fdnslimit') != '') {
              $db->update('vncp_settings', 18, array('value' => Input::get('fdnslimit')));
            }
            if(Input::get('ipv6lim') != '') {
              $db->update('vncp_settings', 19, array('value' => Input::get('ipv6lim')));
            }
            if(Input::get('ipv6limsubnet') != '') {
              $db->update('vncp_settings', 31, array('value' => Input::get('ipv6limsubnet')));
            }
            if(Input::get('backuplim') != '') {
              $db->update('vncp_settings', 21, array('value' => Input::get('backuplim')));
            }
            if(Input::get('fdnsblacklist') != '') {
              $db->update('vncp_settings', 23, array('value' => Input::get('fdnsblacklist')));
            }
            if(Input::get('fdnsnameservers') != '') {
              $db->update('vncp_settings', 24, array('value' => Input::get('fdnsnameservers')));
            }
            $log->log('Updated control panel general settings.', 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
            $adminSettingsUpdated = true;
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'settings' && Input::get('whatform') == 'mail_settings') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'from_email_addr' => array(
              'required' => true,
              'valemail' => true,
              'max' => 100,
              'min' => 5
            ),
            'mail_type' => array(
              'required' => true,
              'min' => 4,
              'max' => 7
            ),
            'from_email_addr_name' => array(
              'required' => true,
              'max' => 100,
              'min' => 5
            ),
            'smtp_host' => array(
              'max' => 100
            ),
            'smtp_port' => array(
              'numonly' => true,
              'min-num' => 1,
              'max-num' => 65535,
              'required' => true
            ),
            'smtp_username' => array(
              'max' => 100
            ),
            'smtp_password' => array(
              'max' => 100
            ),
            'smtp_type' => array(
              'required' => true,
              'min' => 4,
              'max' => 8
            )
        ));
        if($validation->passed()) {
            $db->update('vncp_settings', 22, array('value' => escape(Input::get('from_email_addr'))));
            $db->update('vncp_settings', 40, array('value' => escape(Input::get('mail_type'))));
            $db->update('vncp_settings', 34, array('value' => escape(Input::get('from_email_addr_name'))));
            $db->update('vncp_settings', 35, array('value' => escape(Input::get('smtp_host'))));
            $db->update('vncp_settings', 36, array('value' => escape(Input::get('smtp_port'))));
            $db->update('vncp_settings', 37, array('value' => escape(Input::get('smtp_username'))));
            if(!empty(Input::get('smtp_password'))) {
              $db->update('vncp_settings', 38, array('value' => encryptValue(Input::get('smtp_password'))));
            }else{
              $db->update('vncp_settings', 38, array('value' => ''));
            }
            $db->update('vncp_settings', 39, array('value' => escape(Input::get('smtp_type'))));
            $log->log('Updated control panel mail settings.', 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
            $adminSettingsUpdated = true;
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'natnodes') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'natnode' => array(
              'required' => true,
              'max' => 50,
              'unique_nat' => true
            ),
            'natnodeip' => array(
              'required' => true,
              'max' => 15,
              'ip' => true
            ),
            'natiprange' => array(
              'required' => true,
              'max' => 18,
              'min' => 9
            )
        ));
        if($validation->passed()) {
            if(isset($_POST['natunderstand'])) {
              $check1 = $db->get('vncp_nodes', array('name', '=', escape(Input::get('natnode'))))->all();
              $check2 = $db->get('vncp_tuntap', array('node', '=', escape(Input::get('natnode'))))->all();
              if(count($check1) == 1 && count($check2) == 1) {
                $checkResolve = gethostbyname($check1[0]->hostname);
                if($checkResolve == escape(Input::get('natnodeip'))) {
                  $cidr = explode("/", escape(Input::get('natiprange')));
                  if(count($cidr) == 2) {
                    $cidr = (int)$cidr[1];
                    $lastoctet = (int)substr(explode("/", escape(Input::get('natiprange')))[0], -1);
                    if(($cidr > 24 || $cidr < 16) || $lastoctet != 0) {
                      $errors = 'NAT IP Range must be between a /24 and a /16. The last octet must also be 0.';
                    }else{
                      $natrange = explode(".", explode("/", escape(Input::get('natiprange')))[0]);
                      if(count($natrange) == 4) {
                        $firstoctet = (int)$natrange[0];
                        $firstgood = false;
                        $secondoctet = (int)$natrange[1];
                        $secondgood = false;
                        $thirdoctet = (int)$natrange[2];
                        $thirdgood = false;
                        switch($firstoctet) {
                          case 10:
                            $firstgood = true;
                            if($secondoctet <= 255 && $secondoctet >= 0) $secondgood = true;
                            if($thirdoctet <= 255 && $thirdoctet >= 0) $thirdgood = true;
                            break;
                          case 172:
                            $firstgood = true;
                            if($secondoctet <= 31 && $secondoctet >= 16) $secondgood = true;
                            if($thirdoctet <= 255 && $thirdoctet >= 0) $thirdgood = true;
                            break;
                          case 192:
                            $firstgood = true;
                            if($secondoctet == 168) $secondgood = true;
                            if($thirdoctet <= 255 && $thirdoctet >= 0) $thirdgood = true;
                            break;
                          default:
                            $firstgood = false;
                            $secondgood = false;
                            $thirdgood = false;
                            break;
                        }
                        if($firstgood && $secondgood && $thirdgood) {
                          $natnetmask = '255.255.255.0';
                          $limit = 0;
                          switch($cidr) {
                            case 16:
                              $natnetmask = '255.255.0.0';
                              $limit = 65532;
                              break;
                            case 17:
                              $natnetmask = '255.255.128.0';
                              $limit = 32764;
                              break;
                            case 18:
                              $natnetmask = '255.255.192.0';
                              $limit = 16380;
                              break;
                            case 19:
                              $natnetmask = '255.255.224.0';
                              $limit = 8188;
                              break;
                            case 20:
                              $natnetmask = '255.255.240.0';
                              $limit = 4092;
                              break;
                            case 21:
                              $natnetmask = '255.255.248.0';
                              $limit = 2044;
                              break;
                            case 22:
                              $natnetmask = '255.255.252.0';
                              $limit = 1020;
                              break;
                            case 23:
                              $natnetmask = '255.255.254.0';
                              $limit = 508;
                              break;
                            case 24:
                              $natnetmask = '255.255.255.0';
                              $limit = 252;
                              break;
                            default:
                              $natnetmask = '255.255.255.0';
                              $limit = 0;
                              break;
                          }
                          $interfacesTemplate = "
auto vmbr10
iface vmbr10 inet static
        address ".(string)$firstoctet.".".(string)$secondoctet.".".(string)$thirdoctet.".1
        netmask ".$natnetmask."
        bridge_ports none
        bridge_stp off
        bridge_fd 0
        post-up echo 1 > /proc/sys/net/ipv4/ip_forward";
                          $indexTemplate = "
<!DOCTYPE html>
<html>
<head>
<title>Default Web Page</title>
<style>
    body {
        width: 35em;
        margin: 0 auto;
        font-family: Tahoma, Verdana, Arial, sans-serif;
    }
</style>
</head>
<body>
<h1>ProxCP default web page</h1>
<p>This is the default web page for <a href=\"https://proxcp.com\" target=\"_blank\">ProxCP</a>. If you see this page, the NAT domain proxy is successfully installed and
working.</p>
</body>
</html>";
                          $ssh = new SSH2($check1[0]->hostname, (int)$check2[0]->port);
                          if(!$ssh->login('root', decryptValue($check2[0]->password))) {
                            $log->log('Could not SSH to NAT node ' . escape(Input::get('natnode')), 'error', 1, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                            $errors = 'Could not SSH to NAT node.';
                          }else{
                            $ssh->exec("echo \"" . $interfacesTemplate . "\" >> /etc/network/interfaces");
                            $ssh->exec("iptables -t nat -A POSTROUTING -s '" . escape(Input::get('natiprange')) . "' -o vmbr0 -j MASQUERADE");
                            $ssh->exec("iptables -t raw -I PREROUTING -i fwbr+ -j CT --zone 1");
                            $ssh->exec("iptables-save > /root/proxcp-iptables.rules");
                            $ssh->exec("printf '[Service]\nType=oneshot\nRemainAfterExit=yes\nExecStart=/root/proxcp-iptables.sh\n\n[Install]\nWantedBy=multi-user.target\n\n[Unit]\nWants=network-online.target\nAfter=network-online.target\nWants=pvestatd.service\nWants=pveproxy.service\nWants=spiceproxy.service\nWants=pve-firewall.service\nWants=lxc.service\nAfter=pveproxy.service\nAfter=pvestatd.service\nAfter=spiceproxy.service\nAfter=pve-firewall.service\nAfter=lxc.service' > /etc/systemd/system/proxcp-iptables.service");
                            $ssh->exec("printf '#!/bin/bash\niptables-restore < /root/proxcp-iptables.rules' > /root/proxcp-iptables.sh");
                            $ssh->exec("chmod +x /root/proxcp-iptables.sh");
                            $ssh->exec("systemctl enable proxcp-iptables.service");
                            $ssh->exec("apt update --allow-unauthenticated --allow-insecure-repositories && apt -y install nginx");
                            $ssh->exec("service nginx restart");
                            $ssh->exec("printf -- '" . $indexTemplate . "' > /var/www/html/index.nginx-debian.html && mv /var/www/html/index.nginx-debian.html /var/www/html/index.html");
                            $ssh->exec("mkdir -p /etc/nginx/proxcp-nat-ssl");
                            $ssh->exec("service networking restart");
                            $ssh->disconnect();
                            $db->insert('vncp_nat', array(
                              'node' => escape(Input::get('natnode')),
                              'publicip' => escape(Input::get('natnodeip')),
                              'natcidr' => escape(Input::get('natiprange')),
                              'natnetmask' => $natnetmask,
                              'vmlimit' => $limit
                            ));
                            $log->log('Enabled NAT on node ' . escape(Input::get('natnode')), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                            $natCreatedSuccess = true;
                          }
                        }else{
                          $errors = 'NAT IP Range is not in RFC private ranges.';
                        }
                      }else{
                        $errors = 'NAT IP Range is not in CIDR format.';
                      }
                    }
                  }else{
                    $errors = 'NAT IP Range is not in CIDR format.';
                  }
                }else{
                  $errors = 'Node Public IP does not match the node\'s hostname A record.';
                }
              }else{
                $errors = 'Node does not exist.';
              }
            }else{
              $errors = 'Box must be checked to proceed.';
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'lxc') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'userid' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            ),
            'node' => array(
                'required' => true,
                'max' => 50
            ),
            'osfriendly' => array(
                'required' => true,
                'max' => 200
            ),
            'ostype' => array(
                'required' => true,
                'max' => 9
            ),
            'hb_account_id' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1,
                'unique_hbid' => true
            ),
            'poolid' => array(
                'required' => true,
                'max' => 100,
                'unique_poolid' => true
            ),
            'ipv4' => array(
                'required' => true,
                'max' => 18,
                'cidrformat' => true
            ),
            'ipv4gw' => array(
                'required' => true,
                'ip' => true,
                'max' => 15
            ),
            'ipv4_netmask' => array(
              'required' => true,
              'ip' => true,
              'max' => 15
            ),
            'hostname' => array(
                'required' => true,
                'max' => 100
            ),
            'storage_location' => array(
                'required' => true,
                'max' => 100
            ),
            'storage_size' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            ),
            'cpucores' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            ),
            'ram' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 32
            ),
            'bandwidth_limit' => array(
              'required' => true,
              'numonly' => true,
              'min-num' => 50,
              'max-num' => 102400
            ),
            'portspeed' => array(
              'required' => true,
              'numonly' => true,
              'min-num' => 0,
              'max-num' => 10000
            ),
            'setmacaddress' => array(
              'max' => 17,
              'macaddr' => true
            ),
            'lxcisnat' => array(
              'required' => true,
              'strbool' => true
            ),
            'setvlantag' => array(
              'required' => true,
              'numonly' => true,
              'min-num' => 0,
              'max-num' => 4094
            )
        ));
        if($validation->passed()) {
            if(Input::get('node') != 'default' && Input::get('osfriendly') != 'default' && Input::get('storage_location') != 'default' && Input::get('ostype') != 'default' && Input::get('userid') != 'default' && Input::get('lxcisnat') != 'default') {
                $users_results = $db->get('vncp_users', array('id', '=', escape(Input::get('userid'))));
                $users_results = $users_results->all();
                if(count($users_results) == 1) {
                    $lxcisnat = escape(Input::get('lxcisnat'));
                    $natpublicports = escape(Input::get('natpublicports'));
                    $natdomainproxy = escape(Input::get('natdomainproxy'));
                    if($lxcisnat == 'true') {
                      if(isset($natpublicports) && !empty($natpublicports) && (int)$natpublicports >= 1 && (int)$natpublicports <= 30) {
                        if(isset($natdomainproxy) && (int)$natdomainproxy >= 0 && (int)$natdomainproxy <= 15) {
                          $getNATCIDR = $db->get('vncp_nat', array('node', '=', escape(Input::get('node'))))->all();
                          if(count($getNATCIDR) == 1) {
                            $NATCIDR = $getNATCIDR[0]->natcidr;
                            if(IPInRange(explode('/', escape(Input::get('ipv4')))[0], $NATCIDR)) {
                              $node_results = $db->get('vncp_nodes', array('name', '=', escape(Input::get('node'))));
                              $node_data = $node_results->first();
                              $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
                              $noLogin = false;
                              if(!$pxAPI->login()) $noLogin = true;
                              if($noLogin == false) {
                                  $plaintext_password = getRandomString(12);
                                  $createpool = $pxAPI->post('/pools', array(
                                      'poolid' => Input::get('poolid')
                                  ));
                                  sleep(1);
                                  $createuser = $pxAPI->post('/access/users', array(
                                      'userid' => Input::get('poolid') . '@pve',
                                      'password' => $plaintext_password
                                  ));
                                  sleep(1);
                                  $setpoolperms = $pxAPI->put('/access/acl', array(
                                      'path' => '/pool/' . Input::get('poolid'),
                                      'users' => Input::get('poolid') . '@pve',
                                      'roles' => 'PVEVMUser'
                                  ));
                                  sleep(1);
                                  $allVMIDs = [];
                                  $getallKVM = $pxAPI->get('/nodes/'.Input::get('node').'/qemu');
                                  for($i = 0; $i < count($getallKVM); $i++) {
                                    $allVMIDs[] = (int)$getallKVM[$i]['vmid'];
                                  }
                                  $getallLXC = $pxAPI->get('/nodes/'.Input::get('node').'/lxc');
                                  for($i = 0; $i < count($getallLXC); $i++) {
                                    $allVMIDs[] = (int)$getallLXC[$i]['vmid'];
                                  }
                                  $getvmid = array_keys($allVMIDs, max($allVMIDs));
                                  $getvmid = ((int)$allVMIDs[$getvmid[0]]) + 1;
                      						if((int)$getvmid < 100) {
                      						  $getvmid = 100;
                      						}
                                  sleep(1);
                                  if(!empty(Input::get('setmacaddress'))) {
                                    $saved_macaddr = strtoupper(escape(Input::get('setmacaddress')));
                                  }else{
                                    $saved_macaddr = MacAddress::generateMacAddress();
                                  }
                                  $newlxc = array(
                                      'ostemplate' => escape(Input::get('osfriendly')),
                                      'vmid' => (int)$getvmid,
                                      'cmode' => 'tty',
                                      'cores' => (int)escape(Input::get('cpucores')),
                                      'cpulimit' => 0,
                                      'cpuunits' => 1024,
                                      'description' => explode('/', escape(Input::get('ipv4')))[0],
                                      'hostname' => escape(Input::get('hostname')),
                                      'memory' => (int)escape(Input::get('ram')),
                                      'onboot' => 0,
                                      'ostype' => escape(Input::get('ostype')),
                                      'password' => $plaintext_password,
                                      'pool' => escape(Input::get('poolid')),
                                      'protection' => 0,
                                      'rootfs' => ''.escape(Input::get('storage_location')).':'.escape(Input::get('storage_size')),
                                      'storage' => escape(Input::get('storage_location')),
                                      'swap' => 512,
                                      'tty' => 2,
                                      'unprivileged' => 1
                                  );
                                  if(empty(Input::get('portspeed')) || (int)escape(Input::get('portspeed')) <= 0) {
                                    $newlxc['net0'] = 'bridge=vmbr10,hwaddr='.$saved_macaddr.',ip='.Input::get('ipv4').',gw='.Input::get('ipv4gw').',ip6=auto,name=eth0,type=veth';
                                  }else{
                                    $newlxc['net0'] = 'bridge=vmbr10,hwaddr='.$saved_macaddr.',ip='.Input::get('ipv4').',gw='.Input::get('ipv4gw').',ip6=auto,name=eth0,type=veth,rate='.(string)escape(Input::get('portspeed'));
                                  }
                                  if((int)Input::get('setvlantag') > 0) {
                                    $newlxc['net0'] = $newlxc['net0'] . ',tag=' . (string)Input::get('setvlantag');
                                  }
                                  $createlxc = $pxAPI->post('/nodes/'.Input::get('node').'/lxc', $newlxc);
                                  if(!$createlxc) {
                                      $log->log('Could not create LXC. Proxmox API returned error.', 'error', 2, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                                      $errors = 'Could not create LXC. Proxmox API returned error.';
                                  }else{
                                      $allow_backups = escape($db->get('vncp_settings', array('item', '=', 'enable_backups'))->first()->value);
                                      $abvalue = -1;
                                      if($allow_backups == 'true') {
                                        $abvalue = 1;
                                      }else{
                                        $abvalue = 0;
                                      }
                                      $db->insert('vncp_lxc_ct', array(
                                          'user_id' => escape(Input::get('userid')),
                                          'node' => escape(Input::get('node')),
                                          'os' => escape(Input::get('ostype')),
                                          'hb_account_id' => escape(Input::get('hb_account_id')),
                                          'pool_id' => escape(Input::get('poolid')),
                                          'pool_password' => encryptValue($plaintext_password),
                                          'ip' => explode('/', Input::get('ipv4'))[0],
                                          'suspended' => 0,
                                          'allow_backups' => $abvalue,
                                          'fw_enabled_net0' => 0,
                                          'fw_enabled_net1' => 0,
                                          'has_net1' => 0,
                                          'tuntap' => 0,
                                          'onboot' => 0,
                                          'quotas' => 0
                                      ));
                                      $db->insert('vncp_ct_backups', array(
                                        'userid' => escape(Input::get('userid')),
                                        'hb_account_id' => escape(Input::get('hb_account_id')),
                                        'backuplimit' => -1
                                      ));
                                      $today = new DateTime();
                                      $today->add(new DateInterval('P30D'));
                                      $reset_date = $today->format('Y-m-d 00:00:00');
                                      $db->insert('vncp_bandwidth_monitor', array(
                                        'node' => escape(Input::get('node')),
                                        'pool_id' => escape(Input::get('poolid')),
                                        'hb_account_id' => escape(Input::get('hb_account_id')),
                                        'ct_type' => 'lxc',
                                        'current' => 0,
                                        'max' => ((int)escape(Input::get('bandwidth_limit')) * 1073741824),
                                        'reset_date' => $reset_date,
                                        'suspended' => 0
                                      ));
                                      $saved_network = explode('.', Input::get('ipv4gw'));
                                      $dhcpip = explode('/', escape(Input::get('ipv4')))[0];
                                      $saved_network = explode('.', escape(Input::get('ipv4gw')));
                                      $db->insert('vncp_dhcp', array(
                                        'mac_address' => $saved_macaddr,
                                        'ip' => $dhcpip,
                                        'gateway' => escape(Input::get('ipv4gw')),
                                        'netmask' => escape(Input::get('ipv4_netmask')),
                                        'network' => $saved_network[0].'.'.$saved_network[1].'.'.$saved_network[2].'.'.(string)((int)$saved_network[3] - 1),
                                        'type' => 0
                                      ));
                                      $db->insert('vncp_natforwarding', array(
                                        'user_id' => escape(Input::get('userid')),
                                        'node' => escape(Input::get('node')),
                                        'hb_account_id' => escape(Input::get('hb_account_id')),
                                        'avail_ports' => (int)$natpublicports,
                                        'ports' => '',
                                        'avail_domains' => (int)$natdomainproxy,
                                        'domains' => ''
                                      ));
                                      $log->log('Created new NAT LXC ' . $getvmid . ' on node ' . escape(Input::get('node')), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                                      $lxcCreatedSuccess = true;
                                  }
                              }else{
                                $errors = 'Could not login to Proxmox node.';
                              }
                            }else{
                              $errors = 'IPv4 is not in selected node\'s NAT range.';
                            }
                          }else{
                            $errors = 'Selected node is not NAT-enabled.';
                          }
                        }else{
                          $errors = 'NAT Domain Forwarding cannot be empty and must be between 0 - 15.';
                        }
                      }else{
                        $errors = 'NAT Public Ports cannot be empty and must be between 1 - 30.';
                      }
                    }else{
                      $node_results = $db->get('vncp_nodes', array('name', '=', escape(Input::get('node'))));
                      $node_data = $node_results->first();
                      $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
                      $noLogin = false;
                      if(!$pxAPI->login()) $noLogin = true;
                      if($noLogin == false) {
                          $plaintext_password = getRandomString(12);
                          $createpool = $pxAPI->post('/pools', array(
                              'poolid' => Input::get('poolid')
                          ));
                          sleep(1);
                          $createuser = $pxAPI->post('/access/users', array(
                              'userid' => Input::get('poolid') . '@pve',
                              'password' => $plaintext_password
                          ));
                          sleep(1);
                          $setpoolperms = $pxAPI->put('/access/acl', array(
                              'path' => '/pool/' . Input::get('poolid'),
                              'users' => Input::get('poolid') . '@pve',
                              'roles' => 'PVEVMUser'
                          ));
                          sleep(1);
                          $allVMIDs = [];
                          $getallKVM = $pxAPI->get('/nodes/'.Input::get('node').'/qemu');
                          for($i = 0; $i < count($getallKVM); $i++) {
                            $allVMIDs[] = (int)$getallKVM[$i]['vmid'];
                          }
                          $getallLXC = $pxAPI->get('/nodes/'.Input::get('node').'/lxc');
                          for($i = 0; $i < count($getallLXC); $i++) {
                            $allVMIDs[] = (int)$getallLXC[$i]['vmid'];
                          }
                          $getvmid = array_keys($allVMIDs, max($allVMIDs));
                          $getvmid = ((int)$allVMIDs[$getvmid[0]]) + 1;
              						if((int)$getvmid < 100) {
              						  $getvmid = 100;
              						}
                          sleep(1);
                          if(!empty(Input::get('setmacaddress'))) {
                            $saved_macaddr = strtoupper(escape(Input::get('setmacaddress')));
                          }else{
                            $saved_macaddr = MacAddress::generateMacAddress();
                          }
                          $newlxc = array(
                              'ostemplate' => escape(Input::get('osfriendly')),
                              'vmid' => (int)$getvmid,
                              'cmode' => 'tty',
                              'cores' => (int)escape(Input::get('cpucores')),
                              'cpulimit' => 0,
                              'cpuunits' => 1024,
                              'description' => explode('/', escape(Input::get('ipv4')))[0],
                              'hostname' => escape(Input::get('hostname')),
                              'memory' => (int)escape(Input::get('ram')),
                              'onboot' => 0,
                              'ostype' => escape(Input::get('ostype')),
                              'password' => $plaintext_password,
                              'pool' => escape(Input::get('poolid')),
                              'protection' => 0,
                              'rootfs' => ''.escape(Input::get('storage_location')).':'.escape(Input::get('storage_size')),
                              'storage' => escape(Input::get('storage_location')),
                              'swap' => 512,
                              'tty' => 2,
                              'unprivileged' => 1
                          );
                          if(empty(Input::get('portspeed')) || (int)escape(Input::get('portspeed')) <= 0) {
                            $newlxc['net0'] = 'bridge=vmbr0,hwaddr='.$saved_macaddr.',ip='.Input::get('ipv4').',gw='.Input::get('ipv4gw').',ip6=auto,name=eth0,type=veth';
                          }else{
                            $newlxc['net0'] = 'bridge=vmbr0,hwaddr='.$saved_macaddr.',ip='.Input::get('ipv4').',gw='.Input::get('ipv4gw').',ip6=auto,name=eth0,type=veth,rate='.(string)escape(Input::get('portspeed'));
                          }
                          if((int)Input::get('setvlantag') > 0) {
                            $newlxc['net0'] = $newlxc['net0'] . ',tag=' . (string)Input::get('setvlantag');
                          }
                          $createlxc = $pxAPI->post('/nodes/'.Input::get('node').'/lxc', $newlxc);
                          if(!$createlxc) {
                              $log->log('Could not create LXC. Proxmox API returned error.', 'error', 2, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                              $errors = 'Could not create LXC. Proxmox API returned error.';
                          }else{
                              $allow_backups = escape($db->get('vncp_settings', array('item', '=', 'enable_backups'))->first()->value);
                              $abvalue = -1;
                              if($allow_backups == 'true') {
                                $abvalue = 1;
                              }else{
                                $abvalue = 0;
                              }
                              $db->insert('vncp_lxc_ct', array(
                                  'user_id' => escape(Input::get('userid')),
                                  'node' => escape(Input::get('node')),
                                  'os' => escape(Input::get('ostype')),
                                  'hb_account_id' => escape(Input::get('hb_account_id')),
                                  'pool_id' => escape(Input::get('poolid')),
                                  'pool_password' => encryptValue($plaintext_password),
                                  'ip' => explode('/', escape(Input::get('ipv4')))[0],
                                  'suspended' => 0,
                                  'allow_backups' => $abvalue,
                                  'fw_enabled_net0' => 0,
                                  'fw_enabled_net1' => 0,
                                  'has_net1' => 0,
                                  'tuntap' => 0,
                                  'onboot' => 0,
                                  'quotas' => 0
                              ));
                              $db->insert('vncp_ct_backups', array(
                                'userid' => escape(Input::get('userid')),
                                'hb_account_id' => escape(Input::get('hb_account_id')),
                                'backuplimit' => -1
                              ));
                              $today = new DateTime();
                              $today->add(new DateInterval('P30D'));
                              $reset_date = $today->format('Y-m-d 00:00:00');
                              $db->insert('vncp_bandwidth_monitor', array(
                                'node' => escape(Input::get('node')),
                                'pool_id' => escape(Input::get('poolid')),
                                'hb_account_id' => escape(Input::get('hb_account_id')),
                                'ct_type' => 'lxc',
                                'current' => 0,
                                'max' => ((int)escape(Input::get('bandwidth_limit')) * 1073741824),
                                'reset_date' => $reset_date,
                                'suspended' => 0
                              ));
                              $saved_network = explode('.', Input::get('ipv4gw'));
                              $db->insert('vncp_dhcp', array(
                                'mac_address' => $saved_macaddr,
                                'ip' => explode('/', escape(Input::get('ipv4')))[0],
                                'gateway' => escape(Input::get('ipv4gw')),
                                'netmask' => escape(Input::get('ipv4_netmask')),
                                'network' => $saved_network[0].'.'.$saved_network[1].'.'.$saved_network[2].'.'.(string)((int)$saved_network[3] - 1),
                                'type' => 0
                              ));
                              $log->log('Created new LXC ' . $getvmid . ' on node ' . escape(Input::get('node')), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                              $lxcCreatedSuccess = true;
                          }
                      }else{
                        $errors = 'Could not login to Proxmox node.';
                      }
                    }
                }else{
                    $errors = 'User ID does not exist.';
                }
            }else{
                $errors = 'User ID, Node, Operating System, LXC Storage Location, and NAT cannot be default.';
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'kvm') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'userid' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            ),
            'node' => array(
                'required' => true,
                'max' => 50
            ),
            'osfriendly' => array(
                'required' => true,
                'max' => 200
            ),
            'ostype' => array(
                'required' => true,
                'max' => 7
            ),
            'hb_account_id' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1,
                'unique_hbid' => true
            ),
            'poolid' => array(
                'required' => true,
                'max' => 100,
                'unique_poolid' => true
            ),
            'ipv4' => array(
                'required' => true,
                'max' => 15,
                'ip' => true
            ),
            'ipv4_gateway' => array(
              'required' => true,
              'max' => 15,
              'ip' => true
            ),
            'ipv4_netmask' => array(
              'required' => true,
              'max' => 15,
              'ip' => true
            ),
            'hostname' => array(
                'required' => true,
                'max' => 100
            ),
            'storage_location' => array(
                'required' => true,
                'max' => 100
            ),
            'storage_size' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            ),
            'cpucores' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            ),
            'ram' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 32
            ),
            'nicdriver' => array(
                'required' => true,
                'max' => 7
            ),
            'cputype' => array(
                'required' => true,
                'max' => 7
            ),
            'storage_driver' => array(
                'required' => true,
                'max' => 7
            ),
            'os_installation_type' => array(
              'required' => true,
              'max' => 8
            ),
            'ostemplate' => array(
              'required' => true,
              'max' => 7
            ),
            'bandwidth_limit' => array(
              'required' => true,
              'numonly' => true,
              'min-num' => 50,
              'max-num' => 102400
            ),
            'portspeed' => array(
              'required' => true,
              'numonly' => true,
              'min-num' => 0,
              'max-num' => 10000
            ),
            'setmacaddress' => array(
              'max' => 17,
              'macaddr' => true
            ),
            'kvmisnat' => array(
              'required' => true,
              'strbool' => true
            ),
            'setvlantag' => array(
              'required' => true,
              'numonly' => true,
              'min-num' => 0,
              'max-num' => 4094
            )
        ));
        if($validation->passed()) {
            if(Input::get('os_installation_type') == 'iso') {
              if(Input::get('node') != 'default' && Input::get('osfriendly') != 'default' && Input::get('storage_location') != 'default'
              && Input::get('ostype') != 'default' && Input::get('nicdriver') != 'default' && Input::get('cputype') != 'default'
              && Input::get('storage_driver') != 'default' && Input::get('userid') != 'default' && Input::get('kvmisnat') != 'default') {
                  $users_results = $db->get('vncp_users', array('id', '=', escape(Input::get('userid'))));
                  $users_results = $users_results->all();
                  if(count($users_results) == 1) {
                      $kvmisnat = escape(Input::get('kvmisnat'));
                      $natpublicports = escape(Input::get('natpublicports'));
                      $natdomainproxy = escape(Input::get('natdomainproxy'));
                      if($kvmisnat == 'true') {
                        if(isset($natpublicports) && !empty($natpublicports) && (int)$natpublicports >= 1 && (int)$natpublicports <= 30) {
                          if(isset($natdomainproxy) && (int)$natdomainproxy >= 0 && (int)$natdomainproxy <= 15) {
                            $getNATCIDR = $db->get('vncp_nat', array('node', '=', escape(Input::get('node'))))->all();
                            if(count($getNATCIDR) == 1) {
                              $NATCIDR = $getNATCIDR[0]->natcidr;
                              if(IPInRange(escape(Input::get('ipv4')), $NATCIDR)) {
                                $node_results = $db->get('vncp_nodes', array('name', '=', escape(Input::get('node'))));
                                $node_data = $node_results->first();
                                $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
                                $noLogin = false;
                                if(!$pxAPI->login()) $noLogin = true;
                                if($noLogin == false) {
                                    $plaintext_password = getRandomString(12);
                                    $createpool = $pxAPI->post('/pools', array(
                                        'poolid' => Input::get('poolid')
                                    ));
                                    sleep(1);
                                    $createuser = $pxAPI->post('/access/users', array(
                                        'userid' => Input::get('poolid') . '@pve',
                                        'password' => $plaintext_password
                                    ));
                                    sleep(1);
                                    $setpoolperms = $pxAPI->put('/access/acl', array(
                                        'path' => '/pool/' . Input::get('poolid'),
                                        'users' => Input::get('poolid') . '@pve',
                                        'roles' => 'PVEVMUser'
                                    ));
                                    sleep(1);
                                    $allVMIDs = [];
                                    $getallKVM = $pxAPI->get('/nodes/'.Input::get('node').'/qemu');
                                    for($i = 0; $i < count($getallKVM); $i++) {
                                      $allVMIDs[] = (int)$getallKVM[$i]['vmid'];
                                    }
                                    $getallLXC = $pxAPI->get('/nodes/'.Input::get('node').'/lxc');
                                    for($i = 0; $i < count($getallLXC); $i++) {
                                      $allVMIDs[] = (int)$getallLXC[$i]['vmid'];
                                    }
                                    $getvmid = array_keys($allVMIDs, max($allVMIDs));
                                    $getvmid = ((int)$allVMIDs[$getvmid[0]]) + 1;
                      						  if((int)$getvmid < 100) {
                      							  $getvmid = 100;
                      						  }
                                    sleep(1);
                                    if(Input::get('storage_driver') == 'ide') {
                                        $bootdisk = 'ide0';
                                        $vga = 'std';
                                    }else{
                                        $bootdisk = 'virtio0';
                                        $vga = 'cirrus';
                                    }
                                    if(!empty(Input::get('setmacaddress'))) {
                                      $saved_macaddr = strtoupper(escape(Input::get('setmacaddress')));
                                    }else{
                                      $saved_macaddr = MacAddress::generateMacAddress();
                                    }
                                    $newvm = array(
                                        'vmid' => (int)$getvmid,
                                        'agent' => 0,
                                        'acpi' => 1,
                                        'balloon' => (int)Input::get('ram'),
                                        'boot' => 'cdn',
                                        'bootdisk' => $bootdisk,
                                        'cores' => (int)Input::get('cpucores'),
                                        'cpu' => Input::get('cputype'),
                                        'cpulimit' => '0',
                                        'cpuunits' => 1024,
                                        'description' => escape(Input::get('ipv4')),
                                        'hotplug' => '1',
                                        'ide2' => Input::get('osfriendly') . ',media=cdrom',
                                        'kvm' => 1,
                                        'localtime' => 1,
                                        'memory' => (int)Input::get('ram'),
                                        'name' => escape(Input::get('hostname')),
                                        'numa' => 0,
                                        'onboot' => 0,
                                        'ostype' => Input::get('ostype'),
                                        'pool' => escape(Input::get('poolid')),
                                        'protection' => 0,
                                        'reboot' => 1,
                                        'sockets' => 1,
                                        'storage' => Input::get('storage_location'),
                                        'tablet' => 1,
                                        'template' => 0,
                                        'vga' => $vga
                                    );
                                    if((int)escape(Input::get('portspeed')) <= 0) {
                                      $newvm['net0'] = 'bridge=vmbr10,' . escape(Input::get('nicdriver')) . '=' . $saved_macaddr;
                                    }else{
                                      $newvm['net0'] = 'bridge=vmbr10,' . escape(Input::get('nicdriver')) . '=' . $saved_macaddr . ',rate=' . (string)escape(Input::get('portspeed'));
                                    }
                                    if((int)Input::get('setvlantag') > 0) {
                                      $newvm['net0'] = $newvm['net0'] . ',tag=' . (string)Input::get('setvlantag');
                                    }
                                    if(Input::get('storage_driver') == 'ide') {
                                        $newvm['ide0'] = Input::get('storage_location') . ':' . Input::get('storage_size') . ',cache=writeback';
                                    }else{
                                        $newvm['virtio0'] = Input::get('storage_location') . ':' . Input::get('storage_size') . ',cache=writeback';
                                    }
                                    $createkvm = $pxAPI->post('/nodes/'.Input::get('node').'/qemu', $newvm);
                                    if(!$createkvm) {
                                        $log->log('Could not create NAT KVM. Proxmox API returned error.', 'error', 2, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                                        $errors = 'Could not create NAT KVM. Proxmox API returned error.';
                                    }else{
                                        $allow_backups = escape($db->get('vncp_settings', array('item', '=', 'enable_backups'))->first()->value);
                                        $abvalue = -1;
                                        if($allow_backups == 'true') {
                                          $abvalue = 1;
                                        }else{
                                          $abvalue = 0;
                                        }
                                        $db->insert('vncp_kvm_ct', array(
                                            'user_id' => escape(Input::get('userid')),
                                            'node' => escape(Input::get('node')),
                                            'os' => explode('/', escape(Input::get('osfriendly')))[1],
                                            'hb_account_id' => escape(Input::get('hb_account_id')),
                                            'pool_id' => escape(Input::get('poolid')),
                                            'pool_password' => encryptValue($plaintext_password),
                                            'ip' => escape(Input::get('ipv4')),
                                            'suspended' => 0,
                                            'allow_backups' => $abvalue,
                                            'fw_enabled_net0' => 0,
                                            'fw_enabled_net1' => 0,
                                            'has_net1' => 0,
                                            'onboot' => 0,
                                            'cloud_account_id' => 0,
                                            'cloud_hostname' => '',
                                            'from_template' => 0
                                        ));
                                        $db->insert('vncp_ct_backups', array(
                                          'userid' => escape(Input::get('userid')),
                                          'hb_account_id' => escape(Input::get('hb_account_id')),
                                          'backuplimit' => -1
                                        ));
                                        $today = new DateTime();
                                        $today->add(new DateInterval('P30D'));
                                        $reset_date = $today->format('Y-m-d 00:00:00');
                                        $db->insert('vncp_bandwidth_monitor', array(
                                          'node' => escape(Input::get('node')),
                                          'pool_id' => escape(Input::get('poolid')),
                                          'hb_account_id' => escape(Input::get('hb_account_id')),
                                          'ct_type' => 'qemu',
                                          'current' => 0,
                                          'max' => ((int)escape(Input::get('bandwidth_limit')) * 1073741824),
                                          'reset_date' => $reset_date,
                                          'suspended' => 0
                                        ));
                                        $saved_network = explode('.', Input::get('ipv4_gateway'));
                                        $saved_dhcp = $saved_network[0].'.'.$saved_network[1].'.'.$saved_network[2].'.'.(string)((int)$saved_network[3] - 1);
                                        $db->insert('vncp_dhcp', array(
                                          'mac_address' => $saved_macaddr,
                                          'ip' => escape(Input::get('ipv4')),
                                          'gateway' => escape(Input::get('ipv4_gateway')),
                                          'netmask' => escape(Input::get('ipv4_netmask')),
                                          'network' => $saved_dhcp,
                                          'type' => 0
                                        ));
                                        $db->insert('vncp_natforwarding', array(
                                          'user_id' => escape(Input::get('userid')),
                                          'node' => escape(Input::get('node')),
                                          'hb_account_id' => escape(Input::get('hb_account_id')),
                                          'avail_ports' => (int)$natpublicports,
                                          'ports' => '',
                                          'avail_domains' => (int)$natdomainproxy,
                                          'domains' => ''
                                        ));
                                        $fulldhcp = $db->get('vncp_dhcp', array('network', '=', $saved_dhcp))->all();
                                        if($dhcp_server = $db->get('vncp_dhcp_servers', array('dhcp_network', '=', $saved_dhcp))->first()) {
                                          $ssh = new SSH2($dhcp_server->hostname, (int)$dhcp_server->port);
                                          if(!$ssh->login('root', decryptValue($dhcp_server->password))) {
                                            $log->log('Could not SSH to DHCP server ' . $dhcp_server->hostname, 'error', 1, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                                          }else{
                                            $ssh->exec("printf 'ddns-update-style none;\n\n' > /root/dhcpd.test");
                                            $ssh->exec("printf 'option domain-name-servers 8.8.8.8, 8.8.4.4;\n\n' >> /root/dhcpd.test");
                                            $ssh->exec("printf 'default-lease-time 7200;\n' >> /root/dhcpd.test");
                                            $ssh->exec("printf 'max-lease-time 86400;\n\n' >> /root/dhcpd.test");
                                            $ssh->exec("printf 'log-facility local7;\n\n' >> /root/dhcpd.test");
                                            $ssh->exec("printf 'subnet ".$saved_dhcp." netmask ".$fulldhcp[0]->netmask." {}\n\n' >> /root/dhcpd.test");
                                            for($i = 0; $i < count($fulldhcp); $i++) {
                                              $ssh->exec("printf 'host ".$fulldhcp[$i]->id." {hardware ethernet ".$fulldhcp[$i]->mac_address.";fixed-address ".$fulldhcp[$i]->ip.";option routers ".$fulldhcp[$i]->gateway.";}\n' >> /root/dhcpd.test");
                                            }
                                            $ssh->exec("mv /root/dhcpd.test /etc/dhcp/dhcpd.conf && rm /root/dhcpd.test");
                                            $ssh->exec("service isc-dhcp-server restart");
                                            $ssh->disconnect();
                                          }
                                        }else{
                                          $log->log('No DHCP server exists for ' . $saved_dhcp, 'error', 1, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                                        }
                                        $log->log('Created new NAT KVM ' . $getvmid . ' on node ' . escape(Input::get('node')), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                                        $kvmCreatedSuccess = true;
                                    }
                                }else{
                                  $errors = 'Could not login to Proxmox node.';
                                }
                              }else{
                                $errors = 'IPv4 is not in selected node\'s NAT range.';
                              }
                            }else{
                              $errors = 'Selected node is not NAT-enabled.';
                            }
                          }else{
                            $errors = 'NAT Domain Forwarding cannot be empty and must be between 0 - 15.';
                          }
                        }else{
                          $errors = 'NAT Public Ports cannot be empty and must be between 1 - 30.';
                        }
                      }else{
                        $node_results = $db->get('vncp_nodes', array('name', '=', escape(Input::get('node'))));
                        $node_data = $node_results->first();
                        $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
                        $noLogin = false;
                        if(!$pxAPI->login()) $noLogin = true;
                        if($noLogin == false) {
                            $plaintext_password = getRandomString(12);
                            $createpool = $pxAPI->post('/pools', array(
                                'poolid' => Input::get('poolid')
                            ));
                            sleep(1);
                            $createuser = $pxAPI->post('/access/users', array(
                                'userid' => Input::get('poolid') . '@pve',
                                'password' => $plaintext_password
                            ));
                            sleep(1);
                            $setpoolperms = $pxAPI->put('/access/acl', array(
                                'path' => '/pool/' . Input::get('poolid'),
                                'users' => Input::get('poolid') . '@pve',
                                'roles' => 'PVEVMUser'
                            ));
                            sleep(1);
                            $allVMIDs = [];
                            $getallKVM = $pxAPI->get('/nodes/'.Input::get('node').'/qemu');
                            for($i = 0; $i < count($getallKVM); $i++) {
                              $allVMIDs[] = (int)$getallKVM[$i]['vmid'];
                            }
                            $getallLXC = $pxAPI->get('/nodes/'.Input::get('node').'/lxc');
                            for($i = 0; $i < count($getallLXC); $i++) {
                              $allVMIDs[] = (int)$getallLXC[$i]['vmid'];
                            }
                            $getvmid = array_keys($allVMIDs, max($allVMIDs));
                            $getvmid = ((int)$allVMIDs[$getvmid[0]]) + 1;
              						  if((int)$getvmid < 100) {
              							  $getvmid = 100;
              						  }
                            sleep(1);
                            if(Input::get('storage_driver') == 'ide') {
                                $bootdisk = 'ide0';
                                $vga = 'std';
                            }else{
                                $bootdisk = 'virtio0';
                                $vga = 'cirrus';
                            }
                            if(!empty(Input::get('setmacaddress'))) {
                              $saved_macaddr = strtoupper(escape(Input::get('setmacaddress')));
                            }else{
                              $saved_macaddr = MacAddress::generateMacAddress();
                            }
                            $newvm = array(
                                'vmid' => (int)$getvmid,
                                'agent' => 0,
                                'acpi' => 1,
                                'balloon' => (int)Input::get('ram'),
                                'boot' => 'cdn',
                                'bootdisk' => $bootdisk,
                                'cores' => (int)Input::get('cpucores'),
                                'cpu' => Input::get('cputype'),
                                'cpulimit' => '0',
                                'cpuunits' => 1024,
                                'description' => escape(Input::get('ipv4')),
                                'hotplug' => '1',
                                'ide2' => Input::get('osfriendly') . ',media=cdrom',
                                'kvm' => 1,
                                'localtime' => 1,
                                'memory' => (int)Input::get('ram'),
                                'name' => escape(Input::get('hostname')),
                                'numa' => 0,
                                'onboot' => 0,
                                'ostype' => Input::get('ostype'),
                                'pool' => Input::get('poolid'),
                                'protection' => 0,
                                'reboot' => 1,
                                'sockets' => 1,
                                'storage' => Input::get('storage_location'),
                                'tablet' => 1,
                                'template' => 0,
                                'vga' => $vga
                            );
                            if((int)escape(Input::get('portspeed')) <= 0) {
                              $newvm['net0'] = 'bridge=vmbr0,' . escape(Input::get('nicdriver')) . '=' . $saved_macaddr;
                            }else{
                              $newvm['net0'] = 'bridge=vmbr0,' . escape(Input::get('nicdriver')) . '=' . $saved_macaddr . ',rate=' . (string)escape(Input::get('portspeed'));
                            }
                            if((int)Input::get('setvlantag') > 0) {
                              $newvm['net0'] = $newvm['net0'] . ',tag=' . (string)Input::get('setvlantag');
                            }
                            if(Input::get('storage_driver') == 'ide') {
                                $newvm['ide0'] = Input::get('storage_location') . ':' . Input::get('storage_size') . ',cache=writeback';
                            }else{
                                $newvm['virtio0'] = Input::get('storage_location') . ':' . Input::get('storage_size') . ',cache=writeback';
                            }
                            $createkvm = $pxAPI->post('/nodes/'.Input::get('node').'/qemu', $newvm);
                            if(!$createkvm) {
                                $log->log('Could not create KVM. Proxmox API returned error.', 'error', 2, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                                $errors = 'Could not create KVM. Proxmox API returned error.';
                            }else{
                                $allow_backups = escape($db->get('vncp_settings', array('item', '=', 'enable_backups'))->first()->value);
                                $abvalue = -1;
                                if($allow_backups == 'true') {
                                  $abvalue = 1;
                                }else{
                                  $abvalue = 0;
                                }
                                $db->insert('vncp_kvm_ct', array(
                                    'user_id' => escape(Input::get('userid')),
                                    'node' => escape(Input::get('node')),
                                    'os' => explode('/', escape(Input::get('osfriendly')))[1],
                                    'hb_account_id' => escape(Input::get('hb_account_id')),
                                    'pool_id' => escape(Input::get('poolid')),
                                    'pool_password' => encryptValue($plaintext_password),
                                    'ip' => escape(Input::get('ipv4')),
                                    'suspended' => 0,
                                    'allow_backups' => $abvalue,
                                    'fw_enabled_net0' => 0,
                                    'fw_enabled_net1' => 0,
                                    'has_net1' => 0,
                                    'onboot' => 0,
                                    'cloud_account_id' => 0,
                                    'cloud_hostname' => '',
                                    'from_template' => 0
                                ));
                                $db->insert('vncp_ct_backups', array(
                                  'userid' => escape(Input::get('userid')),
                                  'hb_account_id' => escape(Input::get('hb_account_id')),
                                  'backuplimit' => -1
                                ));
                                $today = new DateTime();
                                $today->add(new DateInterval('P30D'));
                                $reset_date = $today->format('Y-m-d 00:00:00');
                                $db->insert('vncp_bandwidth_monitor', array(
                                  'node' => escape(Input::get('node')),
                                  'pool_id' => escape(Input::get('poolid')),
                                  'hb_account_id' => escape(Input::get('hb_account_id')),
                                  'ct_type' => 'qemu',
                                  'current' => 0,
                                  'max' => ((int)escape(Input::get('bandwidth_limit')) * 1073741824),
                                  'reset_date' => $reset_date,
                                  'suspended' => 0
                                ));
                                $saved_network = explode('.', Input::get('ipv4_gateway'));
                                $saved_dhcp = $saved_network[0].'.'.$saved_network[1].'.'.$saved_network[2].'.'.(string)((int)$saved_network[3] - 1);
                                $db->insert('vncp_dhcp', array(
                                  'mac_address' => $saved_macaddr,
                                  'ip' => escape(Input::get('ipv4')),
                                  'gateway' => escape(Input::get('ipv4_gateway')),
                                  'netmask' => escape(Input::get('ipv4_netmask')),
                                  'network' => $saved_dhcp,
                                  'type' => 0
                                ));
                                $fulldhcp = $db->get('vncp_dhcp', array('network', '=', $saved_dhcp))->all();
                                if($dhcp_server = $db->get('vncp_dhcp_servers', array('dhcp_network', '=', $saved_dhcp))->first()) {
                                  $ssh = new SSH2($dhcp_server->hostname, (int)$dhcp_server->port);
                                  if(!$ssh->login('root', decryptValue($dhcp_server->password))) {
                                    $log->log('Could not SSH to DHCP server ' . $dhcp_server->hostname, 'error', 1, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                                  }else{
                                    $ssh->exec("printf 'ddns-update-style none;\n\n' > /root/dhcpd.test");
                                    $ssh->exec("printf 'option domain-name-servers 8.8.8.8, 8.8.4.4;\n\n' >> /root/dhcpd.test");
                                    $ssh->exec("printf 'default-lease-time 7200;\n' >> /root/dhcpd.test");
                                    $ssh->exec("printf 'max-lease-time 86400;\n\n' >> /root/dhcpd.test");
                                    $ssh->exec("printf 'log-facility local7;\n\n' >> /root/dhcpd.test");
                                    $ssh->exec("printf 'subnet ".$saved_dhcp." netmask ".$fulldhcp[0]->netmask." {}\n\n' >> /root/dhcpd.test");
                                    for($i = 0; $i < count($fulldhcp); $i++) {
                                      $ssh->exec("printf 'host ".$fulldhcp[$i]->id." {hardware ethernet ".$fulldhcp[$i]->mac_address.";fixed-address ".$fulldhcp[$i]->ip.";option routers ".$fulldhcp[$i]->gateway.";}\n' >> /root/dhcpd.test");
                                    }
                                    $ssh->exec("mv /root/dhcpd.test /etc/dhcp/dhcpd.conf && rm /root/dhcpd.test");
                                    $ssh->exec("service isc-dhcp-server restart");
                                    $ssh->disconnect();
                                  }
                                }else{
                                  $log->log('No DHCP server exists for ' . $saved_dhcp, 'error', 1, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                                }
                                $log->log('Created new KVM ' . $getvmid . ' on node ' . escape(Input::get('node')), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                                $kvmCreatedSuccess = true;
                            }
                        }else{
                          $errors = 'Could not login to Proxmox node.';
                        }
                      }
                  }else{
                      $errors = 'User ID does not exist.';
                  }
              }else{
                  $errors = 'User ID, Node, Operating System, CPU type, drivers, KVM Storage Location, and KVM NAT cannot be default.';
              }
            }else if(Input::get('os_installation_type') == 'template') {
              if(Input::get('node') != 'default' && Input::get('ostemplate') != 'default' && Input::get('storage_location') != 'default'
              && Input::get('cputype') != 'default' && Input::get('userid') != 'default' && Input::get('kvmisnat') != 'default') {
                  $users_results = $db->get('vncp_users', array('id', '=', escape(Input::get('userid'))));
                  $users_results = $users_results->all();
                  if(count($users_results) == 1) {
                    $kvmisnat = escape(Input::get('kvmisnat'));
                    $natpublicports = escape(Input::get('natpublicports'));
                    $natdomainproxy = escape(Input::get('natdomainproxy'));
                    if($kvmisnat == 'true') {
                      if(isset($natpublicports) && !empty($natpublicports) && (int)$natpublicports >= 1 && (int)$natpublicports <= 30) {
                        if(isset($natdomainproxy) && (int)$natdomainproxy >= 0 && (int)$natdomainproxy <= 15) {
                          $getNATCIDR = $db->get('vncp_nat', array('node', '=', escape(Input::get('node'))))->all();
                          if(count($getNATCIDR) == 1) {
                            $NATCIDR = $getNATCIDR[0]->natcidr;
                            if(IPInRange(escape(Input::get('ipv4')), $NATCIDR)) {
                              $node_results = $db->get('vncp_nodes', array('name', '=', escape(Input::get('node'))));
                              $node_data = $node_results->first();
                              $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
                              $noLogin = false;
                              if(!$pxAPI->login()) $noLogin = true;
                              if($noLogin == false) {
                                  $plaintext_password = getRandomString(12);
                                  $cipassword = getRandomString(16);
                                  $createpool = $pxAPI->post('/pools', array(
                                      'poolid' => Input::get('poolid')
                                  ));
                                  sleep(1);
                                  $createuser = $pxAPI->post('/access/users', array(
                                      'userid' => Input::get('poolid') . '@pve',
                                      'password' => $plaintext_password
                                  ));
                                  sleep(1);
                                  $setpoolperms = $pxAPI->put('/access/acl', array(
                                      'path' => '/pool/' . Input::get('poolid'),
                                      'users' => Input::get('poolid') . '@pve',
                                      'roles' => 'PVEVMUser'
                                  ));
                                  sleep(1);
                                  $allVMIDs = [];
                                  $getallKVM = $pxAPI->get('/nodes/'.Input::get('node').'/qemu');
                                  for($i = 0; $i < count($getallKVM); $i++) {
                                    $allVMIDs[] = (int)$getallKVM[$i]['vmid'];
                                  }
                                  $getallLXC = $pxAPI->get('/nodes/'.Input::get('node').'/lxc');
                                  for($i = 0; $i < count($getallLXC); $i++) {
                                    $allVMIDs[] = (int)$getallLXC[$i]['vmid'];
                                  }
                                  $getvmid = array_keys($allVMIDs, max($allVMIDs));
                                  $getvmid = ((int)$allVMIDs[$getvmid[0]]) + 1;
                    						  if((int)$getvmid < 100) {
                    							  $getvmid = 100;
                    						  }
                                  sleep(1);
                                  $clone_type = 'qcow2';
                                  if(strpos(Input::get('storage_location'), 'lvm') !== false) {
                                    $clone_type = 'raw';
                                  }
                                  $newvm = array(
                                      'newid' => (int)$getvmid,
                                      'description' => escape(Input::get('ipv4')),
                                      'format' => $clone_type,
                                      'full' => 1,
                                      'name' => escape(Input::get('hostname')),
                                      'pool' => Input::get('poolid'),
                                      'storage' => Input::get('storage_location')
                                  );
                                  $clonevm = $db->get('vncp_kvm_templates', array('id', '=', escape(Input::get('ostemplate'))))->first();
                                  $createkvm = $pxAPI->post('/nodes/'.Input::get('node').'/qemu/'.$clonevm->vmid.'/clone', $newvm);
                                  if(!$createkvm) {
                                      $log->log('Could not clone NAT KVM. Proxmox API returned error.', 'error', 2, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                                      $errors = 'Could not clone NAT KVM. Proxmox API returned error.';
                                  }else{
                                    $db->insert('vncp_pending_clone', array(
                                      'node' => escape(Input::get('node')),
                                      'upid' => $createkvm,
                                      'hb_account_id' => escape(Input::get('hb_account_id')),
                                      'data' => json_encode(array(
                                        'vmid' => $getvmid,
                                        'cores' => escape(Input::get('cpucores')),
                                        'cpu' => escape(Input::get('cputype')),
                                        'memory' => escape(Input::get('ram')),
                                        'cipassword' => encryptValue($cipassword),
                                        'storage_size' => escape(Input::get('storage_size')),
                                        'cvmtype' => $clonevm->type,
                                        'gateway' => escape(Input::get('ipv4_gateway')),
                                        'ip' => escape(Input::get('ipv4')),
                                        'netmask' => escape(Input::get('ipv4_netmask')),
                                        'portspeed' => escape(Input::get('portspeed')),
                                        'setmacaddress' => strtoupper(escape(Input::get('setmacaddress'))),
                                        'vlantag' => escape(Input::get('setvlantag'))
                                      ))
                                    ));
                                    $allow_backups = escape($db->get('vncp_settings', array('item', '=', 'enable_backups'))->first()->value);
                                    $abvalue = -1;
                                    if($allow_backups == 'true') {
                                      $abvalue = 1;
                                    }else{
                                      $abvalue = 0;
                                    }
                                    $db->insert('vncp_kvm_ct', array(
                                        'user_id' => escape(Input::get('userid')),
                                        'node' => escape(Input::get('node')),
                                        'os' => $clonevm->friendly_name,
                                        'hb_account_id' => escape(Input::get('hb_account_id')),
                                        'pool_id' => escape(Input::get('poolid')),
                                        'pool_password' => encryptValue($plaintext_password),
                                        'ip' => escape(Input::get('ipv4')),
                                        'suspended' => 0,
                                        'allow_backups' => $abvalue,
                                        'fw_enabled_net0' => 0,
                                        'fw_enabled_net1' => 0,
                                        'has_net1' => 0,
                                        'onboot' => 0,
                                        'cloud_account_id' => 0,
                                        'cloud_hostname' => '',
                                        'from_template' => 1
                                    ));
                                    $db->insert('vncp_ct_backups', array(
                                      'userid' => escape(Input::get('userid')),
                                      'hb_account_id' => escape(Input::get('hb_account_id')),
                                      'backuplimit' => -1
                                    ));
                                    $today = new DateTime();
                                    $today->add(new DateInterval('P30D'));
                                    $reset_date = $today->format('Y-m-d 00:00:00');
                                    $db->insert('vncp_bandwidth_monitor', array(
                                      'node' => escape(Input::get('node')),
                                      'pool_id' => escape(Input::get('poolid')),
                                      'hb_account_id' => escape(Input::get('hb_account_id')),
                                      'ct_type' => 'qemu',
                                      'current' => 0,
                                      'max' => ((int)escape(Input::get('bandwidth_limit')) * 1073741824),
                                      'reset_date' => $reset_date,
                                      'suspended' => 0
                                    ));
                                    $db->insert('vncp_natforwarding', array(
                                      'user_id' => escape(Input::get('userid')),
                                      'node' => escape(Input::get('node')),
                                      'hb_account_id' => escape(Input::get('hb_account_id')),
                                      'avail_ports' => (int)$natpublicports,
                                      'ports' => '',
                                      'avail_domains' => (int)$natdomainproxy,
                                      'domains' => ''
                                    ));
                                    $log->log('Created new NAT KVM ' . $getvmid . ' on node ' . escape(Input::get('node')), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                                    $kvmCreatedSuccess = true;
                                  }
                              }else{
                                $errors = 'Could not login to Proxmox node.';
                              }
                            }else{
                              $errors = 'IPv4 is not in selected node\'s NAT range.';
                            }
                          }else{
                            $errors = 'Selected node is not NAT-enabled.';
                          }
                        }else{
                          $errors = 'NAT Domain Forwarding cannot be empty and must be between 0 - 15.';
                        }
                      }else{
                        $errors = 'NAT Public Ports cannot be empty and must be between 1 - 30.';
                      }
                    }else{
                      $node_results = $db->get('vncp_nodes', array('name', '=', escape(Input::get('node'))));
                      $node_data = $node_results->first();
                      $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
                      $noLogin = false;
                      if(!$pxAPI->login()) $noLogin = true;
                      if($noLogin == false) {
                          $plaintext_password = getRandomString(12);
                          $cipassword = getRandomString(16);
                          $createpool = $pxAPI->post('/pools', array(
                              'poolid' => Input::get('poolid')
                          ));
                          sleep(1);
                          $createuser = $pxAPI->post('/access/users', array(
                              'userid' => Input::get('poolid') . '@pve',
                              'password' => $plaintext_password
                          ));
                          sleep(1);
                          $setpoolperms = $pxAPI->put('/access/acl', array(
                              'path' => '/pool/' . Input::get('poolid'),
                              'users' => Input::get('poolid') . '@pve',
                              'roles' => 'PVEVMUser'
                          ));
                          sleep(1);
                          $allVMIDs = [];
                          $getallKVM = $pxAPI->get('/nodes/'.Input::get('node').'/qemu');
                          for($i = 0; $i < count($getallKVM); $i++) {
                            $allVMIDs[] = (int)$getallKVM[$i]['vmid'];
                          }
                          $getallLXC = $pxAPI->get('/nodes/'.Input::get('node').'/lxc');
                          for($i = 0; $i < count($getallLXC); $i++) {
                            $allVMIDs[] = (int)$getallLXC[$i]['vmid'];
                          }
                          $getvmid = array_keys($allVMIDs, max($allVMIDs));
                          $getvmid = ((int)$allVMIDs[$getvmid[0]]) + 1;
            						  if((int)$getvmid < 100) {
            							  $getvmid = 100;
            						  }
                          sleep(1);
                          $clone_type = 'qcow2';
                          if(strpos(Input::get('storage_location'), 'lvm') !== false) {
                            $clone_type = 'raw';
                          }
                          $newvm = array(
                              'newid' => (int)$getvmid,
                              'description' => escape(Input::get('ipv4')),
                              'format' => $clone_type,
                              'full' => 1,
                              'name' => escape(Input::get('hostname')),
                              'pool' => Input::get('poolid'),
                              'storage' => Input::get('storage_location')
                          );
                          $clonevm = $db->get('vncp_kvm_templates', array('id', '=', escape(Input::get('ostemplate'))))->first();
                          $createkvm = $pxAPI->post('/nodes/'.Input::get('node').'/qemu/'.$clonevm->vmid.'/clone', $newvm);
                          if(!$createkvm) {
                              $log->log('Could not clone KVM. Proxmox API returned error.', 'error', 2, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                              $errors = 'Could not clone KVM. Proxmox API returned error.';
                          }else{
                            $db->insert('vncp_pending_clone', array(
                              'node' => escape(Input::get('node')),
                              'upid' => $createkvm,
                              'hb_account_id' => escape(Input::get('hb_account_id')),
                              'data' => json_encode(array(
                                'vmid' => $getvmid,
                                'cores' => escape(Input::get('cpucores')),
                                'cpu' => escape(Input::get('cputype')),
                                'memory' => escape(Input::get('ram')),
                                'cipassword' => encryptValue($cipassword),
                                'storage_size' => escape(Input::get('storage_size')),
                                'cvmtype' => $clonevm->type,
                                'gateway' => escape(Input::get('ipv4_gateway')),
                                'ip' => escape(Input::get('ipv4')),
                                'netmask' => escape(Input::get('ipv4_netmask')),
                                'portspeed' => escape(Input::get('portspeed')),
                                'setmacaddress' => strtoupper(escape(Input::get('setmacaddress'))),
                                'vlantag' => escape(Input::get('setvlantag'))
                              ))
                            ));
                            $allow_backups = escape($db->get('vncp_settings', array('item', '=', 'enable_backups'))->first()->value);
                            $abvalue = -1;
                            if($allow_backups == 'true') {
                              $abvalue = 1;
                            }else{
                              $abvalue = 0;
                            }
                            $db->insert('vncp_kvm_ct', array(
                                'user_id' => escape(Input::get('userid')),
                                'node' => escape(Input::get('node')),
                                'os' => $clonevm->friendly_name,
                                'hb_account_id' => escape(Input::get('hb_account_id')),
                                'pool_id' => escape(Input::get('poolid')),
                                'pool_password' => encryptValue($plaintext_password),
                                'ip' => escape(Input::get('ipv4')),
                                'suspended' => 0,
                                'allow_backups' => $abvalue,
                                'fw_enabled_net0' => 0,
                                'fw_enabled_net1' => 0,
                                'has_net1' => 0,
                                'onboot' => 0,
                                'cloud_account_id' => 0,
                                'cloud_hostname' => '',
                                'from_template' => 1
                            ));
                            $db->insert('vncp_ct_backups', array(
                              'userid' => escape(Input::get('userid')),
                              'hb_account_id' => escape(Input::get('hb_account_id')),
                              'backuplimit' => -1
                            ));
                            $today = new DateTime();
                            $today->add(new DateInterval('P30D'));
                            $reset_date = $today->format('Y-m-d 00:00:00');
                            $db->insert('vncp_bandwidth_monitor', array(
                              'node' => escape(Input::get('node')),
                              'pool_id' => escape(Input::get('poolid')),
                              'hb_account_id' => escape(Input::get('hb_account_id')),
                              'ct_type' => 'qemu',
                              'current' => 0,
                              'max' => ((int)escape(Input::get('bandwidth_limit')) * 1073741824),
                              'reset_date' => $reset_date,
                              'suspended' => 0
                            ));
                            $log->log('Created new KVM ' . $getvmid . ' on node ' . escape(Input::get('node')), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                            $kvmCreatedSuccess = true;
                          }
                      }else{
                        $errors = 'Could not login to Proxmox node.';
                      }
                    }
                  }else{
                      $errors = 'User ID does not exist.';
                  }
              }else{
                  $errors = 'User ID, Node, Operating System, CPU type, KVM Storage Location, and KVM NAT cannot be default.';
              }
            }else{
              $errors = 'Invalid OS installation type.';
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'lxctemp' && Input::get('form_name') == 'new_lxc_template') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'fname' => array(
                'required' => true,
                'max' => 100
            ),
            'volid' => array(
                'required' => true,
                'max' => 200
            )
        ));
        if($validation->passed()) {
            $db->insert('vncp_lxc_templates', array(
                'friendly_name' => Input::get('fname'),
                'volid' => Input::get('volid'),
                'content' => 'vztmpl'
            ));
            $log->log('Added new LXC template ' . Input::get('fname'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
            $lxcTempSuccess = true;
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'lxctemp' && Input::get('form_name') == 'import_lxc_template') {
    $lxcImportCount = 0;
    for($i = 0; $i < count($_POST['field']); $i++) {
      $temp_fname = escape(trim($_POST['field'][$i]['fname']));
      $temp_volid = escape(trim($_POST['field'][$i]['volid']));
      if(!empty($temp_fname) && !is_numeric($temp_fname) && strlen($temp_fname) <= 100) {
        if(!empty($temp_volid) && !is_numeric($temp_volid) && strlen($temp_volid) <= 200 && strpos($temp_volid, ":vztmpl/") !== false) {
          $db->insert('vncp_lxc_templates', array(
            'friendly_name' => $temp_fname,
            'volid' => $temp_volid,
            'content' => 'vztmpl'
          ));
          $log->log('Imported new LXC template ' . $temp_fname, 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
          $lxcImportCount++;
        }
      }
    }
    if($lxcImportCount > 0) {
      $lxcImportSuccess = true;
    }
}else if(Input::exists() && Input::get('action') == 'api') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'apiip' => array(
                'required' => true,
                'ip' => true,
                'max' => 15,
                'min' => 7
            )
        ));
        if($validation->passed()) {
            $db->insert('vncp_api', array(
                'api_id' => md5(getRandomString(32)),
                'api_key' => md5(getRandomString(32)),
                'api_ip' => Input::get('apiip')
            ));
            $log->log('Added new API pair for ' . Input::get('apiip'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'kvmtemp') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'fname' => array(
                'required' => true,
                'max' => 100
            ),
            'template_vmid' => array(
                'required' => true,
                'numonly' => true,
                'max-num' => 999999999,
                'min-num' => 100
            ),
            'template_type' => array(
              'required' => true,
              'max' => 7,
              'min' => 5
            ),
            'template_node' => array(
              'required' => true,
              'max' => 50
            )
        ));
        if($validation->passed()) {
          if(Input::get('template_node') != 'default' && Input::get('template_type') != 'default') {
            $db->insert('vncp_kvm_templates', array(
                'vmid' => Input::get('template_vmid'),
                'friendly_name' => Input::get('fname'),
                'type' => Input::get('template_type'),
                'node' => Input::get('template_node')
            ));
            $log->log('Added new KVM template ' . Input::get('fname'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
            $kvmTempSuccess = true;
          }else{
            $errors = 'Form values cannot be default.';
          }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'kvmiso' && Input::get('form_name') == 'new_kvm_iso') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'fname' => array(
                'required' => true,
                'max' => 100
            ),
            'volid' => array(
                'required' => true,
                'max' => 200
            )
        ));
        if($validation->passed()) {
            $db->insert('vncp_kvm_isos', array(
                'friendly_name' => Input::get('fname'),
                'volid' => Input::get('volid'),
                'content' => 'iso'
            ));
            $log->log('Added new KVM ISO ' . Input::get('fname'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
            $kvmIsoSuccess = true;
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'kvmiso' && Input::get('form_name') == 'import_kvm_iso') {
    $kvmImportCount = 0;
    for($i = 0; $i < count($_POST['field']); $i++) {
      $temp_fname = escape(trim($_POST['field'][$i]['fname']));
      $temp_volid = escape(trim($_POST['field'][$i]['volid']));
      if(!empty($temp_fname) && !is_numeric($temp_fname) && strlen($temp_fname) <= 100) {
        if(!empty($temp_volid) && !is_numeric($temp_volid) && strlen($temp_volid) <= 200 && strpos($temp_volid, ":iso/") !== false) {
          $db->insert('vncp_kvm_isos', array(
            'friendly_name' => $temp_fname,
            'volid' => $temp_volid,
            'content' => 'iso'
          ));
          $log->log('Imported new KVM ISO ' . $temp_fname, 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
          $kvmImportCount++;
        }
      }
    }
    if($kvmImportCount > 0) {
      $kvmImportSuccess = true;
    }
}else if(Input::exists() && Input::get('action') == 'tuntap') {
  if(Token::check(Input::get('token'))) {
      $validate = new Validate();
      $validation = $validate->check($_POST, array(
          'tuntapnode' => array(
              'required' => true,
              'max' => 50,
              'unique_node' => true
          ),
          'rpassword' => array(
              'required' => true,
              'max' => 200
          ),
          'sshport' => array(
            'required' => true,
            'min-num' => 1,
            'max-num' => 65535,
            'numonly' => true
          )
      ));
      if($validation->passed()) {
        $checkExists = $db->get('vncp_nodes', array('name', '=', escape(Input::get('tuntapnode'))))->all();
        if(count($checkExists) == 1) {
          $db->insert('vncp_tuntap', array(
            'node' => escape(Input::get('tuntapnode')),
            'password' => encryptValue(escape(Input::get('rpassword'))),
            'port' => escape(Input::get('sshport'))
          ));
          $log->log('Add new TUN/TAP credentials.', 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
        }else{
          $errors = 'Node does not exist.';
        }
      }else{
          $errors = '';
          foreach($validation->errors() as $error) {
              $errors .= $error . '<br />';
          }
      }
  }
}else if(Input::exists() && Input::get('action') == 'dhcp') {
  if(Token::check(Input::get('token'))) {
      $validate = new Validate();
      $validation = $validate->check($_POST, array(
          'dhcphostname' => array(
              'required' => true,
              'max' => 100,
              'min' => 3
          ),
          'rpassword' => array(
              'required' => true,
              'max' => 200
          ),
          'sshport' => array(
            'required' => true,
            'min-num' => 1,
            'max-num' => 65535,
            'numonly' => true
          ),
          'dhcpnetwork' => array(
            'required' => true,
            'max' => 15,
            'min' => 7,
            'ip' => true
          )
      ));
      if($validation->passed()) {
          $validate_network = $db->get('vncp_dhcp', array('network', '=', Input::get('dhcpnetwork')))->all();
          if(count($validate_network) > 0) {
            $db->insert('vncp_dhcp_servers', array(
              'hostname' => Input::get('dhcphostname'),
              'password' => encryptValue(Input::get('rpassword')),
              'port' => Input::get('sshport'),
              'dhcp_network' => Input::get('dhcpnetwork')
            ));
            $log->log('Added new DHCP server.', 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
          }else{
            $errors = 'DHCP network does not exist.';
          }
      }else{
          $errors = '';
          foreach($validation->errors() as $error) {
              $errors .= $error . '<br />';
          }
      }
  }
}else if(Input::exists() && Input::get('action') == 'cloud' && Input::get('whatform') == 'createcloud') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'userid' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            ),
            'hb_account_id' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1,
                'unique_hbid' => true
            ),
            'poolid' => array(
                'required' => true,
                'max' => 50,
                'unique_poolid' => true
            ),
            'node' => array(
                'required' => true,
                'max' => 50
            ),
            'ipv4' => array(
                'required' => true,
                'max' => 1000
            ),
            'cpucores' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            ),
            'cputype' => array(
                'required' => true,
                'max' => 6
            ),
            'ram' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 32
            ),
            'storage_size' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            )
        ));
        if($validation->passed()) {
            if(Input::get('node') != 'default' && Input::get('cputype') != 'default' && Input::get('userid') != 'default') {
                $users_results = $db->get('vncp_users', array('id', '=', escape(Input::get('userid'))));
                $users_results = $users_results->all();
                if(count($users_results) == 1) {
                    $node_results = $db->get('vncp_nodes', array('name', '=', Input::get('node')));
                    $node_data = $node_results->first();
                    $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
                    $noLogin = false;
                    if(!$pxAPI->login()) $noLogin = true;
                    if($noLogin == false) {
                        $plaintext_password = getRandomString(12);
                        $createpool = $pxAPI->post('/pools', array(
                            'poolid' => Input::get('poolid')
                        ));
                        sleep(1);
                        $createuser = $pxAPI->post('/access/users', array(
                            'userid' => Input::get('poolid') . '@pve',
                            'password' => $plaintext_password
                        ));
                        sleep(1);
                        $setpoolperms = $pxAPI->put('/access/acl', array(
                            'path' => '/pool/' . Input::get('poolid'),
                            'users' => Input::get('poolid') . '@pve',
                            'roles' => 'PVEVMUser'
                        ));
                        sleep(1);
                        $db->insert('vncp_kvm_cloud', array(
                            'user_id' => Input::get('userid'),
                            'nodes' => Input::get('node'),
                            'hb_account_id' => Input::get('hb_account_id'),
                            'pool_id' => Input::get('poolid'),
                            'pool_password' => encryptValue($plaintext_password),
                            'memory' => (int)Input::get('ram'),
                            'cpu_cores' => (int)Input::get('cpucores'),
                            'cpu_type' => Input::get('cputype'),
                            'disk_size' => (int)Input::get('storage_size'),
                            'ip_limit' => count(explode(';', Input::get('ipv4'))),
                            'ipv4' => Input::get('ipv4'),
                            'avail_memory' => (int)Input::get('ram'),
                            'avail_cpu_cores' => (int)Input::get('cpucores'),
                            'avail_disk_size' => (int)Input::get('storage_size'),
                            'avail_ip_limit' => count(explode(';', Input::get('ipv4'))),
                            'avail_ipv4' => Input::get('ipv4'),
                            'suspended' => 0
                        ));
                        $log->log('Created new cloud account ' . Input::get('poolid'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                        $cloudCreatedSuccess = true;
                    }
                }else{
                    $errors = 'User ID does not exist.';
                }
            }else{
                $errors = 'User ID, Node, and CPU type cannot be default.';
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'lxckvmprops') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'hbaccountid' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            ),
            'userid' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            ),
            'vmnode' => array(
                'required' => true,
                'max' => 50
            ),
            'vmos' => array(
                'required' => true,
                'max' => 250
            ),
            'vmip' => array(
                'required' => true,
                'ip' => true,
                'max' => 15
            ),
            'vmip_gateway' => array(
              'required' => true,
              'ip' => true,
              'max' => 15
            ),
            'vmip_netmask' => array(
              'required' => true,
              'ip' => true,
              'max' => 15
            ),
            'vm_backups' => array(
              'numonly' => true,
              'min-num' => 0,
              'max-num' => 1
            ),
            'vm_backup_override' => array(
              'numonly' => true,
              'min-num' => -1,
              'max-num' => 1000
            )
        ));
        if($validation->passed()) {
            $verifyuid = $db->get('vncp_users', array('id', '=', escape(Input::get('userid'))))->all();
            if(count($verifyuid) != 1) {
                $errors = 'User ID does not exist.';
            }else{
                $verifynode = $db->get('vncp_nodes', array('name', '=', escape(Input::get('vmnode'))))->all();
                if(count($verifynode) != 1) {
                    $errors = 'Node name does not exist.';
                }else{
                    $verifyoldip = $db->get('vncp_dhcp', array('ip', '=', escape(Input::get('vmip_old'))))->all();
                    if(count($verifyoldip) != 1) {
                      $errors = 'VM IP does not exist.';
                    }else{
                      $verifypoolpw = true;
                      $dopoolpw = false;
                      $vmpp = Input::get('vm_poolpw');
                      if(isset($vmpp)) {
                        if(strlen(Input::get('vm_poolpw')) > 32 || strlen(Input::get('vm_poolpw')) < 12 || !ctype_alnum(Input::get('vm_poolpw'))) {
                          $verifypoolpw = false;
                        }else{
                          $dopoolpw = true;
                        }
                        if(empty(Input::get('vm_poolpw'))) {
                          $verifypoolpw = true;
                        }
                      }
                      if($verifypoolpw == false) {
                        $errors = 'Proxmox Pool Password must be between 12 and 32 characters long and be alphanumeric only.';
                      }else{
                        $lxccheck = $db->get('vncp_lxc_ct', array('hb_account_id', '=', escape(Input::get('hbaccountid'))))->all();
                        if(count($lxccheck) != 1) {
                            $db->updatevm_aid('vncp_kvm_ct', escape(Input::get('hbaccountid')), array(
                                'user_id' => escape(Input::get('userid')),
                                'node' => escape(Input::get('vmnode')),
                                'os' => escape(Input::get('vmos')),
                                'ip' => escape(Input::get('vmip')),
                                'allow_backups' => escape(Input::get('vm_backups'))
                            ));
                            $db->updatevm_aid('vncp_ct_backups', escape(Input::get('hbaccountid')), array(
                              'backuplimit' => escape(Input::get('vm_backup_override'))
                            ));
                            if(Input::get('vmip') != Input::get('vmip_old')) {
                              $db->updatevm_aid('vncp_ipv4_pool', escape(Input::get('hbaccountid')), array(
                                'user_id' => 0,
                                'hb_account_id' => 0,
                                'available' => 1
                              ));
                              $db->update_address('vncp_ipv4_pool', escape(Input::get('vmip')), array(
                                'user_id' => escape(Input::get('userid')),
                                'hb_account_id' => escape(Input::get('hbaccountid')),
                                'available' => 0
                              ));
                            }else{
                              $db->updatevm_aid('vncp_ipv4_pool', escape(Input::get('hbaccountid')), array(
                                'user_id' => escape(Input::get('userid'))
                              ));
                            }
                            if($dopoolpw == true) {
                              $db->updatevm_aid('vncp_kvm_ct', escape(Input::get('hbaccountid')), array(
                                'pool_password' => encryptValue(escape(Input::get('vm_poolpw')))
                              ));
                            }
                            $db->updatevm_aid('vncp_ipv6_assignment', escape(Input::get('hbaccountid')), array(
                              'user_id' => escape(Input::get('userid'))
                            ));
                            $db->updatevm_aid('vncp_natforwarding', escape(Input::get('hbaccountid')), array(
                              'user_id' => escape(Input::get('userid'))
                            ));
                            $db->updatevm_aid('vncp_private_pool', escape(Input::get('hbaccountid')), array(
                              'user_id' => escape(Input::get('userid'))
                            ));
                            $db->updatevm_aid('vncp_secondary_ips', escape(Input::get('hbaccountid')), array(
                              'user_id' => escape(Input::get('userid'))
                            ));
                            $db->update_dhcp('vncp_dhcp', Input::get('vmip_old'), array(
                              'gateway' => escape(Input::get('vmip_gateway')),
                              'netmask' => escape(Input::get('vmip_netmask')),
                              'ip' => escape(Input::get('vmip'))
                            ));
                            $savednetwork = $db->get('vncp_dhcp', array('ip', '=', Input::get('vmip')))->all();
                            $fulldhcp = $db->get('vncp_dhcp', array('network', '=', $savednetwork[0]->network));
                            if($dhcp_server = $db->get('vncp_dhcp_servers', array('dhcp_network', '=', $savednetwork[0]->network))->first()) {
                              $ssh = new SSH2($dhcp_server->hostname, (int)$dhcp_server->port);
                              if(!$ssh->login('root', decryptValue($dhcp_server->password))) {
                                $log->log('Could not SSH to DHCP server ' . $dhcp_server->hostname, 'error', 1, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                              }else{
                                $ssh->exec("printf 'ddns-update-style none;\n\n' > /root/dhcpd.test");
                                $ssh->exec("printf 'option domain-name-servers 8.8.8.8, 8.8.4.4;\n\n' >> /root/dhcpd.test");
                                $ssh->exec("printf 'default-lease-time 7200;\n' >> /root/dhcpd.test");
                                $ssh->exec("printf 'max-lease-time 86400;\n\n' >> /root/dhcpd.test");
                                $ssh->exec("printf 'log-facility local7;\n\n' >> /root/dhcpd.test");
                                $ssh->exec("printf 'subnet ".$savednetwork[0]->network." netmask ".$fulldhcp[0]->netmask." {}\n\n' >> /root/dhcpd.test");
                                for($i = 0; $i < count($fulldhcp); $i++) {
                                  $ssh->exec("printf 'host ".$fulldhcp[$i]->id." {hardware ethernet ".$fulldhcp[$i]->mac_address.";fixed-address ".$fulldhcp[$i]->ip.";option routers ".$fulldhcp[$i]->gateway.";}\n' >> /root/dhcpd.test");
                                }
                                $ssh->exec("mv /root/dhcpd.test /etc/dhcp/dhcpd.conf && rm /root/dhcpd.test");
                                $ssh->exec("service isc-dhcp-server restart");
                                $ssh->disconnect();
                              }
                            }else{
                              $log->log('No DHCP server exists for ' . $savednetwork[0]->network, 'error', 1, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                            }
                        }else{
                            $db->updatevm_aid('vncp_lxc_ct', escape(Input::get('hbaccountid')), array(
                                'user_id' => escape(Input::get('userid')),
                                'node' => escape(Input::get('vmnode')),
                                'os' => escape(Input::get('vmos')),
                                'ip' => escape(Input::get('vmip')),
                                'allow_backups' => escape(Input::get('vm_backups'))
                            ));
                            $db->updatevm_aid('vncp_ct_backups', escape(Input::get('hbaccountid')), array(
                              'backuplimit' => escape(Input::get('vm_backup_override'))
                            ));
                            if(Input::get('vmip') != Input::get('vmip_old')) {
                              $db->updatevm_aid('vncp_ipv4_pool', escape(Input::get('hbaccountid')), array(
                                'user_id' => 0,
                                'hb_account_id' => 0,
                                'available' => 1
                              ));
                              $db->update_address('vncp_ipv4_pool', escape(Input::get('vmip')), array(
                                'user_id' => escape(Input::get('userid')),
                                'hb_account_id' => escape(Input::get('hbaccountid')),
                                'available' => 0
                              ));
                            }else{
                              $db->updatevm_aid('vncp_ipv4_pool', escape(Input::get('hbaccountid')), array(
                                'user_id' => escape(Input::get('userid'))
                              ));
                            }
                            if($dopoolpw == true) {
                              $db->updatevm_aid('vncp_lxc_ct', escape(Input::get('hbaccountid')), array(
                                'pool_password' => encryptValue(escape(Input::get('vm_poolpw')))
                              ));
                            }
                            $db->updatevm_aid('vncp_ipv6_assignment', escape(Input::get('hbaccountid')), array(
                              'user_id' => escape(Input::get('userid'))
                            ));
                            $db->updatevm_aid('vncp_natforwarding', escape(Input::get('hbaccountid')), array(
                              'user_id' => escape(Input::get('userid'))
                            ));
                            $db->updatevm_aid('vncp_private_pool', escape(Input::get('hbaccountid')), array(
                              'user_id' => escape(Input::get('userid'))
                            ));
                            $db->updatevm_aid('vncp_secondary_ips', escape(Input::get('hbaccountid')), array(
                              'user_id' => escape(Input::get('userid'))
                            ));
                            $db->update_dhcp('vncp_dhcp', escape(Input::get('vmip_old')), array(
                              'gateway' => escape(Input::get('vmip_gateway')),
                              'netmask' => escape(Input::get('vmip_netmask')),
                              'ip' => escape(Input::get('vmip'))
                            ));
                        }
                        if($dopoolpw == true) {
                          $pxAPI = new PVE2_API($verifynode[0]->hostname, $verifynode[0]->username, $verifynode[0]->realm, decryptValue($verifynode[0]->password));
                          $noLogin = false;
                          if(!$pxAPI->login()) $noLogin = true;
                          if($noLogin == false) {
                            $updatepw = $pxAPI->put('/access/password', array(
                              'userid' => escape(Input::get('vm_poolname')).'@pve',
                              'password' => escape(Input::get('vm_poolpw'))
                            ));
                            sleep(1);
                            $log->log('Updated VPS pool password of account ID ' . escape(Input::get('hbaccountid')), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                          }
                        }
                        $log->log('Updated VPS properties of account ID ' . escape(Input::get('hbaccountid')), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                        $editedSuccess = true;
                      }
                    }
                }
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'ip2') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'userid' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            ),
            'hbaccountid' => array(
                'required' => true,
                'numonly' => true,
                'min-num' => 1
            ),
            'ipaddr' => array(
                'required' => true,
                'ip' => true,
                'max' => 15
            )
        ));
        if($validation->passed()) {
            if(Input::get('userid') != 'default') {
              $verifyuser = $db->get('vncp_users', array('id', '=', escape(Input::get('userid'))))->all();
              if(count($verifyuser) != 1) {
                  $errors = 'User ID does not exist.';
              }else{
                $ckvm = $db->get('vncp_kvm_ct', array('hb_account_id', '=', Input::get('hbaccountid')))->all();
                if(count($ckvm) != 1 || $ckvm[0]->user_id != Input::get('userid')) {
                    $errors = 'Billing Account ID does not exist for KVM or user IDs do not match.';
                }else{
                    $db->insert('vncp_secondary_ips', array(
                        'user_id' => Input::get('userid'),
                        'hb_account_id' => Input::get('hbaccountid'),
                        'address' => Input::get('ipaddr')
                    ));
                    $log->log('Added secondary IP to account ID ' . Input::get('hbaccountid'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                    $ipAddedSuccess = true;
                }
              }
            }else{
              $errors = 'User ID cannot be default.';
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'private') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'cidr' => array(
                'required' => true,
                'max' => 18
            )
        ));
        if($validation->passed()) {
            $privnodes = $_POST['privnodes'];
            if(count($privnodes) <= 0) {
              $errors = 'Nodes field is required.';
            }else{
              $exists = true;
              foreach($privnodes as $privnode) {
                $exist = $db->get('vncp_nodes', array('name', '=', $privnode))->all();
                if(count($exist) != 1)
                  $exists = false;
              }
              if($exists == false) {
                $errors = 'One or more selected nodes do not exist.';
              }else{
                $classC = explode('/', Input::get('cidr'))[1];
                if((int)$classC >= 24 && (int)$classC <= 30) {
                    switch($classC) {
                      case 24:
                        $privatenm = '255.255.255.0';
                      break;
                      case 25:
                        $privatenm = '255.255.255.128';
                      break;
                      case 26:
                        $privatenm = '255.255.255.192';
                      break;
                      case 27:
                        $privatenm = '255.255.255.224';
                      break;
                      case 28:
                        $privatenm = '255.255.255.240';
                      break;
                      case 29:
                        $privatenm = '255.255.255.248';
                      break;
                      default:
                        $privatenm = '255.255.255.252';
                    }
                    $range = cidrToRange(Input::get('cidr'));
                    $first = explode('.', $range[0])[3];
                    $last = explode('.', $range[1])[3];
                    $prefix = explode('.', $range[0]);
                    $prefix = $prefix[0] . '.' . $prefix[1] . '.' . $prefix[2] . '.';
                    for($i = $first + 2; $i < $last; $i++) {
                        $db->insert('vncp_private_pool', array(
                            'user_id' => 0,
                            'hb_account_id' => 0,
                            'address' => $prefix . (string)$i,
                            'available' => 1,
                            'netmask' => $privatenm,
                            'nodes' => implode(';', $privnodes)
                        ));
                    }
                    $log->log('Added new private pool CIDR ' . Input::get('cidr'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                    $privAddedSuccess = true;
                }else{
                    $errors = 'CIDR cannot be larger than a /24 or smaller than a /30.';
                }
              }
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'ipv4' && Input::get('form_name') == 'add_cidr') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'cidr' => array(
                'required' => true,
                'max' => 18
            )
        ));
        if($validation->passed()) {
            $privnodes = $_POST['pubnodes'];
            if(count($privnodes) <= 0) {
              $errors = 'Nodes field is required.';
            }else{
              $exists = true;
              foreach($privnodes as $privnode) {
                $exist = $db->get('vncp_nodes', array('name', '=', $privnode))->all();
                if(count($exist) != 1)
                  $exists = false;
              }
              if($exists == false) {
                $errors = 'One or more selected nodes do not exist.';
              }else{
                $classC = explode('/', Input::get('cidr'))[1];
                if((int)$classC >= 24 && (int)$classC <= 30) {
                    switch($classC) {
                      case 24:
                        $privatenm = '255.255.255.0';
                      break;
                      case 25:
                        $privatenm = '255.255.255.128';
                      break;
                      case 26:
                        $privatenm = '255.255.255.192';
                      break;
                      case 27:
                        $privatenm = '255.255.255.224';
                      break;
                      case 28:
                        $privatenm = '255.255.255.240';
                      break;
                      case 29:
                        $privatenm = '255.255.255.248';
                      break;
                      default:
                        $privatenm = '255.255.255.252';
                    }
                    $range = cidrToRange(Input::get('cidr'));
                    $first = explode('.', $range[0])[3];
                    $last = explode('.', $range[1])[3];
                    $prefix = explode('.', $range[0]);
                    $prefix = $prefix[0] . '.' . $prefix[1] . '.' . $prefix[2] . '.';
                    for($i = $first + 2; $i < $last; $i++) {
                        $db->insert('vncp_ipv4_pool', array(
                            'user_id' => 0,
                            'hb_account_id' => 0,
                            'address' => $prefix . (string)$i,
                            'available' => 1,
                            'netmask' => $privatenm,
                            'nodes' => implode(';', $privnodes),
                            'gateway' => $prefix . ($first+1)
                        ));
                    }
                    $log->log('Added new IPv4 pool CIDR ' . Input::get('cidr'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                    $pubAddedSuccess = true;
                }else{
                    $errors = 'CIDR cannot be larger than a /24 or smaller than a /30.';
                }
              }
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
}else if(Input::exists() && Input::get('action') == 'ipv4' && Input::get('form_name') == 'add_single') {
    $validate = new Validate();
    $validation = $validate->check($_POST, array(
        'ipaddress' => array(
            'required' => true,
            'max' => 15,
            'ip' => true
        ),
        'ipgateway' => array(
          'required' => true,
          'max' => 15,
          'ip' => true
        ),
        'ipnetmask' => array(
          'required' => true,
          'max' => 15,
          'ip' => true
        )
    ));
    if($validation->passed()) {
        $privnodes = $_POST['pubnodes'];
        if(count($privnodes) <= 0) {
          $errors = 'Nodes field is required.';
        }else{
          $exists = true;
          foreach($privnodes as $privnode) {
            $exist = $db->get('vncp_nodes', array('name', '=', $privnode))->all();
            if(count($exist) != 1)
              $exists = false;
          }
          if($exists == false) {
            $errors = 'One or more selected nodes do not exist.';
          }else{
                $db->insert('vncp_ipv4_pool', array(
                    'user_id' => 0,
                    'hb_account_id' => 0,
                    'address' => escape(Input::get('ipaddress')),
                    'available' => 1,
                    'netmask' => escape(Input::get('ipnetmask')),
                    'nodes' => implode(';', $privnodes),
                    'gateway' => escape(Input::get('ipgateway'))
                ));
                $log->log('Added new IPv4 pool single ' . Input::get('ipaddress'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                $pubAddedSuccess = true;
          }
        }
    }else{
        $errors = '';
        foreach($validation->errors() as $error) {
            $errors .= $error . '<br />';
        }
    }
}else if(Input::exists() && Input::get('action') == 'ipv6') {
    if(Token::check(Input::get('token'))) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'v6cidr' => array(
                'required' => true,
                'max' => 45
            )
        ));
        if($validation->passed()) {
            $v6nodes = $_POST['v6nodes'];
            if(count($v6nodes) <= 0) {
                $errors = 'Nodes field is required.';
            }else{
                $exists = true;
                foreach($v6nodes as $v6node) {
                    $exist = $db->get('vncp_nodes', array('name', '=', $v6node))->all();
                    if(count($exist) != 1)
                        $exists = false;
                }
                if($exists == false) {
                    $errors = 'One or more selected nodes do not exist.';
                }else{
                    $class = explode('/', Input::get('v6cidr'))[1];
                    if((int)$class > 64) {
                        $errors = 'IPv6 subnet must be a /64 or larger.';
                    }else{
                        $db->insert('vncp_ipv6_pool', array(
                            'subnet' => Input::get('v6cidr'),
                            'nodes' => implode(';', $v6nodes)
                        ));
                        $log->log('Added new IPv6 subnet ' . Input::get('v6cidr'), 'admin', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                        $ipv6AddedSuccess = true;
                    }
                }
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }
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
    <title><?php $appname = escape($db->get('vncp_settings', array('item', '=', 'app_name'))->first()->value); echo $appname; ?> - Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/font-awesome.min.css" />
    <link rel="stylesheet" href="css/main.css" />
    <link rel="stylesheet" href="//cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css" />
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,300,700' rel='stylesheet' type='text/css' />
    <link href='css/bootstrap-select.min.css' rel='stylesheet' type='text/css' />
    <link href='css/custom.css' rel='stylesheet' type='text/css' />
    <link rel="icon" type="image/png" href="favicon.ico" />
    <script src="js/vendor/modernizr-2.8.3-respond-1.4.2.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.1.1/socket.io.slim.js"></script>
</head>
<body>
    <div id="socket_error" class="socket_error" style="visibility:hidden;padding:0px;"></div>
    <!--[if lt IE 8]>
        <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->
    <?php
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
    echo $twig->render('menu_top.tpl', [
      'adminBase' => Config::get('admin/base'),
      'enable_firewall' => $enable_firewall,
      'enable_forward_dns' => $enable_forward_dns,
      'enable_reverse_dns' => $enable_reverse_dns,
      'enable_notepad' => $enable_notepad,
      'enable_status' => $enable_status,
      'isAdmin' => $isAdmin,
      'L' => $L
    ]);
    ?>
    <?php
    $constants = false;
    if(defined('constant') || defined('constant-fw')) {
        $constants = true;
    }
    $appname = $db->get('vncp_settings', array('item', '=', 'app_name'))->first()->value;
    $aclsetting = $db->get('vncp_settings', array('item', '=', 'user_acl'))->first()->value;
    echo $twig->render('menu_sub.tpl', [
      'constants' => $constants,
      'username' => $user->data()->username,
      'appname' => $appname,
      'aclsetting' => $aclsetting,
      'L' => $L
    ]);
    ?>
    <div class="container">
    	<div class="row">
            <div class="col-md-12">
                <?php
                if(!empty($errors)) {
                    echo '<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><strong>Errors: </strong>' . $errors . '</div>';
                }
                $action = escape(Input::get('action'));
                switch($action) {
                	case 'users':
                		require_once('includes/admin/users.php');
                		break;
                  case 'admaccess':
                    require_once('includes/admin/admaccess.php');
                    break;
                	case 'nodes':
                		require_once('includes/admin/nodes.php');
                		break;
                  case 'edit_node':
                    require_once('includes/admin/edit_node.php');
                    break;
                	case 'lxc':
                		require_once('includes/admin/lxc.php');
                		break;
                	case 'cloud':
                		require_once('includes/admin/cloud.php');
                		break;
                    case 'settings':
                        require_once('includes/admin/settings.php');
                        break;
                    case 'acl':
                        require_once('includes/admin/acl.php');
                        break;
                    case 'lxctemp':
                        require_once('includes/admin/lxctemp.php');
                        break;
                    case 'kvm':
                        require_once('includes/admin/kvm.php');
                        break;
                    case 'kvmiso':
                        require_once('includes/admin/kvmiso.php');
                        break;
                    case 'kvmiso_custom':
                        require_once('includes/admin/kvmiso_custom.php');
                        break;
                    case 'kvmtemp':
                        require_once('includes/admin/kvmtemp.php');
                        break;
                    case 'fdns':
                        require_once('includes/admin/fdns.php');
                        break;
                    case 'rdns':
                        require_once('includes/admin/rdns.php');
                        break;
                    case 'ipv6':
                        require_once('includes/admin/ipv6.php');
                        break;
                    case 'private':
                        require_once('includes/admin/private.php');
                        break;
                    case 'ip2':
                        require_once('includes/admin/ip2.php');
                        break;
                    case 'lxckvmprops':
                        require_once('includes/admin/lxckvmprops.php');
                        break;
                    case 'log':
                        require_once('includes/admin/log.php');
                        break;
                    case 'tuntap':
                      require_once('includes/admin/tuntap.php');
                      break;
                    case 'natnodes':
                      require_once('includes/admin/natnodes.php');
                      break;
                    case 'dhcp':
                      require_once('includes/admin/dhcp.php');
                      break;
                    case 'bandwidth':
                      require_once('includes/admin/bandwidth.php');
                      break;
                    case 'api':
                      require_once('includes/admin/api.php');
                      break;
                    case 'ipv4':
                      require_once('includes/admin/ipv4.php');
                      break;
                	default:
                		require_once('includes/admin/dashboard.php');
                		break;
                }
                ?>
            </div>
    	</div>
    </div>
    <input type="hidden" value="<?php echo Session::get('user'); ?>" id="user" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.2.min.js"><\/script>')</script>
    <script src="js/vendor/bootstrap.min.js"></script>
    <script src="//cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script src="js/vendor/bootstrap-select.min.js"></script>
    <script src="js/vendor/jquery-confirm.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/buttons.js"></script>
    <script src="js/io.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>
