<?php
require_once('vendor/autoload.php');
use phpseclib\Net\SSH2;
require_once('core/autoload.php');
require_once('core/init.php');

$ua = $_SERVER['HTTP_USER_AGENT'];
$from = $_SERVER['REMOTE_ADDR'];
$response = array(
  'success' => 0,
  'message' => 'Invalid pre-check data',
  'data' => []
);

function private_getIPv4FromPool($db, $node, $userid, $hbid, $nat) {
  $freeips = $db->get('vncp_ipv4_pool', array('available', '=', 1))->all();
  if($nat != 'on') {
    foreach($freeips as $freeip) {
      if($freeip->user_id == 0 && $freeip->hb_account_id == 0 && strpos($freeip->nodes, $node) !== false) {
        $db->update('vncp_ipv4_pool', $freeip->id, array(
          'user_id' => $userid,
          'hb_account_id' => $hbid,
          'available' => 0
        ));
        return array($freeip->address, $freeip->netmask, $freeip->gateway);
      }
    }
  }else{
    $natCIDR = $db->get('vncp_nat', array('node', '=', $node))->all();
    foreach($freeips as $freeip) {
      if(IPInRange($freeip->address, $natCIDR[0]->natcidr) && $freeip->user_id == 0 && $freeip->hb_account_id == 0 && strpos($freeip->nodes, $node) !== false) {
        $db->update('vncp_ipv4_pool', $freeip->id, array(
          'user_id' => $userid,
          'hb_account_id' => $hbid,
          'available' => 0
        ));
        return array($freeip->address, $freeip->netmask, $freeip->gateway);
      }
    }
  }
  return array('127.0.0.2', '255.0.0.0', '127.0.0.1');
}

function private_getISO($db, $osfriendly) {
  $isos = $db->get('vncp_kvm_isos', array('id', '!=', 0))->all();
  foreach($isos as $iso) {
    if($iso->friendly_name == $osfriendly) {
      return $iso->volid;
    }
  }
  return false;
}

function private_getStorageLocation($node, $pxAPI) {
  $storages = $pxAPI->get('/nodes/'.$node.'/storage');
  foreach($storages as $storage) {
    if((int)$storage['active'] == 1 && (float)$storage['used_fraction'] < 0.80 && strpos($storage['content'], "images") !== false) {
      return $storage['storage'];
    }
  }
  return 'local';
}

function api_startserver($db, $type, $hbid, $userid) {
  $retval = array(
    'success' => 0,
    'message' => 'startserver failed',
    'data' => []
  );
  if($type == 'kvm') {
    $kvms = $db->get('vncp_kvm_ct', array('hb_account_id', '=', $hbid))->first();
    if($kvms->user_id == $userid) {
      $node_results = $db->get('vncp_nodes', array('name', '=', $kvms->node))->first();
      $pxAPI = new PVE2_API($node_results->hostname, $node_results->username, $node_results->realm, decryptValue($node_results->password));
      $noLogin = false;
      if(!$pxAPI->login()) $noLogin = true;
      if($noLogin == false) {
        $vminfo = $pxAPI->get('/pools/'.$kvms->pool_id);
        $clvmid = $vminfo['members'][0]['vmid'];
        $pxstart = $pxAPI->post('/nodes/'.$kvms->node.'/qemu/'.$clvmid.'/status/start', array());
        sleep(5);
        $retval['success'] = 1;
        $retval['message'] = 'success';
      }
    }else{
      $retval['message'] = 'Invalid user ID';
    }
  }else if($type == 'lxc') {
    $lxcs = $db->get('vncp_lxc_ct', array('hb_account_id', '=', $hbid))->first();
    if($lxcs->user_id == $userid) {
      $node_results = $db->get('vncp_nodes', array('name', '=', $lxcs->node))->first();
      $pxAPI = new PVE2_API($node_results->hostname, $node_results->username, $node_results->realm, decryptValue($node_results->password));
      $noLogin = false;
      if(!$pxAPI->login()) $noLogin = true;
      if($noLogin == false) {
        $vminfo = $pxAPI->get('/pools/'.$lxcs->pool_id);
        $clvmid = $vminfo['members'][0]['vmid'];
        $pxstart = $pxAPI->post('/nodes/'.$lxcs->node.'/lxc/'.$clvmid.'/status/start', array());
        sleep(5);
        $retval['success'] = 1;
        $retval['message'] = 'success';
      }
    }else{
      $retval['message'] = 'Invalid user ID';
    }
  }else{
    $retval['message'] = 'Invalid type';
  }
  return $retval;
}

function api_stopserver($db, $type, $hbid, $userid) {
  $retval = array(
    'success' => 0,
    'message' => 'stopserver failed',
    'data' => []
  );
  if($type == 'kvm') {
    $kvms = $db->get('vncp_kvm_ct', array('hb_account_id', '=', $hbid))->first();
    if($kvms->user_id == $userid) {
      $node_results = $db->get('vncp_nodes', array('name', '=', $kvms->node))->first();
      $pxAPI = new PVE2_API($node_results->hostname, $node_results->username, $node_results->realm, decryptValue($node_results->password));
      $noLogin = false;
      if(!$pxAPI->login()) $noLogin = true;
      if($noLogin == false) {
        $vminfo = $pxAPI->get('/pools/'.$kvms->pool_id);
        $clvmid = $vminfo['members'][0]['vmid'];
        $pxstart = $pxAPI->post('/nodes/'.$kvms->node.'/qemu/'.$clvmid.'/status/stop', array());
        sleep(5);
        $retval['success'] = 1;
        $retval['message'] = 'success';
      }
    }else{
      $retval['message'] = 'Invalid user ID';
    }
  }else if($type == 'lxc') {
    $lxcs = $db->get('vncp_lxc_ct', array('hb_account_id', '=', $hbid))->first();
    if($lxcs->user_id == $userid) {
      $node_results = $db->get('vncp_nodes', array('name', '=', $lxcs->node))->first();
      $pxAPI = new PVE2_API($node_results->hostname, $node_results->username, $node_results->realm, decryptValue($node_results->password));
      $noLogin = false;
      if(!$pxAPI->login()) $noLogin = true;
      if($noLogin == false) {
        $vminfo = $pxAPI->get('/pools/'.$lxcs->pool_id);
        $clvmid = $vminfo['members'][0]['vmid'];
        $pxstart = $pxAPI->post('/nodes/'.$lxcs->node.'/lxc/'.$clvmid.'/status/stop', array());
        sleep(5);
        $retval['success'] = 1;
        $retval['message'] = 'success';
      }
    }else{
      $retval['message'] = 'Invalid user ID';
    }
  }else{
    $retval['message'] = 'Invalid type';
  }
  return $retval;
}

