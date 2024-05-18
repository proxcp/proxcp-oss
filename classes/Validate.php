<?php
class Validate {
	private $_passed = false,
			$_errors = array(),
			$_db = null;

	public function __construct() {
		$this->_db = DB::getInstance();
	}

	public function check($source, $items = array()) {
		foreach($items as $item => $rules) {
			foreach($rules as $rule => $rule_value) {
				$value = trim($source[$item]);
				$item = escape($item);

				if($rule === 'required' && empty($value) && !is_numeric($value)) {
					$this->addError("{$item} is required");
				}else if(!empty($value)) {
					switch($rule) {
						case 'min':
							if(strlen($value) < $rule_value) {
								$this->addError("{$item} must be a minimum of {$rule_value} characters");
							}
						break;
						case 'min-num':
							if($value < $rule_value) {
								$this->addError("{$item} must be a minimum of {$rule_value}");
							}
						break;
						case 'max':
							if(strlen($value) > $rule_value) {
								$this->addError("{$item} must be a maximum of {$rule_value} characters");
							}
						break;
						case 'max-num':
							if($value > $rule_value) {
								$this->addError("{$item} must be a maximum of {$rule_value}");
							}
						break;
						case 'matches':
							if($value != $source[$rule_value]) {
								$this->addError("{$rule_value} must match {$item}");
							}
						break;
						case 'unique':
							$check = $this->_db->get('vncp_users', array($item, '=', $value));
							if($check->count()) {
								$this->addError("{$item} already exists.");
							}
						break;
						case 'unique_domain':
							$check = $this->_db->get('vncp_forward_dns_domain', array($item, '=', $value));
							if($check->count()) {
								$this->addError("{$item} already exists.");
							}
						break;
						case 'unique_hostname':
							$check = $this->_db->get('vncp_nodes', array($item, '=', $value));
							if($check->count()) {
								$this->addError("{$item} already exists.");
							}
						break;
						case 'unique_node':
							$check = $this->_db->get('vncp_tuntap', array('node', '=', $value));
							if($check->count()) {
								$this->addError("Node already exists.");
							}
						break;
						case 'unique_nat':
							$check = $this->_db->get('vncp_nat', array('node', '=', $value));
							if($check->count()) {
								$this->addError("Node already exists.");
							}
						break;
						case 'unique_hbid':
							$check = $this->_db->get('vncp_lxc_ct', array($item, '=', $value));
							$check = $check->all();
							$check2 = $this->_db->get('vncp_kvm_ct', array($item, '=', $value));
							$check2 = $check2->all();
							$check3 = $this->_db->get('vncp_kvm_cloud', array($item, '=', $value));
							$check3 = $check3->all();
							if(count($check) >= 1 || count($check2) >= 1 || count($check3) >= 1) {
								$this->addError("{$item} already exists.");
							}
						break;
						case 'unique_poolid':
							$check = $this->_db->get('vncp_lxc_ct', array('pool_id', '=', $value));
							$check = $check->all();
							$check2 = $this->_db->get('vncp_kvm_ct', array('pool_id', '=', $value));
							$check2 = $check2->all();
							$check3 = $this->_db->get('vncp_kvm_cloud', array('pool_id', '=', $value));
							$check3 = $check3->all();
							if(count($check) >= 1 || count($check2) >= 1 || count($check3) >= 1) {
								$this->addError("{$item} already exists.");
							}
						break;
						case 'valemail':
							if(!filter_var($value, FILTER_VALIDATE_EMAIL)) {
								$this->addError("{$item} must be a valid email address");
							}
						break;
						case 'numonly':
							if(!is_numeric($value)) {
								$this->addError("{$item} is not a valid number. Please use numbers only.");
							}
						break;
						case 'ip':
							if(!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
								$this->addError("{$item} is not a valid IP address");
							}
						break;
						case 'ip6':
							if(!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
								$this->addError("{$item} is not a valid IPv6 address");
							}
						break;
						case 'strbool':
							if($value != 'true' && $value != 'false') {
								$this->addError("{$item} must be true or false.");
							}
						break;
						case 'macaddr':
							if(!preg_match('/^([a-fA-F0-9]{2}:){5}[a-fA-F0-9]{2}$/', $value)) {
								$this->addError("{$item} must be in 00:00:00:00:00:00 MAC address format and use only 0-9, A-F.");
							}
						break;
						case 'cidrformat':
							if(!(strpos($value, '/') !== false)) {
								$this->addError("{$item} must be in CIDR format.");
							}
						break;
					}
				}
			}
		}

		if(empty($this->_errors)) {
			$this->_passed = true;
		}
		return $this;
	}

	private function addError($error) {
		$this->_errors[] = $error;
	}

	public function errors() {
		return $this->_errors;
	}

	public function passed() {
		return $this->_passed;
	}
}
