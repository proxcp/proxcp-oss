<?php
require_once('vendor/autoload.php');
require_once('core/autoload.php');
require_once('core/init.php');
require_once('core/session.php');

use TusPhp\Exception\TusException;
use TusPhp\Exception\FileException;
use TusPhp\Exception\ConnectionException;

$client = new \TusPhp\Tus\Client(Config::get('instance/base'));

if(!empty($_FILES)) {
  $fileMeta = $_FILES['tus_file'];
  $uploadKey = hash_file('md5', $fileMeta['tmp_name']);
  $uploadKey = md5($_POST['useriso_who'].$_POST['useriso_fname']).$uploadKey;
  try{
    $client->setKey($uploadKey)->file($fileMeta['tmp_name'], $_POST['useriso_who'] . '_' . time() . '_' . $fileMeta['name']);
    $bytesUploaded = $client->upload(5000000); // 5MB chunk size
    echo json_encode([
      'status' => 'uploading',
      'bytes_uploaded' => $bytesUploaded,
      'upload_key' => $uploadKey
    ]);
  }catch(ConnectionException | FileException | TusException $e) {
    echo json_encode([
      'status' => 'error',
      'bytes_uploaded' => -1,
      'upload_key' => '',
      'error' => $e->getMessage()
    ]);
  }
}else{
  echo json_encode([
    'status' => 'error',
    'bytes_uploaded' => -1,
    'error' => 'No input!'
  ]);
}
?>