function api_getDetails($db, $type, $hbid) {
  $retval = array(
    'success' => 0,
    'message' => 'getDetails failed',
    'data' => []
  );
  $vmtype = '';
  $poolid = '';
  if($type == 'kvm') {
    $kvms = $db->get('vncp_kvm_ct', array('hb_account_id', '=', $hbid))->first();
    $retval['success'] = 1;
    $retval['message'] = 'success';
    $retval['data'][] = $kvms->node;
    $retval['data'][] = $kvms->ip;
    $retval['data'][] = $kvms->os;
    $vmtype = 'qemu';
    $poolid = $kvms->pool_id;
  }else if($type == 'pc') {
    $cls = $db->get('vncp_kvm_cloud', array('hb_account_id', '=', $hbid))->first();
    $retval['success'] = 1;
    $retval['message'] = 'success';
    $retval['data'][] = $cls->nodes;
    $retval['data'][] = $cls->ipv4;
    $retval['data'][] = 'N/A';
  }else if($type == 'lxc') {
    $lxcs = $db->get('vncp_lxc_ct', array('hb_account_id', '=', $hbid))->first();
    $retval['success'] = 1;
    $retval['message'] = 'success';
    $retval['data'][] = $lxcs->node;
    $retval['data'][] = $lxcs->ip;
    $retval['data'][] = $lxcs->os;
    $vmtype = 'lxc';
    $poolid = $lxcs->pool_id;
  }else{
    $retval['message'] = 'Invalid type';
  }
  if($retval['success'] == 1 && $retval['message'] == 'success') {
    $retval['data'][] = 'unknown';
    if($vmtype != '' && $type != 'pc' && $poolid != '') {
      $node_results = $db->get('vncp_nodes', array('name', '=', $retval['data'][0]))->first();
      $pxAPI = new PVE2_API($node_results->hostname, $node_results->username, $node_results->realm, decryptValue($node_results->password));
      $noLogin = false;
      if(!$pxAPI->login()) $noLogin = true;
      if($noLogin == false) {
        $vminfo = $pxAPI->get('/pools/'.$poolid);
        $clvmid = $vminfo['members'][0]['vmid'];
        $pxstatus = $pxAPI->get('/nodes/'.$retval['data'][0].'/'.$vmtype.'/'.$clvmid.'/status/current');
        $retval['data'][3] = $pxstatus['status'];
      }
    }
  }
  return $retval;
}

function api_getNodes($db) {
  $retval = array(
    'success' => 0,
    'message' => 'getNodes failed',
    'data' => []
  );
  $nodes = $db->get('vncp_nodes', array('id', '!=', 0))->all();
  if(count($nodes) > 0) {
    foreach($nodes as $node) {
      $retval['data'][] = $node->name;
    }
    $retval['success'] = 1;
    $retval['message'] = 'success';
  }
  return $retval;
}

