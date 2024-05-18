<?php
use WHMCS\Database\Capsule;
use WHMCS\View\Menu\Item as MenuItem;

add_hook('ClientAreaPrimarySidebar', 10, function (MenuItem $primaryNavbar) {
  $url = '';
  $shostname = '';
  foreach(Capsule::table('tblservers')->get() as $server) {
    if($server->type == 'proxcp') {
      $url = 'https://' . $server->hostname;
      $shostname = $server->hostname;
      break;
    }
  }

  if (!is_null($primaryNavbar->getChild('Service Details Actions'))) {
	$command = 'GetClientsProducts';
	$postData = array(
		'limitnum' => 1,
		'serviceid' => $_GET['id']
	);
	$results = localAPI($command, $postData, '');
	$loggedInID = $results['products']['product'][0]['clientid'];
  $servicestatus = $results['products']['product'][0]['status'];
  $servicemodhostname = $results['products']['product'][0]['serverhostname'];

	$command = 'GetClientsDetails';
	$postData = array(
		'clientid' => $loggedInID
	);
	$results = localAPI($command, $postData, '');
	$loggedInEmail = $results['email'];

    if($servicestatus == 'Active' && $servicemodhostname == $shostname) {
      $primaryNavbar->getChild('Service Details Actions')
        ->addChild('proxcpLogin', array(
          'label' => 'Control Panel',
          'uri' => $url . '/login?u=' . urlencode(base64_encode($loggedInEmail)) . '&from=whmcs',
          'order' => '1'
      ))->setAttribute('target', '_blank');
    }
  }
});
?>
