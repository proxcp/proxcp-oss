<?php
require_once('vendor/autoload.php');
use phpseclib\Net\SSH2;
require_once('core/autoload.php');
require_once('core/init.php');
require_once('core/session.php');

$server = new \TusPhp\Tus\Server('file');
$server->setMaxUploadSize(5000000000); // 5GB

$server->event()->addListener('tus-server.upload.complete', function(\TusPhp\Events\TusEvent $event) {
  $db = DB::getInstance();
  $log = new Logger();
  $fileMeta = $event->getFile()->details();
  $request = $event->getRequest();
  $response = $event->getResponse();

  $file_path = $fileMeta['file_path'];
  $extension = substr($file_path, -4);
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $magic = finfo_file($finfo, $file_path);
  finfo_close($finfo);

  $uploadKey = explode('/', $fileMeta['location'])[4];
  $downloadKey = $uploadKey;
  if($extension != '.iso' || ($magic != 'application/octet-stream' && $magic != 'application/x-iso9660-image')) {
    unlink($file_path);
    $db->delete('vncp_kvm_isos_custom', array('upload_key', '=', $uploadKey));
    $log->log('isodel '.substr($uploadKey, 0, 32).': not an ISO', 'general', 1, 'system', '127.0.0.1');
  }else{
    $db->update_iso('vncp_kvm_isos_custom', (string)$uploadKey, array(
      'status' => 'uploaded',
      'download_key' => $downloadKey
    ));
    $log->log('isoup '.substr($uploadKey, 0, 32).' completed', 'general', 0, 'system', '127.0.0.1');
    $nodes = $db->get('vncp_nodes', array('id', '!=', 0))->first();
    $creds = $db->get('vncp_tuntap', array('node', '=', $nodes->name))->first();
    $isos = $db->get('vncp_kvm_isos', array('id', '!=', 0))->first();
    if(count($creds) > 0 && count($nodes) > 0 && count($isos) > 0) {
      $db->update_iso('vncp_kvm_isos_custom', (string)$uploadKey, array(
        'status' => 'copying'
      ));
      $noLogin = false;
      $pxAPI = new PVE2_API($nodes->hostname, $nodes->username, $nodes->realm, decryptValue($nodes->password));
    	if(!$pxAPI->login()) $noLogin = true;
      if($noLogin == true) {
        $db->update_iso('vncp_kvm_isos_custom', (string)$uploadKey, array(
          'status' => 'uploaded'
        ));
        $log->log('isocp fail '.substr($uploadKey, 0, 32).': no pxlogin '.$nodes->name.'.', 'error', 1, 'system', '127.0.0.1');
      }else{
        $storage_location = explode(':', $isos->volid)[0];
        $pxpath = $pxAPI->get('/storage/' . $storage_location);
        $pxpath = $pxpath['path'] . '/template/iso';
        $ssh = new SSH2($nodes->hostname, (int)$creds->port);
        if(!$ssh->login('root', decryptValue($creds->password))) {
          $db->update_iso('vncp_kvm_isos_custom', (string)$uploadKey, array(
            'status' => 'uploaded'
          ));
          $log->log('isocp fail '.substr($uploadKey, 0, 32).': no sshlogin '.$nodes->name.'.', 'error', 1, 'system', '127.0.0.1');
        }else{
          $ssh->exec("wget -bqc -O ".$pxpath."/".$uploadKey.".iso ".Config::get('instance/base')."/files/".$downloadKey."/get");
          $ssh->disconnect();
          unlink($file_path);
          unlink('vendor/ankitpokhrel/tus-php/.cache/tus_php.client.cache');
          unlink('vendor/ankitpokhrel/tus-php/.cache/tus_php.server.cache');
          $db->update_iso('vncp_kvm_isos_custom', (string)$uploadKey, array(
            'status' => 'active'
          ));
          $log->log('ISO '.substr($uploadKey, 0, 32).' copied and active.', 'general', 0, 'system', '127.0.0.1');
        }
      }
    }else{
      $log->log('isocp fail '.substr($uploadKey, 0, 32).'; no SSH '.$nodes->name.'.', 'error', 1, 'system', '127.0.0.1');
    }
  }
});

$response = $server->serve();

$response->send();

exit(0);
?>