function api_terminate($db, $type, $hbid, $node, $poolid, $userid) {
  $retval = array(
    'success' => 0,
    'message' => 'terminate failed',
    'data' => []
  );
  $node_results = $db->get('vncp_nodes', array('name', '=', $node))->first();
  $pxAPI = new PVE2_API($node_results->hostname, $node_results->username, $node_results->realm, decryptValue($node_results->password));
  $noLogin = false;
  if(!$pxAPI->login()) $noLogin = true;
  if($noLogin == false) {
    if($type == 'kvm') {
      $kvmdetails = $db->get('vncp_kvm_ct', array('hb_account_id', '=', $hbid))->first();
      $db->delete('vncp_dhcp', array('ip', '=', $kvmdetails->ip));
      $db->delete('vncp_kvm_ct', array('hb_account_id', '=', $hbid));
      $db->delete('vncp_ct_backups', array('hb_account_id', '=', $hbid));
      $db->delete('vncp_bandwidth_monitor', array('hb_account_id', '=', $hbid));
      $db->delete('vncp_reverse_dns', array('ipaddress', '=', $kvmdetails->ip));
      $db->delete('vncp_ipv6_assignment', array('hb_account_id', '=', $hbid));
      $db->updatevm_aid('vncp_private_pool', $hbid, array(
        'user_id' => 0,
        'hb_account_id' => 0,
        'available' => 1
      ));
      $db->updatevm_aid('vncp_ipv4_pool', $hbid, array(
        'user_id' => 0,
        'hb_account_id' => 0,
        'available' => 1
      ));
      $db->delete('vncp_secondary_ips', array('hb_account_id', '=', $hbid));
      $db->delete('vncp_pending_clone', array('hb_account_id', '=', $hbid));

      $natentries = $db->get('vncp_natforwarding', array('hb_account_id', '=', $hbid))->all();
      if(count($natentries) == 1) {
        $natnode = $db->get('vncp_nat', array('node', '=', $natentries[0]->node))->first();
        $domain_array = explode(";", $natentries[0]->domains);
        $domain_str = '';
        for($i = 0; $i < count($domain_array) - 1; $i++) {
          $domain_str .= 'rm /etc/nginx/conf.d/'.$hbid.'-'.$domain_array[$i].'-*.conf && rm /etc/nginx/proxcp-nat-ssl/cert-'.$hbid.'-'.$domain_array[$i].'-*.pem && rm /etc/nginx/proxcp-nat-ssl/key-'.$hbid.'-'.$domain_array[$i].'-*.pem && ';
        }
        $domain_str .= 'service nginx restart';
        $port_array = explode(";", $natentries[0]->ports);
        $port_str = '';
        for($i = 0; $i < count($port_array); $i++) {
          $tport = explode(":", $port_array[$i]);
          $port_str .= 'iptables -t nat -D PREROUTING -p tcp -d '.$natnode->publicip.' --dport '.$tport[1].' -i vmbr0 -j DNAT --to-destination '.$kvmdetails->ip.':'.$tport[2].' && ';
        }
        $port_str .= 'iptables-save > /root/proxcp-iptables.rules';
        $nodehn = $db->get('vncp_nodes', array('name', '=', $natentries[0]->node))->all();
        $rcreds = $db->get('vncp_tuntap', array('node', '=', $natentries[0]->node))->all();
        if(count($nodehn) && count($rcreds)) {
          $ssh = new SSH2($nodehn[0]->hostname, (int)$rcreds[0]->port);
          if($ssh->login('root', decryptValue($rcreds[0]->password))) {
            $ssh->exec($domain_str);
            $ssh->exec($port_str);
            $ssh->disconnect();
          }
        }
        $db->delete('vncp_natforwarding', array('hb_account_id', '=', $hbid));
      }

      $kvmcheck = $db->get('vncp_kvm_ct', array('user_id', '=', $userid))->all();
      $lxccheck = $db->get('vncp_lxc_ct', array('user_id', '=', $userid))->all();
      $clcheck = $db->get('vncp_kvm_cloud', array('user_id', '=', $userid))->all();
      $admincheck = $db->get('vncp_users', array('id', '=', $userid))->first();
      if(count($kvmcheck) == 0 && count($lxccheck) == 0 && count($clcheck) == 0 && $admincheck->group != 2) {
        $db->delete('vncp_forward_dns_domain', array('client_id', '=', $userid));
        $db->delete('vncp_forward_dns_record', array('client_id', '=', $userid));
        $db->delete('vncp_acl', array('user_id', '=', $userid));
        $db->delete('vncp_notes', array('id', '=', $userid));
        $db->delete('vncp_users', array('id', '=', $userid));
      }
      $vminfo = $pxAPI->get('/pools/'.$poolid);
      $clvmid = $vminfo['members'][0]['vmid'];
      $stop = $pxAPI->post('/nodes/'.$node.'/qemu/'.$clvmid.'/status/stop', array());
      sleep(5);
      $delete = $pxAPI->delete('/nodes/'.$node.'/qemu/'.$clvmid);
      sleep(2);
      $delete_pool = $pxAPI->delete('/pools/' . $poolid);
      sleep(1);
      $delete_user = $pxAPI->delete('/access/users/' . $poolid);
      $retval['success'] = 1;
      $retval['message'] = 'Terminated account successfully';
      $retval['data'] = ['terminated account successfully'];
    }else if($type == 'pc') {
      $clouddetails = $db->get('vncp_kvm_ct', array('cloud_account_id', '=', $hbid))->all();
      for($i = 0; $i < count($clouddetails); $i++) {
        $db->delete('vncp_dhcp', array('ip', '=', $clouddetails[$i]->ip));
        $db->delete('vncp_kvm_ct', array('hb_account_id', '=', $clouddetails[$i]->hb_account_id));
        $db->delete('vncp_ct_backups', array('hb_account_id', '=', $clouddetails[$i]->hb_account_id));
        $db->delete('vncp_bandwidth_monitor', array('hb_account_id', '=', $clouddetails[$i]->hb_account_id));
        $db->delete('vncp_reverse_dns', array('ipaddress', '=', $clouddetails[$i]->ip));
        $db->delete('vncp_ipv6_assignment', array('hb_account_id', '=', $clouddetails[$i]->hb_account_id));
        $db->updatevm_aid('vncp_private_pool', $clouddetails[$i]->hb_account_id, array(
          'user_id' => 0,
          'hb_account_id' => 0,
          'available' => 1
        ));
        $db->updatevm_aid('vncp_ipv4_pool', $clouddetails[$i]->hb_account_id, array(
          'user_id' => 0,
          'hb_account_id' => 0,
          'available' => 1
        ));
        $db->delete('vncp_secondary_ips', array('hb_account_id', '=', $clouddetails[$i]->hb_account_id));
        $db->delete('vncp_pending_clone', array('hb_account_id', '=', $clouddetails[$i]->hb_account_id));
        $db->delete('vncp_pending_deletion', array('hb_account_id', '=', $clouddetails[$i]->hb_account_id));
      }
      $db->delete('vncp_kvm_cloud', array('hb_account_id', '=', $hbid));
      $kvmcheck = $db->get('vncp_kvm_ct', array('user_id', '=', $userid))->all();
      $lxccheck = $db->get('vncp_lxc_ct', array('user_id', '=', $userid))->all();
      $clcheck = $db->get('vncp_kvm_cloud', array('user_id', '=', $userid))->all();
      $admincheck = $db->get('vncp_users', array('id', '=', $userid))->first();
      if(count($kvmcheck) == 0 && count($lxccheck) == 0 && count($clcheck) == 0 && $admincheck->group != 2) {
        $db->delete('vncp_forward_dns_domain', array('client_id', '=', $userid));
        $db->delete('vncp_forward_dns_record', array('client_id', '=', $userid));
        $db->delete('vncp_acl', array('user_id', '=', $userid));
        $db->delete('vncp_notes', array('id', '=', $userid));
        $db->delete('vncp_users', array('id', '=', $userid));
      }
      $vminfo = $pxAPI->get('/pools/'.$poolid);
      for($i = 0; $i < count($vminfo['members']); $i++) {
        $clvmid = $vminfo['members'][$i]['vmid'];
        $stop = $pxAPI->post('/nodes/'.$node.'/qemu/'.$clvmid.'/status/stop', array());
        sleep(5);
        $delete = $pxAPI->delete('/nodes/'.$node.'/qemu/'.$clvmid);
      }
      sleep(2);
      $delete_pool = $pxAPI->delete('/pools/' . $poolid);
      $delete_user = $pxAPI->delete('/access/users/' . $poolid);
      $retval['success'] = 1;
      $retval['message'] = 'Terminated account successfully';
      $retval['data'] = ['terminated account successfully'];
    }else if($type == 'lxc') {
      $lxcdetails = $db->get('vncp_lxc_ct', array('hb_account_id', '=', $hbid))->first();
      $db->delete('vncp_dhcp', array('ip', '=', $lxcdetails->ip));
      $db->delete('vncp_lxc_ct', array('hb_account_id', '=', $hbid));
      $db->delete('vncp_ct_backups', array('hb_account_id', '=', $hbid));
      $db->delete('vncp_bandwidth_monitor', array('hb_account_id', '=', $hbid));
      $db->delete('vncp_reverse_dns', array('ipaddress', '=', $kvmdetails->ip));
      $db->delete('vncp_ipv6_assignment', array('hb_account_id', '=', $hbid));
      $db->updatevm_aid('vncp_private_pool', $hbid, array(
        'user_id' => 0,
        'hb_account_id' => 0,
        'available' => 1
      ));
      $db->updatevm_aid('vncp_ipv4_pool', $hbid, array(
        'user_id' => 0,
        'hb_account_id' => 0,
        'available' => 1
      ));
      $db->delete('vncp_secondary_ips', array('hb_account_id', '=', $hbid));

      $natentries = $db->get('vncp_natforwarding', array('hb_account_id', '=', $hbid))->all();
      if(count($natentries) == 1) {
        $natnode = $db->get('vncp_nat', array('node', '=', $natentries[0]->node))->first();
        $domain_array = explode(";", $natentries[0]->domains);
        $domain_str = '';
        for($i = 0; $i < count($domain_array) - 1; $i++) {
          $domain_str .= 'rm /etc/nginx/conf.d/'.$hbid.'-'.$domain_array[$i].'-*.conf && rm /etc/nginx/proxcp-nat-ssl/cert-'.$hbid.'-'.$domain_array[$i].'-*.pem && rm /etc/nginx/proxcp-nat-ssl/key-'.$hbid.'-'.$domain_array[$i].'-*.pem && ';
        }
        $domain_str .= 'service nginx restart';
        $port_array = explode(";", $natentries[0]->ports);
        $port_str = '';
        for($i = 0; $i < count($port_array); $i++) {
          $tport = explode(":", $port_array[$i]);
          $port_str .= 'iptables -t nat -D PREROUTING -p tcp -d '.$natnode->publicip.' --dport '.$tport[1].' -i vmbr0 -j DNAT --to-destination '.$kvmdetails->ip.':'.$tport[2].' && ';
        }
        $port_str .= 'iptables-save > /root/proxcp-iptables.rules';
        $nodehn = $db->get('vncp_nodes', array('name', '=', $natentries[0]->node))->all();
        $rcreds = $db->get('vncp_tuntap', array('node', '=', $natentries[0]->node))->all();
        if(count($nodehn) && count($rcreds)) {
          $ssh = new SSH2($nodehn[0]->hostname, (int)$rcreds[0]->port);
          if($ssh->login('root', decryptValue($rcreds[0]->password))) {
            $ssh->exec($domain_str);
            $ssh->exec($port_str);
            $ssh->disconnect();
          }
        }
        $db->delete('vncp_natforwarding', array('hb_account_id', '=', $hbid));
      }

      $kvmcheck = $db->get('vncp_kvm_ct', array('user_id', '=', $userid))->all();
      $lxccheck = $db->get('vncp_lxc_ct', array('user_id', '=', $userid))->all();
      $clcheck = $db->get('vncp_kvm_cloud', array('user_id', '=', $userid))->all();
      $admincheck = $db->get('vncp_users', array('id', '=', $userid))->first();
      if(count($kvmcheck) == 0 && count($lxccheck) == 0 && count($clcheck) == 0 && $admincheck->group != 2) {
        $db->delete('vncp_forward_dns_domain', array('client_id', '=', $userid));
        $db->delete('vncp_forward_dns_record', array('client_id', '=', $userid));
        $db->delete('vncp_acl', array('user_id', '=', $userid));
        $db->delete('vncp_notes', array('id', '=', $userid));
        $db->delete('vncp_users', array('id', '=', $userid));
      }
      $vminfo = $pxAPI->get('/pools/'.$poolid);
      $clvmid = $vminfo['members'][0]['vmid'];
      $stop = $pxAPI->post('/nodes/'.$node.'/lxc/'.$clvmid.'/status/stop', array());
      sleep(5);
      $delete = $pxAPI->delete('/nodes/'.$node.'/lxc/'.$clvmid);
      sleep(2);
      $delete_pool = $pxAPI->delete('/pools/' . $poolid);
      $delete_user = $pxAPI->delete('/access/users/' . $poolid);
      $retval['success'] = 1;
      $retval['message'] = 'Terminated account successfully';
      $retval['data'] = ['terminated account successfully'];
    }else{
      $retval['message'] = 'Invalid type';
    }
  }else{
    $retval['message'] = 'Could not connect to Proxmox node';
  }
  return $retval;
}

