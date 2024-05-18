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
$cpanel_host = $db->get('vncp_settings', array('item', '=', 'whm_url'))->first()->value;
$cphtemp = explode(':', $cpanel_host);
if(count($cphtemp) == 2) {
  $cpanel_host = $cpanel_host . ':2087';
}
$cpanel = new Cpanel($cpanel_host, $db->get('vncp_settings', array('item', '=', 'whm_username'))->first()->value, $db->get('vncp_settings', array('item', '=', 'whm_api_token'))->first()->value);
if(Input::exists()) {
    if(strpos(Input::get('formid'), 'v4radd') !== false) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'ipaddress' => array(
                'required' => true,
                'ip' => true
            ),
            'hostname' => array(
                'required' => true,
                'max' => 250,
                'min' => 6
            )
        ));
        if($validation->passed()) {
            $ovzcheck = $db->get('vncp_lxc_ct', array('ip', '=', Input::get('ipaddress')));
            $ovzdata = $ovzcheck->first();
            $kvmcheck = $db->get('vncp_kvm_ct', array('ip', '=', Input::get('ipaddress')));
            $kvmdata = $kvmcheck->first();
            $secondarycheck = $db->get('vncp_secondary_ips', array('address', '=', Input::get('ipaddress')));
            $secondarydata = $secondarycheck->first();
            if($ovzdata->user_id == $user->data()->id || $kvmdata->user_id == $user->data()->id || $secondarydata->user_id == $user->data()->id) {
                $ipaddress_octets = explode('.', Input::get('ipaddress'));
                $zone = $ipaddress_octets[2] . '.' . $ipaddress_octets[1] . '.' . $ipaddress_octets[0] . '.in-addr.arpa';
                $ptrdname = Input::get('hostname');
                if(preg_match('/[^a-z\.\-0-9]/i', $ptrdname)) {
                    $errors = 'Invalid hostname / target.';
                }else{
                    $add = $cpanel->addzonerecord($zone, $ipaddress_octets[3], 'PTR', $ptrdname);
                    if($add) {
                      $insert = $db->insert('vncp_reverse_dns', array(
                          'client_id' => $user->data()->id,
                          'type' => 'PTR',
                          'ipaddress' => Input::get('ipaddress'),
                          'hostname' => $ptrdname
                      ));
                      $log->log('Added PTR ' . Input::get('ipaddress') . ' -> ' . $ptrdname . ' record', 'general', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                      $success = 'Your reverse DNS record has been created!';
                    }else{
                      $errors = 'Could not edit DNS zone.';
                      $log->log('Could not edit DNS zone ' . $zone, 'error', 1, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                    }
                }
            }else{
                $errors = 'Invalid IP address.';
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }else if(strpos(Input::get('formid'), 'v6radd') !== false) {
        $validate = new Validate();
        $validation = $validate->check($_POST, array(
            'ipaddress' => array(
                'required' => true,
                'ip6' => true
            ),
            'hostname' => array(
                'required' => true,
                'max' => 250,
                'min' => 6
            )
        ));
        if($validation->passed()) {
            $v6check = $db->get('vncp_ipv6_assignment', array('address', '=', Input::get('ipaddress')));
            $v6data = $v6check->first();
            if($v6data->user_id == $user->data()->id) {
                $ipaddress_octets = explode(':', Input::get('ipaddress'));
                for($i = 0; $i < count($ipaddress_octets); $i++) {
                    if(strlen($ipaddress_octets[$i]) < 4) {
                        switch(strlen($ipaddress_octets[$i])) {
                            case 1:
                                $ipaddress_octets[$i] = '000' . $ipaddress_octets[$i];
                            break;
                            case 2:
                                $ipaddress_octets[$i] = '00' . $ipaddress_octets[$i];
                            break;
                            case 3:
                                $ipaddress_octets[$i] = '0' . $ipaddress_octets[$i];
                            break;
                        }
                    }
                }
                for($i = 0; $i < count($ipaddress_octets); $i++) {
                    $ipaddress_octets[$i] = str_split($ipaddress_octets[$i]);
                }
                $zoneprefix = '';
                for($i = 3; $i >= 0; $i--) {
                    for($k = count($ipaddress_octets[$i]) - 1; $k >= 0; $k--) {
                        $zoneprefix = $zoneprefix . $ipaddress_octets[$i][$k] . '.';
                    }
                }
                $zone = $zoneprefix . 'ip6.arpa';
                $ptrdname = Input::get('hostname');
                if(preg_match('/[^a-z\.\-0-9]/i', $ptrdname)) {
                    $errors = 'Invalid hostname / target.';
                }else{
                    $dnsname = '';
                    for($i = 7; $i >= 4; $i--) {
                        for($k = count($ipaddress_octets[$i]) - 1; $k >= 0; $k--) {
                            $dnsname = $dnsname . $ipaddress_octets[$i][$k] . '.';
                        }
                    }
                    $dnsname = rtrim($dnsname, '.');
                    $add = $cpanel->addzonerecord($zone, $dnsname, 'PTR', $ptrdname);
                    if($add) {
                      $insert = $db->insert('vncp_reverse_dns', array(
                          'client_id' => $user->data()->id,
                          'type' => 'PTR',
                          'ipaddress' => Input::get('ipaddress'),
                          'hostname' => $ptrdname
                      ));
                      $log->log('Added PTR ' . Input::get('ipaddress') . ' -> ' . $ptrdname . ' record', 'general', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                      $success = 'Your reverse DNS record has been created!';
                    }else{
                      $errors = 'Could not edit DNS zone (IPv6).';
                      $log->log('Could not edit DNS zone (IPv6) ' . $zone, 'error', 1, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                    }
                }
            }else{
                $errors = 'Invalid IPv6 address.';
            }
        }else{
            $errors = '';
            foreach($validation->errors() as $error) {
                $errors .= $error . '<br />';
            }
        }
    }else if(strpos(Input::get('formid'), 'del_rdns') !== false) {
        if(strpos(Input::get('ipaddress'), ':') !== false) {
            $v6check = $db->get('vncp_ipv6_assignment', array('address', '=', Input::get('ipaddress')));
            $v6data = $v6check->first();
            if($v6data->user_id == $user->data()->id) {
                $ipaddress_octets = explode(':', Input::get('ipaddress'));
                for($i = 0; $i < count($ipaddress_octets); $i++) {
                    if(strlen($ipaddress_octets[$i]) < 4) {
                        switch(strlen($ipaddress_octets[$i])) {
                            case 1:
                                $ipaddress_octets[$i] = '000' . $ipaddress_octets[$i];
                            break;
                            case 2:
                                $ipaddress_octets[$i] = '00' . $ipaddress_octets[$i];
                            break;
                            case 3:
                                $ipaddress_octets[$i] = '0' . $ipaddress_octets[$i];
                            break;
                        }
                    }
                }
                for($i = 0; $i < count($ipaddress_octets); $i++) {
                    $ipaddress_octets[$i] = str_split($ipaddress_octets[$i]);
                }
                $zoneprefix = '';
                for($i = 3; $i >= 0; $i--) {
                    for($k = count($ipaddress_octets[$i]) - 1; $k >= 0; $k--) {
                        $zoneprefix = $zoneprefix . $ipaddress_octets[$i][$k] . '.';
                    }
                }
                $zone = $zoneprefix . 'ip6.arpa';
                $dump = $cpanel->dumpzone($zone);
                $dnsname = '';
                for($i = 7; $i >= 4; $i--) {
                    for($k = count($ipaddress_octets[$i]) - 1; $k >= 0; $k--) {
                        $dnsname = $dnsname . $ipaddress_octets[$i][$k] . '.';
                    }
                }
                $dnsname = rtrim($dnsname, '.');
                for($i = 0; $i < count($dump->data->zone[0]->record); $i++) {
                    if($dump->data->zone[0]->record[$i]->name == $dnsname . '.' . $zone . '.') {
                        $line = $dump->data->zone[0]->record[$i]->Line;
                        $remove = $cpanel->removezonerecord($zone, (int)$line);
                        $dbremove = $db->delete('vncp_reverse_dns', array('ipaddress', '=', Input::get('ipaddress')));
                        $log->log('Removed PTR ' . Input::get('ipaddress') . ' record', 'general', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                    }
                }
            }else{
                $errors = 'Invalid IPv6 address.';
            }
        }else{
            $ovzcheck = $db->get('vncp_lxc_ct', array('ip', '=', Input::get('ipaddress')));
            $ovzdata = $ovzcheck->first();
            $kvmcheck = $db->get('vncp_kvm_ct', array('ip', '=', Input::get('ipaddress')));
            $kvmdata = $kvmcheck->first();
            if($ovzdata->user_id == $user->data()->id || $kvmdata->user_id == $user->data()->id) {
                $ipaddress_octets = explode('.', Input::get('ipaddress'));
                $zone = $ipaddress_octets[2] . '.' . $ipaddress_octets[1] . '.' . $ipaddress_octets[0] . '.in-addr.arpa';
                $dump = $cpanel->dumpzone($zone);
                for($i = 0; $i < count($dump->data->zone[0]->record); $i++) {
                    if($dump->data->zone[0]->record[$i]->name == $ipaddress_octets[3].'.'.$zone.'.') {
                        $line = $dump->data->zone[0]->record[$i]->Line;
                        $remove = $cpanel->removezonerecord($zone, (int)$line);
                        $dbremove = $db->delete('vncp_reverse_dns', array('ipaddress', '=', Input::get('ipaddress')));
                        $log->log('Removed PTR ' . Input::get('ipaddress') . ' record', 'general', 0, $user->data()->username, $_SERVER['REMOTE_ADDR']);
                    }
                }
                $success = 'Your reverse DNS record has been removed!';
            }else{
                $errors = 'Invalid IP address.';
            }
        }
    }
}

$appname = $db->get('vncp_settings', array('item', '=', 'app_name'))->first()->value;
$rdnssetting = $db->get('vncp_settings', array('item', '=', 'enable_reverse_dns'))->first()->value;
$result = $db->get('vncp_reverse_dns', array('client_id', '=', $user->data()->id));
$existingdata = $result->all();
$result = $db->get('vncp_kvm_ct', array('user_id', '=', $user->data()->id));
$data = $result->all();
$kvmips = array();
for($i = 0; $i < count($data); $i++) {
    $rcheck = $db->get('vncp_reverse_dns', array('client_id', '=', $user->data()->id));
    $rdata = $rcheck->all();
    $matches = 0;
    for($j = 0; $j < count($rdata); $j++) {
        if($rdata[$j]->ipaddress == $data[$i]->ip) {
            $matches++;
        }
    }
    $natcheck = $db->get('vncp_nat', array('node', '=', $data[$i]->node))->all();
    $isNAT = false;
    for($j = 0; $j < count($natcheck); $j++) {
      if(IPInRange($data[$i]->ip, $natcheck[$j]->natcidr)) {
        $isNAT = true;
        break;
      }
    }
    if($matches == 0 && $isNAT == false) {
        $kvmips[] = $data[$i]->ip;
    }
}
$result = $db->get('vncp_lxc_ct', array('user_id', '=', $user->data()->id));
$data = $result->all();
$lxcips = array();
for($i = 0; $i < count($data); $i++) {
    $rcheck = $db->get('vncp_reverse_dns', array('client_id', '=', $user->data()->id));
    $rdata = $rcheck->all();
    $matches = 0;
    for($j = 0; $j < count($rdata); $j++) {
        if($rdata[$j]->ipaddress == $data[$i]->ip) {
            $matches++;
        }
    }
    $natcheck = $db->get('vncp_nat', array('node', '=', $data[$i]->node))->all();
    $isNAT = false;
    for($j = 0; $j < count($natcheck); $j++) {
      if(IPInRange($data[$i]->ip, $natcheck[$j]->natcidr)) {
        $isNAT = true;
        break;
      }
    }
    if($matches == 0 && $isNAT == false) {
        $lxcips[] = $data[$i]->ip;
    }
}
$result = $db->get('vncp_secondary_ips', array('user_id', '=', $user->data()->id));
$data = $result->all();
$secondaryips = array();
for($i = 0; $i < count($data); $i++) {
    $rcheck = $db->get('vncp_reverse_dns', array('client_id', '=', $user->data()->id));
    $rdata = $rcheck->all();
    $matches = 0;
    for($j = 0; $j < count($rdata); $j++) {
        if($rdata[$j]->ipaddress == $data[$i]->address) {
            $matches++;
        }
    }
    if($matches == 0) {
        $secondaryips[] = $data[$i]->address;
    }
}
$result = $db->get('vncp_ipv6_assignment', array('user_id', '=', $user->data()->id));
$data = $result->all();
$v6ips = array();
for($i = 0; $i < count($data); $i++) {
    $rcheck = $db->get('vncp_reverse_dns', array('client_id', '=', $user->data()->id));
    $rdata = $rcheck->all();
    $matches = 0;
    for($j = 0; $j < count($rdata); $j++) {
        if($rdata[$j]->ipaddress == $data[$i]->address) {
            $matches++;
        }
    }
    if($matches == 0) {
        $v6ips[] = $data[$i]->address;
    }
}

$enable_firewall = escape($db->get('vncp_settings', array('item', '=', 'enable_firewall'))->first()->value);
$enable_forward_dns = escape($db->get('vncp_settings', array('item', '=', 'enable_forward_dns'))->first()->value);
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

echo $twig->render('reverse_dns.tpl', [
  'appname' => $appname,
  'rdnssetting' => $rdnssetting,
  'errors' => $errors,
  'success' => $success,
  'existingdata' => $existingdata,
  'formID' => 'del_rdns_'.getRandomString(10),
  'kvmips' => $kvmips,
  'lxcips' => $lxcips,
  'secondaryips' => $secondaryips,
  'formID2' => 'v4radd_'.getRandomString(10),
  'formID3' => 'v6radd_'.getRandomString(10),
  'v6ips' => $v6ips,
  'adminBase' => Config::get('admin/base'),
  'enable_firewall' => $enable_firewall,
  'enable_forward_dns' => $enable_forward_dns,
  'enable_reverse_dns' => $rdnssetting,
  'enable_notepad' => $enable_notepad,
  'enable_status' => $enable_status,
  'isAdmin' => $isAdmin,
  'constants' => $constants,
  'username' => $user->data()->username,
  'aclsetting' => $aclsetting,
  'pagename' => 'Manage Reverse DNS',
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
