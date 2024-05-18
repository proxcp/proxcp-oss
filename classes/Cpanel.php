<?php
class Cpanel {
	private $_host, $_username, $_password;
  private $_url;

	public function __construct($host, $username, $password) {
		$this->_host = $host;
    $this->_username = $username;
    $this->_password = $password;
    $this->_url = $host . '/json-api/';
	}

	public function addzonerecord($zone, $name, $type, $ptrdname) {
    $query = $this->_url . "addzonerecord?api.version=1";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $header[0] = "Authorization: whm ".$this->_username.":".$this->_password;
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_URL, $query);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
      'zone' => $zone,
      'name' => $name,
      'type' => $type,
      'ptrdname' => $ptrdname
    )));
    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_status != 200) {
        return false;
    }else{
        $json = json_decode($result);
        return $json;
    }
    curl_close($curl);
  }

  public function dumpzone($domain) {
    $query = $this->_url . "dumpzone?api.version=1";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $header[0] = "Authorization: whm ".$this->_username.":".$this->_password;
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_URL, $query);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
      'domain' => $domain
    )));
    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_status != 200) {
        return false;
    }else{
        $json = json_decode($result);
        return $json;
    }
    curl_close($curl);
  }

  public function removezonerecord($zone, $line) {
    $query = $this->_url . "removezonerecord?api.version=1";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $header[0] = "Authorization: whm ".$this->_username.":".$this->_password;
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_URL, $query);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
      'zone' => $zone,
      'line' => (int)$line
    )));
    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_status != 200) {
        return false;
    }else{
        $json = json_decode($result);
        return $json;
    }
    curl_close($curl);
  }

	public function adddns($domain, $ip) {
    $query = $this->_url . "adddns?api.version=1";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $header[0] = "Authorization: whm ".$this->_username.":".$this->_password;
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_URL, $query);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
      'domain' => $domain,
      'ip' => $ip
    )));
    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_status != 200) {
        return false;
    }else{
        $json = json_decode($result);
        return $json;
    }
    curl_close($curl);
  }

	public function killdns($domain) {
    $query = $this->_url . "killdns?api.version=1";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $header[0] = "Authorization: whm ".$this->_username.":".$this->_password;
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_URL, $query);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
      'domain' => $domain
    )));
    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_status != 200) {
        return false;
    }else{
        $json = json_decode($result);
        return $json;
    }
    curl_close($curl);
  }

	public function addzonerecord_A($domain, $name, $class, $ttl, $type, $address) {
    $query = $this->_url . "addzonerecord?api.version=1";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $header[0] = "Authorization: whm ".$this->_username.":".$this->_password;
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_URL, $query);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
      'domain' => $domain,
      'name' => $name,
      'class' => $class,
      'ttl' => $ttl,
			'type' => $type,
			'address' => $address
    )));
    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_status != 200) {
        return false;
    }else{
        $json = json_decode($result);
        return $json;
    }
    curl_close($curl);
  }

	public function addzonerecord_CNAME($domain, $name, $class, $ttl, $type, $cname, $flatten) {
    $query = $this->_url . "addzonerecord?api.version=1";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $header[0] = "Authorization: whm ".$this->_username.":".$this->_password;
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_URL, $query);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
      'domain' => $domain,
      'name' => $name,
      'class' => $class,
      'ttl' => $ttl,
			'type' => $type,
			'cname' => $cname,
			'flatten' => $flatten
    )));
    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_status != 200) {
        return false;
    }else{
        $json = json_decode($result);
        return $json;
    }
    curl_close($curl);
  }

	public function addzonerecord_SRV($domain, $name, $class, $ttl, $type, $priority, $weight, $port, $target) {
    $query = $this->_url . "addzonerecord?api.version=1";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $header[0] = "Authorization: whm ".$this->_username.":".$this->_password;
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_URL, $query);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
      'domain' => $domain,
      'name' => $name,
      'class' => $class,
      'ttl' => $ttl,
			'type' => $type,
			'priority' => $priority,
			'weight' => $weight,
			'port' => $port,
			'target' => $target
    )));
    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_status != 200) {
        return false;
    }else{
        $json = json_decode($result);
        return $json;
    }
    curl_close($curl);
  }

	public function addzonerecord_MX($domain, $name, $class, $ttl, $type, $preference, $exchange) {
    $query = $this->_url . "addzonerecord?api.version=1";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $header[0] = "Authorization: whm ".$this->_username.":".$this->_password;
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_URL, $query);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
      'domain' => $domain,
      'name' => $name,
      'class' => $class,
      'ttl' => $ttl,
			'type' => $type,
			'preference' => $preference,
			'exchange' => $exchange
    )));
    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_status != 200) {
        return false;
    }else{
        $json = json_decode($result);
        return $json;
    }
    curl_close($curl);
  }

	public function addzonerecord_TXT($domain, $name, $class, $ttl, $type, $txtdata, $unencoded) {
    $query = $this->_url . "addzonerecord?api.version=1";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $header[0] = "Authorization: whm ".$this->_username.":".$this->_password;
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_URL, $query);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
      'domain' => $domain,
      'name' => $name,
      'class' => $class,
      'ttl' => $ttl,
			'type' => $type,
			'txtdata' => $txtdata,
			'unencoded' => $unencoded
    )));
    $result = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($http_status != 200) {
        return false;
    }else{
        $json = json_decode($result);
        return $json;
    }
    curl_close($curl);
  }
}