function api_suspend($db, $type, $hbid, $node, $poolid) {
  $retval = array(
    'success' => 0,
    'message' => 'suspend failed',
    'data' => []
  );
  $node_results = $db->get('vncp_nodes', array('name', '=', $node))->first();
  $pxAPI = new PVE2_API($node_results->hostname, $node_results->username, $node_results->realm, decryptValue($node_results->password));
  $noLogin = false;
  if(!$pxAPI->login()) $noLogin = true;
  if($noLogin == false) {
    if($type == 'kvm') {
      $vminfo = $pxAPI->get('/pools/'.$poolid);
      $clvmid = $vminfo['members'][0]['vmid'];
      $stop = $pxAPI->post('/nodes/'.$node.'/qemu/'.$clvmid.'/status/stop', array());
      sleep(1);
      $db->updatevm_aid('vncp_kvm_ct', $hbid, array(
        'suspended' => 1
      ));
      $retval['success'] = 1;
      $retval['message'] = 'success';
      $retval['data'] = ['success'];
    }else if($type == 'pc') {
      $vminfo = $pxAPI->get('/pools/'.$poolid);
      for($j = 0; $j < count($vminfo['members']); $j++) {
        $clvmid = $vminfo['members'][$j]['vmid'];
        $stop = $pxAPI->post('/nodes/'.$node.'/qemu/'.$clvmid.'/status/stop', array());
        sleep(1);
      }
      $db->updatevm_aid('vncp_kvm_cloud', $hbid, array(
        'suspended' => 1
      ));
      $db->updatevm_clid('vncp_kvm_ct', $hbid, array(
        'suspended' => 1
      ));
      $retval['success'] = 1;
      $retval['message'] = 'success';
      $retval['data'] = ['success'];
    }else if($type == 'lxc') {
      $vminfo = $pxAPI->get('/pools/'.$poolid);
      $clvmid = $vminfo['members'][0]['vmid'];
      $stop = $pxAPI->post('/nodes/'.$node.'/lxc/'.$clvmid.'/status/stop', array());
      sleep(1);
      $db->updatevm_aid('vncp_lxc_ct', $hbid, array(
        'suspended' => 1
      ));
      $retval['success'] = 1;
      $retval['message'] = 'success';
      $retval['data'] = ['success'];
    }else{
      $retval['message'] = 'Invalid type';
    }
  }else{
    $retval['message'] = 'Could not connect to Proxmox node';
  }
  return $retval;
}

function api_unsuspend($db, $type, $hbid, $node, $poolid) {
  $retval = array(
    'success' => 0,
    'message' => 'suspend failed',
    'data' => []
  );
  $node_results = $db->get('vncp_nodes', array('name', '=', $node))->first();
  $pxAPI = new PVE2_API($node_results->hostname, $node_results->username, $node_results->realm, decryptValue($node_results->password));
  $noLogin = false;
  if(!$pxAPI->login()) $noLogin = true;
  if($noLogin == false) {
    if($type == 'kvm') {
      $vminfo = $pxAPI->get('/pools/'.$poolid);
      $clvmid = $vminfo['members'][0]['vmid'];
      $start = $pxAPI->post('/nodes/'.$node.'/qemu/'.$clvmid.'/status/start', array());
      sleep(1);
      $db->updatevm_aid('vncp_kvm_ct', $hbid, array(
        'suspended' => 0
      ));
      $retval['success'] = 1;
      $retval['message'] = 'success';
      $retval['data'] = ['success'];
    }else if($type == 'pc') {
      $vminfo = $pxAPI->get('/pools/'.$poolid);
      for($j = 0; $j < count($vminfo['members']); $j++) {
        $clvmid = $vminfo['members'][$j]['vmid'];
        $start = $pxAPI->post('/nodes/'.$node.'/qemu/'.$clvmid.'/status/start', array());
        sleep(1);
      }
      $db->updatevm_aid('vncp_kvm_cloud', $hbid, array(
        'suspended' => 0
      ));
      $db->updatevm_clid('vncp_kvm_ct', $hbid, array(
        'suspended' => 0
      ));
      $retval['success'] = 1;
      $retval['message'] = 'success';
      $retval['data'] = ['success'];
    }else if($type == 'lxc') {
      $vminfo = $pxAPI->get('/pools/'.$poolid);
      $clvmid = $vminfo['members'][0]['vmid'];
      $start = $pxAPI->post('/nodes/'.$node.'/lxc/'.$clvmid.'/status/start', array());
      sleep(1);
      $db->updatevm_aid('vncp_lxc_ct', $hbid, array(
        'suspended' => 0
      ));
      $retval['success'] = 1;
      $retval['message'] = 'success';
      $retval['data'] = ['success'];
    }else{
      $retval['message'] = 'Invalid type';
    }
  }else{
    $retval['message'] = 'Could not connect to Proxmox node';
  }
  return $retval;
}

function api_createCloud($db, $userid, $email, $pw, $node, $hbid, $poolid, $storage, $cpu, $ram, $cputype, $howmanyips) {
  $retval = array(
    'success' => 0,
    'message' => 'createCloud failed',
    'data' => []
  );
  $users_results = $db->get('vncp_users', array('id', '=', $userid))->all();
  if(count($users_results) < 1) {
    $user_salt = Hash::salt(32);
    $default_language = escape($db->get('vncp_settings', array('item', '=', 'default_language'))->first()->value);
    $db->insert('vncp_users', array(
        'id' => (int)$userid,
        'email' => strtolower($email),
        'username' => strtolower($email),
        'password' => Hash::make($pw, $user_salt),
        'salt' => $user_salt,
        'tfa_enabled' => 0,
        'tfa_secret' => '',
        'group' => 1,
        'locked' => 0,
        'language' => $default_language
    ));
  }else{
    $pw = -1;
  }
  $node_results = $db->get('vncp_nodes', array('name', '=', $node));
  $node_data = $node_results->first();
  $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
  $noLogin = false;
  if(!$pxAPI->login()) $noLogin = true;
  if($noLogin == false) {
  	$plaintext_password = getRandomString(12);
  	$createpool = $pxAPI->post('/pools', array(
  		'poolid' => $poolid
  	));
  	sleep(1);
  	$createuser = $pxAPI->post('/access/users', array(
  		'userid' => $poolid . '@pve',
  		'password' => $plaintext_password
  	));
  	sleep(1);
  	$setpoolperms = $pxAPI->put('/access/acl', array(
  		'path' => '/pool/' . $poolid,
  		'users' => $poolid . '@pve',
  		'roles' => 'PVEVMUser'
  	));
  	sleep(1);
    $ipv4 = [];
    for($i = 0; $i < $howmanyips; $i++) {
      $tempip = private_getIPv4FromPool($db, $node, $userid, $hbid);
      $ipv4[] = $tempip[0];
    }
    $ipv4 = implode(';', $ipv4);
  	$db->insert('vncp_kvm_cloud', array(
  		'user_id' => $userid,
  		'nodes' => $node,
  		'hb_account_id' => $hbid,
  		'pool_id' => $poolid,
  		'pool_password' => encryptValue($plaintext_password),
  		'memory' => (int)$ram,
  		'cpu_cores' => (int)$cpu,
  		'cpu_type' => $cputype,
  		'disk_size' => (int)$storage,
  		'ip_limit' => $howmanyips,
  		'ipv4' => $ipv4,
  		'avail_memory' => (int)$ram,
  		'avail_cpu_cores' => (int)$cpu,
  		'avail_disk_size' => (int)$storage,
  		'avail_ip_limit' => $howmanyips,
  		'avail_ipv4' => $ipv4,
  		'suspended' => 0
  	));
    $retval['success'] = 1;
    $retval['message'] = 'success';
    $retval['data'] = ['success'];
    if($pw == -1) {
      $retval['data'][] = $pw;
    }
  }else{
    $retval['message'] = 'Could not connect to Proxmox node';
  }
  return $retval;
}

