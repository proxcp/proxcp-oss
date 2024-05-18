<?php
class Proxcp extends Module {
  // --- BEGIN REQUIRED SECTION ---
  const VERSION = "1.0.1";
  private static $authors = array(array(
    'name' => 'ProxCP',
    'url' => 'https://google.com'
  ));

  public function __construct() {
    $this->loadConfig(dirname(__FILE__) . DS . "config.json");
    Loader::loadComponents($this, array("Input"));
  }
  public function getName() {
    return "proxcp";
  }
  public function getVersion() {
    return self::VERSION;
  }
  public function getAuthors() {
    return self::$authors;
  }
  public function getServiceName($service) {
    $key = "proxcp_name";
    foreach($service->fields as $field) {
      if($field->key == $key) {
        return $field->value;
      }
    }
    return null;
  }
  public function moduleRowName() {
    return "Server";
  }
  public function moduleRowNamePlural() {
    return "Servers";
  }
  public function moduleGroupName() {
    return "ProxCP Group";
  }
  public function moduleRowMetaKey() {
    return "proxcp_name";
  }
  public function getLogo() {
    return "views/default/images/logo.png";
  }
  // --- END REQUIRED SECTION ---

  public function manageModule($module, array &$vars) {
    $this->view = new View("manage", "default");
    $this->view->base_uri = $this->base_uri;
    $this->view->setDefaultView("components" . DS . "modules" . DS . "proxcp" . DS);
    Loader::loadHelpers($this, array("Form", "Html", "Widget"));
    $this->view->set("module", $module);
    return $this->view->fetch();
  }
  public function manageAddRow(array &$vars) {
    $this->view = new View("add_row", "default");
    $this->view->base_uri = $this->base_uri;
    $this->view->setDefaultView("components" . DS . "modules" . DS . "proxcp" . DS);
    Loader::loadHelpers($this, array("Form", "Html", "Widget"));
    $this->view->set("vars", (object)$vars);
    return $this->view->fetch();
  }
  public function manageEditRow($module_row, array &$vars) {
    $this->view = new View("edit_row", "default");
    $this->view->base_uri = $this->base_uri;
    $this->view->setDefaultView("components" . DS . "modules" . DS . "proxcp" . DS);
    Loader::loadHelpers($this, array("Form", "Html", "Widget"));
    if(empty($vars)) $vars = $module_row->meta;
    $this->view->set("vars", (object)$vars);
    return $this->view->fetch();
  }
  public function addModuleRow(array &$vars) {
    $meta_fields = array("server_name", "hostname", "user", "password");
    $encrypted_fields = array("user", "password");
    $this->Input->setRules($this->getRowRules($vars));
    if($this->Input->validates($vars)) {
      $meta = array();
      foreach($vars as $key => $value) {
        if(in_array($key, $meta_fields)) {
          $meta[] = array(
            'key' => $key,
            'value' => $value,
            'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
          );
        }
      }
      return $meta;
    }
  }
  public function editModuleRow($module_row, array &$vars) {
    return $this->addModuleRow($vars);
  }
  public function deleteModuleRow($module_row) {
    return null;
  }
  public function getPackageFields($vars=null) {
    Loader::loadHelpers($this, array("Html"));
    $fields = new ModuleFields();
    $fields->setHtml("
      <script type=\"text/javascript\">
        $(document).ready(function() {
          $('#proxcp_servicetype, #proxcp_isnat').change(function() {
            fetchModuleOptions();
          });
        });
      </script>
    ");
    $module_row = null;
    if(isset($vars->module_group) && $vars->module_group == "") {
      if(isset($vars->module_row) && $vars->module_row > 0) {
        $module_row = $this->getModuleRow($vars->module_row);
      }else{
        $rows = $this->getModuleRows();
        if (isset($rows[0])) $module_row = $rows[0];
        unset($rows);
      }
    }else{
      $rows = $this->getModuleRows($vars->module_group);
      if(isset($rows[0])) $module_row = $rows[0];
      unset($rows);
    }

    $select_options = array('kvm' => "KVM VPS", 'pc' => "KVM Public Cloud", 'lxc' => "LXC VPS");
    $field = $fields->label("ProxCP Service Type", "proxcp_servicetype");
    $field->attach($fields->fieldSelect("meta[servicetype]", $select_options,
      $this->Html->ifSet($vars->meta['servicetype']), array('id' => "proxcp_servicetype")));
    $fields->setField($field);
    unset($field);
    unset($select_options);

    if(isset($vars->meta['servicetype'])) {
      $select_options = $this->getNodes($module_row);
      $field = $fields->label("Proxmox Node", "proxcp_node");
      $field->attach($fields->fieldSelect("meta[node]", $select_options,
        $this->Html->ifSet($vars->meta['node']), array('id' => "proxcp_node")));
      $fields->setField($field);
      unset($field);
      unset($select_options);
    }

    if($vars->meta['servicetype'] != 'pc') {
      $select_options = array('off' => "False", 'on' => "True");
      $field = $fields->label("NAT VPS?");
      $field->attach($fields->fieldSelect("meta[isnat]", $select_options,
        $this->Html->ifSet($vars->meta['isnat']), array('id' => 'proxcp_isnat')));
      $fields->setField($field);
      unset($field);
      unset($select_options);
    }

    if($vars->meta['isnat'] == 'on' && $vars->meta['servicetype'] != 'pc') {
      $field = $fields->label("NAT Port Limit", "proxcp_natports");
      $field->attach($fields->fieldText("meta[natports]", $this->Html->ifSet($vars->meta['natports']), array('id' => 'proxcp_natports', 'class' => 'inline')));
      $fields->setField($field);
      unset($field);

      $field = $fields->label("NAT Domain Limit", "proxcp_natdomains");
      $field->attach($fields->fieldText("meta[natdomains]", $this->Html->ifSet($vars->meta['natdomains']), array('id' => 'proxcp_natdomains', 'class' => 'inline')));
      $fields->setField($field);
      unset($field);
    }

    $field = $fields->label("Storage Size (GB)", "proxcp_storagesize");
    $field->attach($fields->fieldText("meta[storagesize]", $this->Html->ifSet($vars->meta['storagesize']), array('id' => 'proxcp_storagesize', 'class' => 'inline')));
    $fields->setField($field);
    unset($field);

    $field = $fields->label("CPU Cores", "proxcp_cpucores");
    $field->attach($fields->fieldText("meta[cpucores]", $this->Html->ifSet($vars->meta['cpucores']), array('id' => 'proxcp_cpucores', 'class' => 'inline')));
    $fields->setField($field);
    unset($field);

    $field = $fields->label("RAM (MB)", "proxcp_ram");
    $field->attach($fields->fieldText("meta[ram]", $this->Html->ifSet($vars->meta['ram']), array('id' => 'proxcp_ram', 'class' => 'inline')));
    $fields->setField($field);
    unset($field);

    $field = $fields->label("Bandwidth Limit (GB)", "proxcp_bwlimit");
    $field->attach($fields->fieldText("meta[bwlimit]", $this->Html->ifSet($vars->meta['bwlimit']), array('id' => 'proxcp_bwlimit', 'class' => 'inline')));
    $fields->setField($field);
    unset($field);

    $field = $fields->label("VLAN Tag", "proxcp_vlantag");
    $field->attach($fields->fieldText("meta[vlantag]", $this->Html->ifSet($vars->meta['vlantag']), array('id' => 'proxcp_vlantag', 'class' => 'inline')));
    $fields->setField($field);
    unset($field);

    $field = $fields->label("Port Speed", "proxcp_portspeed");
    $field->attach($fields->fieldText("meta[portspeed]", $this->Html->ifSet($vars->meta['portspeed']), array('id' => 'proxcp_portspeed', 'class' => 'inline')));
    $fields->setField($field);
    unset($field);

    $field = $fields->label("Backup Limit", "proxcp_backuplimit");
    $field->attach($fields->fieldText("meta[backuplimit]", $this->Html->ifSet($vars->meta['backuplimit']), array('id' => 'proxcp_backuplimit', 'class' => 'inline')));
    $fields->setField($field);
    unset($field);

    if($vars->meta['servicetype'] == 'pc') {
      $field = $fields->label("IP Limit", "proxcp_iplimit");
      $field->attach($fields->fieldText("meta[iplimit]", $this->Html->ifSet($vars->meta['iplimit']), array('id' => 'proxcp_iplimit', 'class' => 'inline')));
      $fields->setField($field);
      unset($field);
    }

    if($vars->meta['servicetype'] == 'kvm') {
      $select_options = array('e1000' => "Intel E1000", 'virtio' => "VirtIO");
      $field = $fields->label("NIC Driver", "proxcp_nicdriver");
      $field->attach($fields->fieldSelect("meta[nicdriver]", $select_options,
        $this->Html->ifSet($vars->meta['nicdriver']), array('id' => "proxcp_nicdriver")));
      $fields->setField($field);
      unset($field);
      unset($select_options);

      $select_options = array('kvm64' => "KVM64", 'qemu64' => "QEMU64", 'host' => "Host passthrough");
      $field = $fields->label("CPU Type", "proxcp_cputype");
      $field->attach($fields->fieldSelect("meta[cputype]", $select_options,
        $this->Html->ifSet($vars->meta['cputype']), array('id' => "proxcp_cputype")));
      $fields->setField($field);
      unset($field);
      unset($select_options);

      $select_options = array('ide' => "IDE", 'virtio' => "VirtIO");
      $field = $fields->label("Storage Driver", "proxcp_storagedriver");
      $field->attach($fields->fieldSelect("meta[storagedriver]", $select_options,
        $this->Html->ifSet($vars->meta['storagedriver']), array('id' => "proxcp_storagedriver")));
      $fields->setField($field);
      unset($field);
      unset($select_options);

      $select_options = array('iso' => "Manual ISO file", 'template' => "Automatic template");
      $field = $fields->label("Default OS Installation Type", "proxcp_osinstalltype");
      $field->attach($fields->fieldSelect("meta[osinstalltype]", $select_options,
        $this->Html->ifSet($vars->meta['osinstalltype']), array('id' => "proxcp_osinstalltype")));
      $fields->setField($field);
      unset($field);
      unset($select_options);
    }

    return $fields;
  }
  public function validateService($package, array $vars = null, $edit = false) {
    $rules = array(
      'proxcp_hostname' => array(
        'format' => array(
          'rule' => array(array($this, "validateHostname")),
          'message' => 'The hostname appears to be invalid. It must include a TLD.'
        )
      ),
      'proxcp_os' => array(
        'empty' => array(
          'rule' => 'isEmpty',
          'negate' => true,
          'message' => 'You must select a valid Operating System.'
        )
      )
    );

    if($edit) {
      $rules['proxcp_hostname']['format']['if_set'] = true;
      unset($rules['proxcp_os']);
    }
    $this->Input->setRules($rules);
    return $this->Input->validates($vars['configoptions']);
  }
  public function validateHostname($host_name) {
    if(strlen($host_name) > 255) return false;
    return $this->Input->matches($host_name, "/^([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9])(\.([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]))+$/");
  }
  public function addService($package, array $vars = null, $parent_package = null, $parent_service = null, $status = "pending") {
    $row = $this->getModuleRow();
    $params = array(
		'proxcp_hostname' => $vars['configoptions']['proxcp_hostname'],
		'proxcp_os' => $vars['configoptions']['proxcp_os'],
    'proxcp_password' => $this->generatePassword()
	);
    $this->validateService($package, $vars);
    if($this->Input->errors()) return;
    if(isset($vars['use_module']) && $vars['use_module'] == "true") {
      Loader::loadModels($this, ['Clients']);
      $client = $this->Clients->get((isset($vars['client_id']) ? $vars['client_id'] : 0), false);
      try {
        $type = $package->meta->servicetype;
        $email = $client->email;
        $password = $params['proxcp_password'];

        $userid = $vars['client_id'];
        $node = $package->meta->node;
        $osfriendly = $vars['configoptions']['proxcp_os'];
        $ostype = $vars['configoptions']['proxcp_os'];
        $hb_account_id = $vars['service_id'];
        $poolid = 'client_'.$userid.'_'.$hb_account_id;
        $hostname = $vars['configoptions']['proxcp_hostname'];
        $storage_size = $package->meta->storagesize;
        $cpucores = $package->meta->cpucores;
        $ram = $package->meta->ram;
        $nicdriver = $package->meta->nicdriver;
        $cputype = $package->meta->cputype;
        $storage_driver = $package->meta->storagedriver;
        $os_installation_type = $package->meta->osinstalltype;
        $ostemplate = $vars['configoptions']['proxcp_os'];
        $bandwidth_limit = $package->meta->bwlimit;
        $howmanyips = isset($package->meta->iplimit) ? $package->meta->iplimit : 0;
        $isNAT = $package->meta->isnat;
        $natports = $package->meta->natports;
        $natdomains = $package->meta->natdomains;
        $vlantag = $package->meta->vlantag;
        $portspeed = $package->meta->portspeed;
        $backuplimit = $package->meta->backuplimit;
        if($isNAT != 'on') {
          $isNAT = 'off';
        }
        if(empty($vlantag)) {
          $vlantag = "0";
        }
        if(empty($portspeed)) {
          $portspeed = "0";
        }
        if(empty($backuplimit)) {
          $backuplimit = "0";
        }

        if($type == 'kvm') {
          $post_info = 'api_id='.$row->meta->user.'&api_key='.$row->meta->password.'&action=createkvm&userid='.$userid.'&node='.$node.'&osfriendly='.$osfriendly.'&ostype='.$ostype.'&hbid='.$hb_account_id.'&poolid='.$poolid.'&hostname='.$hostname.'&storage='.$storage_size.'&cpu='.$cpucores.'&ram='.$ram.'&nicdriver='.$nicdriver.'&cputype='.$cputype.'&strdriver='.$storage_driver.'&osinstalltype='.$os_installation_type.'&ostemp='.$ostemplate.'&bwlimit='.$bandwidth_limit.'&email='.base64_encode($email).'&pw='.base64_encode($password).'&nat='.$isNAT.'&natp='.$natports.'&natd='.$natdomains.'&vlantag='.$vlantag.'&portspeed='.$portspeed.'&backuplimit='.$backuplimit;
        }else if($type == 'pc') {
          $post_info = 'api_id='.$row->meta->user.'&api_key='.$row->meta->password.'&action=createcloud&userid='.$userid.'&hbid='.$hb_account_id.'&poolid='.$poolid.'&node='.$node.'&howmanyips='.$howmanyips.'&cpu='.$cpucores.'&cputype='.$cputype.'&ram='.$ram.'&storage='.$storage_size.'&email='.base64_encode($email).'&pw='.base64_encode($password);
        }else if($type == 'lxc') {
          $post_info = 'api_id='.$row->meta->user.'&api_key='.$row->meta->password.'&action=createlxc&userid='.$userid.'&node='.$node.'&osfriendly='.$osfriendly.'&ostype='.$ostype.'&hbid='.$hb_account_id.'&poolid='.$poolid.'&hostname='.$hostname.'&storage='.$storage_size.'&cpu='.$cpucores.'&ram='.$ram.'&bwlimit='.$bwlimit.'&email='.base64_encode($email).'&pw='.base64_encode($password).'&nat='.$isNAT.'&natp='.$natports.'&natd='.$natdomains.'&vlantag='.$vlantag.'&portspeed='.$portspeed.'&backuplimit='.$backuplimit;
        }else{
          $this->log("Invalid ProxCP Service Type", "input", true);
        }
        $api_url = 'https://'.$row->meta->hostname.'/api.php';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
      	curl_setopt($ch, CURLOPT_POST, 1);
      	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_info);
      	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ProxCP Blesta Module');
        $response = curl_exec($ch);
        if(curl_error($ch)) {
          $this->log('Unable to connect: ' . curl_errno($ch), curl_error($ch), "input", true);
        }else if(empty($response)) {
          $this->log('Empty response', "input", true);
        }
        curl_close($ch);
        $response = json_decode($response, true);
        if(is_null($response)) {
          $this->log('Invalid response format', "input", true);
        }
        if($response['success'] == 0) {
          $this->log($response['message'], "input", true);
        }
        if(array_key_exists('data', $response) && count($response['data'] == 2) && $response['data'][1] == -1) {
          $params['proxcp_password'] = 'N/A - you already have an account.';
        }
      }catch(Exception $e) {
        $this->log($params, $e->getMessage(), "input", true);
      }
    }
    return array(
      array(
        'key' => 'proxcp_hostname',
        'value' => $params['proxcp_hostname'],
        'encrypted' => 0
      ),
      array(
        'key' => 'proxcp_os',
        'value' => $params['proxcp_os'],
        'encrypted' => 0
      ),
      array(
        'key' => 'proxcp_password',
        'value' => $params['proxcp_password'],
        'encrypted' => 0
      ),
      array(
        'key' => 'proxcp_username',
        'value' => isset($email) ? $email : '',
        'encrypted' => 0
      )
    );
  }
  public function cancelService($package, $service, $parent_package = null, $parent_service = null) {
    if(($row = $this->getModuleRow())) {
      $service_fields = $this->serviceFieldsToObject($service->fields);
      try {
        $type = $package->meta->servicetype;
        $hbid = $service->id;
        $userid = $service->client_id;
        $poolid = 'client_'.$userid.'_'.$hbid;
        $api_url = 'https://'.$row->meta->hostname.'/api.php';
        $post_info = 'api_id='.$row->meta->user.'&api_key='.$row->meta->password.'&action=terminate&type='.$type.'&hbid='.$hbid.'&poolid='.$poolid.'&userid='.$userid;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
      	curl_setopt($ch, CURLOPT_POST, 1);
      	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_info);
      	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ProxCP Blesta Module');
        $response = curl_exec($ch);
        if(curl_error($ch)) {
          $this->log('Unable to connect: ' . curl_errno($ch), curl_error($ch), "input", true);
        }else if(empty($response)) {
          $this->log('Empty response', "input", true);
        }
        curl_close($ch);
        $response = json_decode($response, true);
        if(is_null($response)) {
          $this->log('Invalid response format', "input", true);
        }
        if($response['success'] == 0) {
          $this->log($response['message'], "input", true);
        }
      }catch(Exception $e) {
        $this->log($service_fields, $e->getMessage(), "input", true);
      }
    }
    return null;
  }
  public function suspendService($package, $service, $parent_package = null, $parent_service = null) {
    if(($row = $this->getModuleRow())) {
      $service_fields = $this->serviceFieldsToObject($service->fields);
      try {
        $type = $package->meta->servicetype;
        $hbid = $service->id;
        $userid = $service->client_id;
        $poolid = 'client_'.$userid.'_'.$hbid;
        $api_url = 'https://'.$row->meta->hostname.'/api.php';
        $post_info = 'api_id='.$row->meta->user.'&api_key='.$row->meta->password.'&action=suspend&type='.$type.'&hbid='.$hbid.'&poolid='.$poolid;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
      	curl_setopt($ch, CURLOPT_POST, 1);
      	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_info);
      	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ProxCP Blesta Module');
        $response = curl_exec($ch);
        if(curl_error($ch)) {
          $this->log('Unable to connect: ' . curl_errno($ch), curl_error($ch), "input", true);
        }else if(empty($response)) {
          $this->log('Empty response', "input", true);
        }
        curl_close($ch);
        $response = json_decode($response, true);
        if(is_null($response)) {
          $this->log('Invalid response format', "input", true);
        }
        if($response['success'] == 0) {
          $this->log($response['message'], "input", true);
        }
      }catch(Exception $e) {
        $this->log($service_fields, $e->getMessage(), "input", true);
      }
    }
    return array(
      array(
        'key' => 'proxcp_hostname',
        'value' => $service_fields->proxcp_hostname,
        'encrypted' => 0
      ),
      array(
        'key' => 'proxcp_os',
        'value' => $service_fields->proxcp_os,
        'encrypted' => 0
      )
    );
  }
  public function unsuspendService($package, $service, $parent_package = null, $parent_service = null) {
    if(($row = $this->getModuleRow())) {
      $service_fields = $this->serviceFieldsToObject($service->fields);
      try {
        $type = $package->meta->servicetype;
        $hbid = $service->id;
        $userid = $service->client_id;
        $poolid = 'client_'.$userid.'_'.$hbid;
        $api_url = 'https://'.$row->meta->hostname.'/api.php';
        $post_info = 'api_id='.$row->meta->user.'&api_key='.$row->meta->password.'&action=unsuspend&type='.$type.'&hbid='.$hbid.'&poolid='.$poolid;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
      	curl_setopt($ch, CURLOPT_POST, 1);
      	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_info);
      	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ProxCP Blesta Module');
        $response = curl_exec($ch);
        if(curl_error($ch)) {
          $this->log('Unable to connect: ' . curl_errno($ch), curl_error($ch), "input", true);
        }else if(empty($response)) {
          $this->log('Empty response', "input", true);
        }
        curl_close($ch);
        $response = json_decode($response, true);
        if(is_null($response)) {
          $this->log('Invalid response format', "input", true);
        }
        if($response['success'] == 0) {
          $this->log($response['message'], "input", true);
        }
      }catch(Exception $e) {
        $this->log($service_fields, $e->getMessage(), "input", true);
      }
    }
    return array(
      array(
        'key' => 'proxcp_hostname',
        'value' => $service_fields->proxcp_hostname,
        'encrypted' => 0
      ),
      array(
        'key' => 'proxcp_os',
        'value' => $service_fields->proxcp_os,
        'encrypted' => 0
      )
    );
  }
  public function getEmailTags() {
    return [
      'module' => ['server_name', 'hostname'],
      'service' => ['proxcp_password', 'proxcp_hostname', 'proxcp_username']
    ];
  }
  public function getAdminServiceInfo($service, $package) {
    $row = $this->getModuleRow();
    $this->view = new View("admin_service_info", "default");
    $this->view->basea_uri = $this->base_uri;
    $this->view->setDefaultView("components" . DS . "modules" . DS . "proxcp" . DS);
    Loader::loadHelpers($this, array("Form", "Html"));
    $this->view->set("module_row", $row);
    $this->view->set("package", $package);
    $this->view->set("service", $service);
    $this->view->set("service_fields", $this->serviceFieldsToObject($service->fields));
    $service_details = '';
    if($service->status == 'active') {
      $service_details = $this->getServiceDetails($service, $package, $row);
    }else{
      $service_details = array(
        'node' => 'N/A',
        'ip' => 'N/A',
        'status' => 'N/A',
        'os' => 'N/A'
      );
    }
    $this->view->set("service_node", $service_details['node']);
    $this->view->set("service_ip", $service_details['ip']);
    $this->view->set("service_status", $service_details['status']);
    $this->view->set("service_os", $service_details['os']);
    return $this->view->fetch();
  }
  public function getClientServiceInfo($service, $package) {
    $row = $this->getModuleRow();
    $this->view = new View("client_service_info", "default");
    $this->view->basea_uri = $this->base_uri;
    $this->view->setDefaultView("components" . DS . "modules" . DS . "proxcp" . DS);
    Loader::loadHelpers($this, array("Form", "Html"));
    $this->view->set("module_row", $row);
    $this->view->set("package", $package);
    $this->view->set("service", $service);
    $this->view->set("service_fields", $this->serviceFieldsToObject($service->fields));
    $service_details = '';
    if($service->status == 'active') {
      $service_details = $this->getServiceDetails($service, $package, $row);
    }else{
      $service_details = array(
        'node' => 'N/A',
        'ip' => 'N/A',
        'status' => 'N/A',
        'os' => 'N/A'
      );
    }
    $this->view->set("service_node", $service_details['node']);
    $this->view->set("service_ip", $service_details['ip']);
    $this->view->set("service_status", $service_details['status']);
    $this->view->set("service_os", $service_details['os']);
    return $this->view->fetch();
  }
  private function getServiceDetails($service, $package, $row) {
    $node = '';
    $ip = '';
    try {
      $type = $package->meta->servicetype;
      $hbid = $service->id;
      $api_url = 'https://'.$row->meta->hostname.'/api.php';
      $post_info = 'api_id='.$row->meta->user.'&api_key='.$row->meta->password.'&action=getdetails&type='.$type.'&hbid='.$hbid;

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $api_url);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_info);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_USERAGENT, 'ProxCP Blesta Module');
      $response = curl_exec($ch);
      if(curl_error($ch)) {
        $this->log('Unable to connect: '.curl_errno($ch).' - '.curl_error($ch), "input", true);
      }else if(empty($response)) {
        $this->log('Empty response', "input", true);
      }
      curl_close($ch);
      $response = json_decode($response, true);
      if(is_null($response)) {
        $this->log('Invalid response format', "input", true);
      }
      if($response['success'] == 0) {
        $this->log($response['message'], "input", true);
      }
      $node = $response['data'][0];
      $ip = $response['data'][1];
      $status = $response['data'][3];
      $os = $response['data'][2];
    }catch(Exception $e) {
      $this->log(
  			$e->getMessage(),
  			"input",
        true
  		);
  		return array();
    }
    return array(
      'node' => $node,
      'ip' => $ip,
      'status' => $status,
      'os' => $os
    );
  }
  public function getClientTabs($package) {
    return array(
      'cplogin' => array(
        'name' => 'Control Panel Login',
        'icon' => 'glyphicon glyphicon-cog'
      ),
      'start' => array(
        'name' => 'Start Server',
        'icon' => 'glyphicon glyphicon-play'
      ),
      'stop' => array(
        'name' => 'Stop Server',
        'icon' => 'glyphicon glyphicon-stop'
      )
    );
  }

  public function cplogin($package, $service, array $get=null, array $post=null, array $files=null) {
    $row = $this->getModuleRow();
    Loader::loadModels($this, ['Clients']);
    $client = $this->Clients->get($service->client_id);
    $this->view = new View("cplogin", "default");
    Loader::loadHelpers($this, array("Form", "Html"));
    $this->view->setDefaultView("components" . DS . "modules" . DS . "proxcp" . DS);
    $this->view->set("hostname", $row->meta->hostname);
    $this->view->set("email", urlencode(base64_encode($client->email)));
    return $this->view->fetch();
  }

  public function start($package, $service, array $get=null, array $post=null, array $files=null) {
    try {
      $row = $this->getModuleRow();
      $type = $package->meta->servicetype;
      $hbid = $service->id;
      $userid = $service->client_id;
      $api_url = 'https://'.$row->meta->hostname.'/api.php';
      $post_info = 'api_id='.$row->meta->user.'&api_key='.$row->meta->password.'&action=startserver&type='.$type.'&hbid='.$hbid.'&userid='.$userid;

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $api_url);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_info);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_USERAGENT, 'ProxCP Blesta Module');
      $response = curl_exec($ch);
      if(curl_error($ch)) {
        $this->log('Unable to connect: '.curl_errno($ch).' - '.curl_error($ch), "input", true);
        return;
      }else if(empty($response)) {
        $this->log('Empty response', "input", true);
        return;
      }
      curl_close($ch);
      $response = json_decode($response, true);
      if(is_null($response)) {
        $this->log('Invalid response format', "input", true);
        return;
      }
      if($response['success'] == 0) {
        $this->log($response['message'], "input", true);
        return;
      }
    }catch(Exception $e) {
      $this->log(
  			$e->getMessage(),
  			"input",
        true
  		);
      return;
    }
    return '<div class="alert alert-success">Success! Your server has been started.</div>';
  }

  public function stop($package, $service, array $get=null, array $post=null, array $files=null) {
    try {
      $row = $this->getModuleRow();
      $type = $package->meta->servicetype;
      $hbid = $service->id;
      $userid = $service->client_id;
      $api_url = 'https://'.$row->meta->hostname.'/api.php';
      $post_info = 'api_id='.$row->meta->user.'&api_key='.$row->meta->password.'&action=stopserver&type='.$type.'&hbid='.$hbid.'&userid='.$userid;

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $api_url);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_info);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_USERAGENT, 'ProxCP Blesta Module');
      $response = curl_exec($ch);
      if(curl_error($ch)) {
        $this->log('Unable to connect: '.curl_errno($ch).' - '.curl_error($ch), "input", true);
        return;
      }else if(empty($response)) {
        $this->log('Empty response', "input", true);
        return;
      }
      curl_close($ch);
      $response = json_decode($response, true);
      if(is_null($response)) {
        $this->log('Invalid response format', "input", true);
        return;
      }
      if($response['success'] == 0) {
        $this->log($response['message'], "input", true);
        return;
      }
    }catch(Exception $e) {
      $this->log(
  			$e->getMessage(),
  			"input",
        true
  		);
      return;
    }
    return '<div class="alert alert-success">Success! Your server has been stopped.</div>';
  }

  private function getNodes($module_row) {
    $post_info = 'api_id='.$module_row->meta->user.'&api_key='.$module_row->meta->password.'&action=getnodes';
    $api_url = 'https://'.$module_row->meta->hostname.'/api.php';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
  	curl_setopt($ch, CURLOPT_POST, 1);
  	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_info);
  	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ProxCP Blesta Module');
    $response = curl_exec($ch);

    if(curl_error($ch)) {
      $this->log('Unable to connect: ' . curl_errno($ch), curl_error($ch), "input", true);
    }else if(empty($response)) {
      $this->log('Empty response', "input", true);
    }
    curl_close($ch);
    $nodeList = json_decode($response, true);
    if(is_null($nodeList) || $nodeList['success'] == 0) {
      $this->log('Invalid response format', "input", true);
    }
    $list = [];
    foreach($nodeList['data'] as $node) {
      $list[$node] = $node;
    }

    return $list;
  }
  private function getRowRules(&$vars) {
    $rules = array(
      'server_name' => array(
        'valid' => array(
          'rule' => 'isEmpty',
          'negate' => true,
          'message' => 'You must enter a Server Label.'
        )
      ),
      'hostname' => array(
        'valid' => array(
          'rule' => 'isEmpty',
          'negate' => true,
          'message' => 'You must enter a Hostname.'
        )
      ),
      'user' => array(
        'valid' => array(
          'rule' => 'isEmpty',
          'negate' => true,
          'message' => 'You must enter a Username.'
        )
      ),
      'password' => array(
        'valid' => array(
          'last' => true,
          'rule' => 'isEmpty',
          'negate' => true,
          'message' => 'You must enter a Password.'
        )
      )
    );
    return $rules;
  }
  private function generatePassword($min_chars = 12, $max_chars = 12) {
    $password = "";
    $chars = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t',
		'u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R',
		'S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9', '!', '@', '#', '$', '%',
		'^', '&', '*', '(', ')');
    $count = count($chars) - 1;
    $num_chars = (int)abs($min_chars == $max_chars ? $min_chars : mt_rand($min_chars, $max_chars));
    for($i = 0; $i < $num_chars; $i++) {
      $password = $chars[mt_rand(0, $count)] . $password;
    }
    return $password;
  }
}
?>
