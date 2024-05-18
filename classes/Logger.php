<?php
class Logger {
	private $_db;

	public function __construct() {
		$this->_db = DB::getInstance();
	}

	public function log($msg = '', $type = 'general', $severity = 0, $user = '', $ip = '') {
		if($type == 'admin') {
			$fields = array(
				'msg' => $msg,
				'severity' => 0,
				'date' => date('Y-m-d H:i:s'),
				'username' => $user,
				'ipaddress' => $ip
			);
			if(!$this->_db->insert('vncp_log_admin', $fields)) {
				throw new Exception('There was a problem with the logger.');
			}
		}else if($type == 'error') {
			$fields = array(
				'msg' => $msg,
				'severity' => $severity,
				'date' => date('Y-m-d H:i:s'),
				'username' => $user,
				'ipaddress' => $ip
			);
			if(!$this->_db->insert('vncp_log_error', $fields)) {
				throw new Exception('There was a problem with the logger.');
			}
		}else if($type == 'general') {
			$fields = array(
				'msg' => $msg,
				'severity' => 0,
				'date' => date('Y-m-d H:i:s'),
				'username' => $user,
				'ipaddress' => $ip
			);
			if(!$this->_db->insert('vncp_log_general', $fields)) {
				throw new Exception('There was a problem with the logger.');
			}
		}else{
			throw new Exception('Invalid logging type (admin, error, general).');
		}
	}

	public function get($type = 'general') {
		return $this->_db->get('vncp_log_' . $type, array('id', '!=', 0))->all();
	}

	public function purge($type = 'general', $date = '3000-12-31') {
		if($type == 'admin') {
			if(!$this->_db->delete('vncp_log_admin', array('date', '<', $date))) {
				throw new Exception('There was a problem with the logger.');
			}
		}else if($type == 'general') {
			if(!$this->_db->delete('vncp_log_general', array('date', '<', $date))) {
				throw new Exception('There was a problem with the logger.');
			}
		}else if($type == 'error') {
			if(!$this->_db->delete('vncp_log_error', array('date', '<', $date))) {
				throw new Exception('There was a problem with the logger.');
			}
		}else{
			throw new Exception('Invalid logging type (admin, error, general).');
		}
	}
}