function api_createLXC($db, $userid, $email, $pw, $node, $osfriendly, $ostype, $hbid, $poolid, $hostname, $storage, $cpu, $ram, $bwlimit, $nat, $natp, $natd, $vlantag, $portspeed, $backuplimit) {
  $retval = array(
    'success' => 0,
    'message' => 'createLXC failed',
    'data' => []
  );
  $users_results = $db->get('vncp_users', array('id', '=', $userid))->all();
  if(count($users_results) < 1) {
    $user_salt = Hash::salt(32);
    $default_language = escape($db->get('vncp_settings', array('item', '=', 'default_language'))->first()->value);
    $db->insert('vncp_users', array(
        'id' => (int)$userid,
        'email' => strtolower($email),
        'username' => strtolower($email),
        'password' => Hash::make($pw, $user_salt),
        'salt' => $user_salt,
        'tfa_enabled' => 0,
        'tfa_secret' => '',
        'group' => 1,
        'locked' => 0,
        'language' => $default_language
    ));
  }else{
    $pw = -1;
  }
  $natnode = '';
  $natp = (int)$natp;
  $natd = (int)$natd;
  if($nat == 'on') {
    $natnode = $db->get('vncp_nat', array('node', '=', $node))->all();
    if(count($natnode) != 1) {
      $retval['message'] = 'Could not create NAT VPS. Selected node is not NAT-enabled.';
      return $retval;
    }else{
      if(empty($natp) || $natp < 1 || $natp > 30) {
        $natp = 20;
      }
      if(empty($natd) || $natd < 0 || $natd > 15) {
        $natd =  5;
      }
    }
  }
  $node_results = $db->get('vncp_nodes', array('name', '=', $node));
  $node_data = $node_results->first();
  $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
  $noLogin = false;
  if(!$pxAPI->login()) $noLogin = true;
  if($noLogin == false) {
  	$plaintext_password = getRandomString(12);
  	$createpool = $pxAPI->post('/pools', array(
  		'poolid' => $poolid
  	));
  	sleep(1);
  	$createuser = $pxAPI->post('/access/users', array(
  		'userid' => $poolid . '@pve',
  		'password' => $plaintext_password
  	));
  	sleep(1);
  	$setpoolperms = $pxAPI->put('/access/acl', array(
  		'path' => '/pool/' . $poolid,
  		'users' => $poolid . '@pve',
  		'roles' => 'PVEVMUser'
  	));
  	sleep(1);
  	$allVMIDs = [];
  	$getallKVM = $pxAPI->get('/nodes/'.$node.'/qemu');
  	for($i = 0; $i < count($getallKVM); $i++) {
  	  $allVMIDs[] = (int)$getallKVM[$i]['vmid'];
  	}
  	$getallLXC = $pxAPI->get('/nodes/'.$node.'/lxc');
  	for($i = 0; $i < count($getallLXC); $i++) {
  	  $allVMIDs[] = (int)$getallLXC[$i]['vmid'];
  	}
  	$getvmid = array_keys($allVMIDs, max($allVMIDs));
  	$getvmid = ((int)$allVMIDs[$getvmid[0]]) + 1;
  	sleep(1);
  	$saved_macaddr = MacAddress::generateMacAddress();
    $template = $db->get('vncp_lxc_templates', array('friendly_name', '=', $osfriendly))->first();
    $ipv4 = private_getIPv4FromPool($db, $node, $userid, $hbid, $nat);
    $subnet = '/24';
    if($ipv4[1] == '255.255.0.0') {
      $subnet = '/16';
    }else if($ipv4[1] == '255.255.128.0') {
      $subnet = '/17';
    }else if($ipv4[1] == '255.255.192.0') {
      $subnet = '/18';
    }else if($ipv4[1] == '255.255.224.0') {
      $subnet = '/19';
    }else if($ipv4[1] == '255.255.240.0') {
      $subnet = '/20';
    }else if($ipv4[1] == '255.255.248.0') {
      $subnet = '/21';
    }else if($ipv4[1] == '255.255.252.0') {
      $subnet = '/22';
    }else if($ipv4[1] == '255.255.254.0') {
      $subnet = '/23';
    }else if($ipv4[1] == '255.255.255.128') {
      $subnet = '/25';
    }else if($ipv4[1] == '255.255.255.192') {
      $subnet = '/26';
    }else if($ipv4[1] == '255.255.255.224') {
      $subnet = '/27';
    }else if($ipv4[1] == '255.255.255.240') {
      $subnet = '/28';
    }else if($ipv4[1] == '255.255.255.248') {
      $subnet = '/29';
    }else if($ipv4[1] == '255.255.255.252') {
      $subnet = '/30';
    }
    $storage_location = private_getStorageLocation($node, $pxAPI);
    $ostype = 'unmanaged';
    if(strpos(strtolower($osfriendly), 'debian') !== false) {
      $ostype = 'debian';
    }else if(strpos(strtolower($osfriendly), 'ubuntu') !== false) {
      $ostype = 'ubuntu';
    }else if(strpos(strtolower($osfriendly), 'centos') !== false) {
      $ostype = 'centos';
    }else if(strpos(strtolower($osfriendly), 'fedora') !== false) {
      $ostype = 'fedora';
    }else if(strpos(strtolower($osfriendly), 'opensuse') !== false) {
      $ostype = 'opensuse';
    }else if(strpos(strtolower($osfriendly), 'archlinux') !== false) {
      $ostype = 'archlinux';
    }else if(strpos(strtolower($osfriendly), 'alpine') !== false) {
      $ostype = 'alpine';
    }else if(strpos(strtolower($osfriendly), 'gentoo') !== false) {
      $ostype = 'gentoo';
    }
    $newvm = array(
  		'ostemplate' => $template->volid,
  		'vmid' => (int)$getvmid,
  		'cmode' => 'tty',
  		'cores' => (int)$cpu,
  		'cpulimit' => 0,
  		'cpuunits' => 1024,
  		'description' => $ipv4[0],
  		'hostname' => $hostname,
  		'memory' => (int)$ram,
  		'onboot' => 0,
  		'ostype' => $ostype,
  		'password' => $plaintext_password,
  		'pool' => $poolid,
  		'protection' => 0,
      'rootfs' => ''.$storage_location.':'.$storage,
  		'storage' => $storage_location,
  		'swap' => 512,
  		'tty' => 2,
      'unprivileged' => 1
  	);
    if($nat == 'on') {
      $newvm['net0'] = 'bridge=vmbr10,hwaddr='.$saved_macaddr.',ip='.$ipv4[0].$subnet.',gw='.$ipv4[2].',ip6=auto,name=eth0,type=veth';
    }else{
      $newvm['net0'] = 'bridge=vmbr0,hwaddr='.$saved_macaddr.',ip='.$ipv4[0].$subnet.',gw='.$ipv4[2].',ip6=auto,name=eth0,type=veth';
    }
    if(!empty($portspeed) && (int)$portspeed > 0 && (int)$portspeed < 10001) {
      $newvm['net0'] = $newvm['net0'] . ',rate=' . (string)$portspeed;
    }
    if(!empty($vlantag) && (int)$vlantag > 0 && (int)$vlantag < 4095) {
      $newvm['net0'] = $newvm['net0'] . ',tag=' . (string)$vlantag;
    }
  	$createlxc = $pxAPI->post('/nodes/'.$node.'/lxc', $newvm);
  	if(!$createlxc) {
  		$retval['message'] = 'Could not create LXC. Proxmox API returned error';
  	}else{
  		$allow_backups = escape($db->get('vncp_settings', array('item', '=', 'enable_backups'))->first()->value);
  		$abvalue = -1;
  		if($allow_backups == 'true') {
  		  $abvalue = 1;
  		}else{
  		  $abvalue = 0;
  		}
  		$db->insert('vncp_lxc_ct', array(
  			'user_id' => $userid,
  			'node' => $node,
  			'os' => $osfriendly,
  			'hb_account_id' => $hbid,
  			'pool_id' => $poolid,
  			'pool_password' => encryptValue($plaintext_password),
  			'ip' => $ipv4[0],
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
        'userid' => $userid,
        'hb_account_id' => $hbid,
        'backuplimit' => (int)$backuplimit
      ));
  		$today = new DateTime();
  		$today->add(new DateInterval('P30D'));
  		$reset_date = $today->format('Y-m-d 00:00:00');
  		$db->insert('vncp_bandwidth_monitor', array(
  		  'node' => $node,
  		  'pool_id' => $poolid,
  		  'hb_account_id' => $hbid,
  		  'ct_type' => 'lxc',
  		  'current' => 0,
  		  'max' => ((int)$bwlimit * 1073741824),
  		  'reset_date' => $reset_date,
  		  'suspended' => 0
  		));
  		$saved_network = explode('.', $ipv4[2]);
  		$db->insert('vncp_dhcp', array(
  		  'mac_address' => $saved_macaddr,
  		  'ip' => $ipv4[0],
  		  'gateway' => $ipv4[2],
  		  'netmask' => $ipv4[1],
  		  'network' => $saved_network[0].'.'.$saved_network[1].'.'.$saved_network[2].'.'.(string)((int)$saved_network[3] - 1),
  		  'type' => 0
  		));
      if($nat == 'on') {
        $db->insert('vncp_natforwarding', array(
          'user_id' => $userid,
          'node' => $node,
          'hb_account_id' => $hbid,
          'avail_ports' => $natp,
          'ports' => '',
          'avail_domains' => $natd,
          'domains' => ''
        ));
      }
      $retval['success'] = 1;
      $retval['message'] = 'success';
      $retval['data'] = ['success'];
      if($pw == -1) {
        $retval['data'][] = $pw;
      }
  	}
  }else{
    $retval['message'] = 'Could not connect to Proxmox node';
  }
  return $retval;
}

function api_createKVM($db, $userid, $node, $osfriendly, $ostype, $hbid, $poolid, $hostname, $storage, $cpu, $ram, $nicdriver, $cputype, $strdriver, $osinstalltype, $ostemp, $bwlimit, $email, $pw, $nat, $natp, $natd, $vlantag, $portspeed, $backuplimit) {
  $retval = array(
    'success' => 0,
    'message' => 'createKVM failed',
    'data' => []
  );
  $cipw = $pw;
  $users_results = $db->get('vncp_users', array('id', '=', $userid))->all();
  if(count($users_results) < 1) {
    $user_salt = Hash::salt(32);
    $default_language = escape($db->get('vncp_settings', array('item', '=', 'default_language'))->first()->value);
    $db->insert('vncp_users', array(
        'id' => (int)$userid,
        'email' => strtolower($email),
        'username' => strtolower($email),
        'password' => Hash::make($pw, $user_salt),
        'salt' => $user_salt,
        'tfa_enabled' => 0,
        'tfa_secret' => '',
        'group' => 1,
        'locked' => 0,
        'language' => $default_language
    ));
  }else{
    $pw = -1;
  }
  $natnode = '';
  $natp = (int)$natp;
  $natd = (int)$natd;
  if($nat == 'on') {
    $natnode = $db->get('vncp_nat', array('node', '=', $node))->all();
    if(count($natnode) != 1) {
      $retval['message'] = 'Could not create NAT VPS. Selected node is not NAT-enabled.';
      return $retval;
    }else{
      if(empty($natp) || $natp < 1 || $natp > 30) {
        $natp = 20;
      }
      if(empty($natd) || $natd < 0 || $natd > 15) {
        $natd =  5;
      }
    }
  }
  if($osinstalltype == 'iso') {
    $node_results = $db->get('vncp_nodes', array('name', '=', $node));
    $node_data = $node_results->first();
    $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
    $noLogin = false;
    if(!$pxAPI->login()) $noLogin = true;
    if($noLogin == false) {
      $plaintext_password = getRandomString(12);
      $createpool = $pxAPI->post('/pools', array(
        'poolid' => $poolid
      ));
      sleep(1);
      $createuser = $pxAPI->post('/access/users', array(
        'userid' => $poolid . '@pve',
        'password' => $plaintext_password
      ));
      sleep(1);
      $setpoolperms = $pxAPI->put('/access/acl', array(
        'path' => '/pool/' . $poolid,
        'users' => $poolid . '@pve',
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
      sleep(1);
      if($strdriver == 'ide') {
        $bootdisk = 'ide0';
        $vga = 'std';
      }else{
        $bootdisk = 'virtio0';
        $vga = 'cirrus';
      }
      $saved_macaddr = MacAddress::generateMacAddress();
      $ipv4 = private_getIPv4FromPool($db, $node, $userid, $hbid, $nat);
      $iso = private_getISO($db, $osfriendly);
      $storage_location = private_getStorageLocation($node, $pxAPI);
      if($iso) {
        $newvm = array(
          'vmid' => (int)$getvmid,
          'agent' => 0,
          'acpi' => 1,
          'balloon' => (int)$ram,
          'boot' => 'cdn',
          'bootdisk' => $bootdisk,
          'cores' => (int)$cpu,
          'cpu' => $cputype,
          'cpulimit' => '0',
          'cpuunits' => 1024,
          'description' => $ipv4[0],
          'hotplug' => '1',
          'ide2' => $iso . ',media=cdrom',
          'kvm' => 1,
          'localtime' => 1,
          'memory' => (int)$ram,
          'name' => $hostname,
          'numa' => 0,
          'onboot' => 0,
          'ostype' => 'other',
          'pool' => $poolid,
          'protection' => 0,
          'reboot' => 1,
          'sockets' => 1,
          'storage' => $storage_location,
          'tablet' => 1,
          'template' => 0,
          'vga' => $vga
        );
        if($nat == 'on') {
          $newvm['net0'] = 'bridge=vmbr10,' . $nicdriver . '=' . $saved_macaddr;
        }else{
          $newvm['net0'] = 'bridge=vmbr0,' . $nicdriver . '=' . $saved_macaddr;
        }
        if(!empty($portspeed) && (int)$portspeed > 0 && (int)$portspeed < 10001) {
          $newvm['net0'] = $newvm['net0'] . ',rate=' . (string)$portspeed;
        }
        if(!empty($vlantag) && (int)$vlantag > 0 && (int)$vlantag < 4095) {
          $newvm['net0'] = $newvm['net0'] . ',tag=' . (string)$vlantag;
        }
        if($strdriver == 'ide') {
          $newvm['ide0'] = $storage_location . ':' . $storage . ',cache=writeback';
        }else{
          $newvm['virtio0'] = $storage_location . ':' . $storage . ',cache=writeback';
        }
        $createkvm = $pxAPI->post('/nodes/'.$node.'/qemu', $newvm);
        if(!$createkvm) {
          $retval['message'] = 'Could not create KVM. Proxmox API returned error';
        }else{
          $allow_backups = escape($db->get('vncp_settings', array('item', '=', 'enable_backups'))->first()->value);
          $abvalue = -1;
          if($allow_backups == 'true') {
            $abvalue = 1;
          }else{
            $abvalue = 0;
          }
          $db->insert('vncp_kvm_ct', array(
            'user_id' => $userid,
            'node' => $node,
            'os' => explode('/', $iso)[1],
            'hb_account_id' => $hbid,
            'pool_id' => $poolid,
            'pool_password' => encryptValue($plaintext_password),
            'ip' => $ipv4[0],
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
            'userid' => $userid,
            'hb_account_id' => $hbid,
            'backuplimit' => (int)$backuplimit
          ));
          $today = new DateTime();
          $today->add(new DateInterval('P30D'));
          $reset_date = $today->format('Y-m-d 00:00:00');
          $db->insert('vncp_bandwidth_monitor', array(
            'node' => $node,
            'pool_id' => $poolid,
            'hb_account_id' => $hbid,
            'ct_type' => 'qemu',
            'current' => 0,
            'max' => ((int)$bwlimit * 1073741824),
            'reset_date' => $reset_date,
            'suspended' => 0
          ));
          $saved_network = explode('.', $ipv4[2]);
          $saved_dhcp = $saved_network[0].'.'.$saved_network[1].'.'.$saved_network[2].'.'.(string)((int)$saved_network[3] - 1);
          $db->insert('vncp_dhcp', array(
            'mac_address' => $saved_macaddr,
            'ip' => $ipv4[0],
            'gateway' => $ipv4[2],
            'netmask' => $ipv4[1],
            'network' => $saved_dhcp,
            'type' => 0
          ));
          if($nat == 'on') {
            $db->insert('vncp_natforwarding', array(
              'user_id' => $userid,
              'node' => $node,
              'hb_account_id' => $hbid,
              'avail_ports' => $natp,
              'ports' => '',
              'avail_domains' => $natd,
              'domains' => ''
            ));
          }
          $fulldhcp = $db->get('vncp_dhcp', array('network', '=', $saved_dhcp))->all();
          if($dhcp_server = $db->get('vncp_dhcp_servers', array('dhcp_network', '=', $saved_dhcp))->first()) {
            $ssh = new SSH2($dhcp_server->hostname, (int)$dhcp_server->port);
            if($ssh->login('root', decryptValue($dhcp_server->password))) {
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
          }
          $retval['success'] = 1;
          $retval['message'] = 'success';
          $retval['data'] = ['success'];
          if($pw == -1) {
            $retval['data'][] = $pw;
          }
        }
      }else{
        $retval['message'] = 'Could not find ISO file';
      }
    }else{
      $retval['message'] = 'Could not connect to Proxmox node';
    }
  }else if($osinstalltype == 'template') {
    $node_results = $db->get('vncp_nodes', array('name', '=', $node));
    $node_data = $node_results->first();
    $pxAPI = new PVE2_API($node_data->hostname, $node_data->username, $node_data->realm, decryptValue($node_data->password));
    $noLogin = false;
    if(!$pxAPI->login()) $noLogin = true;
    if($noLogin == false) {
      $plaintext_password = getRandomString(12);
      $cipassword = $cipw;
      $createpool = $pxAPI->post('/pools', array(
    	  'poolid' => $poolid
      ));
      sleep(1);
      $createuser = $pxAPI->post('/access/users', array(
    	  'userid' => $poolid . '@pve',
    	  'password' => $plaintext_password
      ));
      sleep(1);
      $setpoolperms = $pxAPI->put('/access/acl', array(
    	  'path' => '/pool/' . $poolid,
    	  'users' => $poolid . '@pve',
    	  'roles' => 'PVEVMUser'
      ));
      sleep(1);
      $allVMIDs = [];
      $getallKVM = $pxAPI->get('/nodes/'.$node.'/qemu');
      for($i = 0; $i < count($getallKVM); $i++) {
    	   $allVMIDs[] = (int)$getallKVM[$i]['vmid'];
      }
      $getallLXC = $pxAPI->get('/nodes/'.$node.'/lxc');
      for($i = 0; $i < count($getallLXC); $i++) {
    	   $allVMIDs[] = (int)$getallLXC[$i]['vmid'];
      }
      $getvmid = array_keys($allVMIDs, max($allVMIDs));
      $getvmid = ((int)$allVMIDs[$getvmid[0]]) + 1;
      sleep(1);
      $ipv4 = private_getIPv4FromPool($db, $node, $userid, $hbid, $nat);
      $storage_location = private_getStorageLocation($node, $pxAPI);
      $newvm = array(
    	  'newid' => (int)$getvmid,
    	  'description' => $ipv4[0],
    	  'format' => 'qcow2',
    	  'full' => 1,
    	  'name' => $hostname,
    	  'pool' => $poolid,
    	  'storage' => $storage_location
      );
      if(!empty($vlantag) && (int)$vlantag > 0 && (int)$vlantag < 4095) {
        $vlantag = $vlantag;
      }else{
        $vlantag = "0";
      }
      if(!empty($portspeed) && (int)$portspeed > 0 && (int)$portspeed < 10001) {
        $portspeed = $portspeed;
      }else{
        $portspeed = -1;
      }
      $clonevm = $db->get('vncp_kvm_templates', array('friendly_name', '=', $ostemp))->all();
      for($i = 0; $i < count($clonevm); $i++) {
        if($clonevm[$i]->node == $node) {
          $clonevm = $clonevm[$i];
          break;
        }
      }
      $createkvm = $pxAPI->post('/nodes/'.$node.'/qemu/'.$clonevm->vmid.'/clone', $newvm);
      if(!$createkvm) {
    	  $retval['message'] = 'Could not create KVM. Proxmox API returned error';
      }else{
      	$db->insert('vncp_pending_clone', array(
      	  'node' => $node,
      	  'upid' => $createkvm,
      	  'hb_account_id' => $hbid,
      	  'data' => json_encode(array(
        		'vmid' => $getvmid,
        		'cores' => $cpu,
        		'cpu' => $cputype,
        		'memory' => $ram,
        		'cipassword' => encryptValue($cipassword),
        		'storage_size' => $storage,
        		'cvmtype' => $clonevm->type,
        		'gateway' => $ipv4[2],
        		'ip' => $ipv4[0],
        		'netmask' => $ipv4[1],
            'portspeed' => $portspeed,
            'vlantag' => $vlantag
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
      		'user_id' => $userid,
      		'node' => $node,
      		'os' => $clonevm->friendly_name,
      		'hb_account_id' => $hbid,
      		'pool_id' => $poolid,
      		'pool_password' => encryptValue($plaintext_password),
      		'ip' => $ipv4[0],
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
          'userid' => $userid,
          'hb_account_id' => $hbid,
          'backuplimit' => (int)$backuplimit
        ));
      	$today = new DateTime();
      	$today->add(new DateInterval('P30D'));
      	$reset_date = $today->format('Y-m-d 00:00:00');
      	$db->insert('vncp_bandwidth_monitor', array(
      	  'node' => $node,
      	  'pool_id' => $poolid,
      	  'hb_account_id' => $hbid,
      	  'ct_type' => 'qemu',
      	  'current' => 0,
      	  'max' => ((int)$bwlimit * 1073741824),
      	  'reset_date' => $reset_date,
      	  'suspended' => 0
      	));
        if($nat == 'on') {
          $db->insert('vncp_natforwarding', array(
            'user_id' => $userid,
            'node' => $node,
            'hb_account_id' => $hbid,
            'avail_ports' => $natp,
            'ports' => '',
            'avail_domains' => $natd,
            'domains' => ''
          ));
        }
        $retval['success'] = 1;
        $retval['message'] = 'success';
        $retval['data'] = ['success'];
        if($pw == -1) {
          $retval['data'][] = $pw;
        }
      }
    }else{
      $retval['message'] = 'Could not connect to Proxmox node';
    }
  }
  return $retval;
}

if(($ua == 'ProxCP WHMCS Module' || $ua == 'ProxCP Blesta Module') && Input::exists()) {
  if(Config::get('instance/installed') == true) {
    $db = DB::getInstance();
    $apiid = Input::get('api_id');
    $apikey = Input::get('api_key');
    $idkey = $db->get('vncp_api', array('api_id', '=', $apiid))->first();
    if(count($idkey) == 1 && $idkey->api_key == $apikey && $idkey->api_ip == $from) {
      $a = Input::get('action');
      if($a == 'getnodes') {
        $response = api_getNodes($db);
      }else if($a == 'createkvm') {
        $userid = Input::get('userid');
        $email = base64_decode(Input::get('email'));
        $pw = base64_decode(Input::get('pw'));
        $node = Input::get('node');
        $osfriendly = Input::get('osfriendly');
        $ostype = Input::get('ostype');
        $hbid = Input::get('hbid');
        $poolid = Input::get('poolid');
        $hostname = Input::get('hostname');
        $storage = Input::get('storage');
        $cpu = Input::get('cpu');
        $ram = Input::get('ram');
        $nicdriver = Input::get('nicdriver');
        $cputype = Input::get('cputype');
        $strdriver = Input::get('strdriver');
        $osinstalltype = Input::get('osinstalltype');
        $ostemp = Input::get('ostemp');
        $bwlimit = Input::get('bwlimit');
        $nat = Input::get('nat');
        $natp = Input::get('natp');
        $natd = Input::get('natd');
        $vlantag = Input::get('vlantag');
        $portspeed = Input::get('portspeed');
        $backuplimit = Input::get('backuplimit');
        if((int)$vlantag < 0)
          $vlantag = 0;
        if((int)$vlantag > 4094)
          $vlantag = 4094;
        if((int)$portspeed < 0)
          $portspeed = 0;
        if((int)$portspeed > 10000)
          $portspeed = 10000;
        if((int)$backuplimit < -1)
          $backuplimit = -1;
        if((int)$backuplimit > 1000)
          $backuplimit = 1000;
        $response = api_createKVM($db, $userid, $node, $osfriendly, $ostype, $hbid, $poolid, $hostname, $storage, $cpu, $ram, $nicdriver, $cputype, $strdriver, $osinstalltype, $ostemp, $bwlimit, $email, $pw, $nat, $natp, $natd, $vlantag, $portspeed, $backuplimit);
      }else if($a == 'createcloud') {
        $userid = Input::get('userid');
        $email = base64_decode(Input::get('email'));
        $pw = base64_decode(Input::get('pw'));
        $node = Input::get('node');
        $hbid = Input::get('hbid');
        $poolid = Input::get('poolid');
        $storage = Input::get('storage');
        $cpu = Input::get('cpu');
        $ram = Input::get('ram');
        $cputype = Input::get('cputype');
        $howmanyips = Input::get('howmanyips');
        $response = api_createCloud($db, $userid, $email, $pw, $node, $hbid, $poolid, $storage, $cpu, $ram, $cputype, $howmanyips);
      }else if($a == 'createlxc') {
        $userid = Input::get('userid');
        $email = base64_decode(Input::get('email'));
        $pw = base64_decode(Input::get('pw'));
        $node = Input::get('node');
        $osfriendly = Input::get('osfriendly');
        $ostype = Input::get('ostype');
        $hbid = Input::get('hbid');
        $poolid = Input::get('poolid');
        $hostname = Input::get('hostname');
        $storage = Input::get('storage');
        $cpu = Input::get('cpu');
        $ram = Input::get('ram');
        $bwlimit = Input::get('bwlimit');
        $nat = Input::get('nat');
        $natp = Input::get('natp');
        $natd = Input::get('natd');
        $vlantag = Input::get('vlantag');
        $portspeed = Input::get('portspeed');
        $backuplimit = Input::get('backuplimit');
        if((int)$vlantag < 0)
          $vlantag = 0;
        if((int)$vlantag > 4094)
          $vlantag = 4094;
        if((int)$portspeed < 0)
          $portspeed = 0;
        if((int)$portspeed > 10000)
          $portspeed = 10000;
        if((int)$backuplimit < -1)
          $backuplimit = -1;
        if((int)$backuplimit > 1000)
          $backuplimit = 1000;
        $response = api_createLXC($db, $userid, $email, $pw, $node, $osfriendly, $ostype, $hbid, $poolid, $hostname, $storage, $cpu, $ram, $bwlimit, $nat, $natp, $natd, $vlantag, $portspeed, $backuplimit);
      }else if($a == 'suspend') {
        $type = Input::get('type');
        $hbid = Input::get('hbid');
        if($type == 'kvm') {
          $record = $db->get('vncp_kvm_ct', array('hb_account_id', '=', $hbid))->first();
          $node = $record->node;
        }else if($type == 'pc') {
          $record = $db->get('vncp_kvm_cloud', array('hb_account_id', '=', $hbid))->first();
          $node = $record->nodes;
        }else{
          $record = $db->get('vncp_lxc_ct', array('hb_account_id', '=', $hbid))->first();
          $node = $record->node;
        }
        $poolid = Input::get('poolid');
        $response = api_suspend($db, $type, $hbid, $node, $poolid);
      }else if($a == 'unsuspend') {
        $type = Input::get('type');
        $hbid = Input::get('hbid');
        if($type == 'kvm') {
          $record = $db->get('vncp_kvm_ct', array('hb_account_id', '=', $hbid))->first();
          $node = $record->node;
        }else if($type == 'pc') {
          $record = $db->get('vncp_kvm_cloud', array('hb_account_id', '=', $hbid))->first();
          $node = $record->nodes;
        }else{
          $record = $db->get('vncp_lxc_ct', array('hb_account_id', '=', $hbid))->first();
          $node = $record->node;
        }
        $poolid = Input::get('poolid');
        $response = api_unsuspend($db, $type, $hbid, $node, $poolid);
      }else if($a == 'terminate') {
        $type = Input::get('type');
        $hbid = Input::get('hbid');
        if($type == 'kvm') {
          $record = $db->get('vncp_kvm_ct', array('hb_account_id', '=', $hbid))->first();
          $node = $record->node;
        }else if($type == 'pc') {
          $record = $db->get('vncp_kvm_cloud', array('hb_account_id', '=', $hbid))->first();
          $node = $record->nodes;
        }else{
          $record = $db->get('vncp_lxc_ct', array('hb_account_id', '=', $hbid))->first();
          $node = $record->node;
        }
        $poolid = Input::get('poolid');
        $userid = Input::get('userid');
        $response = api_terminate($db, $type, $hbid, $node, $poolid, $userid);
      }else if($a == 'getdetails') {
        $type = Input::get('type');
        $hbid = Input::get('hbid');
        $response = api_getDetails($db, $type, $hbid);
      }else if($a == 'startserver') {
        $type = Input::get('type');
        $hbid = Input::get('hbid');
        $userid = Input::get('userid');
        $response = api_startserver($db, $type, $hbid, $userid);
      }else if($a == 'stopserver') {
        $type = Input::get('type');
        $hbid = Input::get('hbid');
        $userid = Input::get('userid');
        $response = api_stopserver($db, $type, $hbid, $userid);
      }else{
        $response['message'] = 'Invalid action data';
      }
    }else{
      $response['message'] = 'Invalid check data';
    }
  }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
