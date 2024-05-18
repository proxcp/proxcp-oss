<?php
if(count(get_included_files()) == 1) die("You just broke everything.");
use org\magiclen\magiccrypt\MagicCrypt;

function escape($string) {
	return htmlentities($string, ENT_QUOTES, 'UTF-8');
}

function getRandomString($length) {
	$chars = 'abcdefghijlkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&';
	$random_string = '';
	$num_valid_chars = strlen($chars);

	for($i = 0; $i < $length; $i++) {
		$random_pick = mt_rand(1, $num_valid_chars);
		$random_char = $chars[$random_pick-1];
		$random_string .= $random_char;
	}

	return $random_string;
}

function getRandNum($min, $max) {
	return rand($min, $max);
}

function read_bytes($bytes, $decimals = 2) {
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes/pow(1024, $factor));
}

function read_bytes_size($bytes, $decimals = 2) {
	$size = array('B', 'KB', 'MB', 'GB', 'TB');
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes/pow(1024, $factor)).@$size[$factor];
}

function toMB($bytes) {
	return number_format($bytes / 1048576, 0, '', '');
}

function read_time($seconds) {
    try {
    	$dtF = new DateTime("@0");
    	$dtT = new DateTime("@$seconds");
    }catch(Exception $e) {
        return '0 seconds';
    }
	return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes, and %s seconds');
}

function news_render($db) {
	$news = $db->get('vncp_settings', array('item', '=', 'panel_news'))->first()->value;
	return $news;
}

function getVersion() {
	$version = "1.7";
	$vhash = "DB0F3CA79FC89DF894FB402916630A3E";
	return array($version, strtolower($vhash));
}

function debug($val) {
	echo '<pre>';
	print_r($val);
	echo '</pre>';
}

function loadEncryptionKeyFromConfig() {
	$load = Config::get('instance/vncp_secret_key');
	$key = explode('.', $load)[0];
	$iv = explode('.', $load)[1];
	return array($key, $iv);
}

function encryptValue($string) {
	$load = loadEncryptionKeyFromConfig();
	$key = $load[0];
	$iv = $load[1];
	$mc = new MagicCrypt($key, 256, $iv);
	return $mc->encrypt($string);
}

function decryptValue($ciphertext) {
	$load = loadEncryptionKeyFromConfig();
	$key = $load[0];
	$iv = $load[1];
	$mc = new MagicCrypt($key, 256, $iv);
	return $mc->decrypt($ciphertext);
}

function gethost(){
    if(isset($_SERVER['HTTPS'])){
        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
    }
    else{
        $protocol = 'http';
    }
    return $protocol . "://" . $_SERVER['HTTP_HOST'];
}

function gethttps() {
	if(isset($_SERVER['HTTPS'])) {
		return ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? true : false;
	}
}

function cidrToRange($cidr) {
	$range = array();
	$cidr = explode('/', $cidr);
	$range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
	$range[1] = long2ip((ip2long($range[0])) + pow(2, (32 - (int)$cidr[1])) - 1);
	return $range;
}

function severity($num) {
	if($num == 0) {
		return 'info';
	}else if($num == 1) {
		return 'warning';
	}else{
		return 'fatal';
	}
}

function Whmcs_Api($url, $postfields) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
	$response = curl_exec($ch);
	if (curl_error($ch)) {
	    return false;
	}
	curl_close($ch);
	$jsonData = json_decode($response, true);
	return $jsonData;
}

function IPInRange($ip, $range) {
	if(strpos($range, '/') == false) {
		$range .= '/24';
	}
	list($range, $netmask) = explode('/', $range, 2);
	$range_decimal = ip2long($range);
	$ip_decimal = ip2long($ip);
	$wildcard_decimal = pow(2, (32 - $netmask)) - 1;
	$netmask_decimal = ~ $wildcard_decimal;
	return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}

function netmaskToCIDR($netmask) {
	$dict = array(
		'255.255.255.255' => 32,
		'255.255.255.254' => 31,
		'255.255.255.252' => 30,
		'255.255.255.248' => 29,
		'255.255.255.240' => 28,
		'255.255.255.224' => 27,
		'255.255.255.192' => 26,
		'255.255.255.128' => 25,
		'255.255.255.0' => 24,
		'255.255.254.0' => 23,
		'255.255.252.0' => 22,
		'255.255.248.0' => 21,
		'255.255.240.0' => 20,
		'255.255.224.0' => 19,
		'255.255.192.0' => 18,
		'255.255.128.0' => 17,
		'255.255.0.0' => 16,
		'255.254.0.0' => 15,
		'255.252.0.0' => 14,
		'255.248.0.0' => 13,
		'255.240.0.0' => 12,
		'255.224.0.0' => 11,
		'255.192.0.0' => 10,
		'255.128.0.0' => 9,
		'255.0.0.0' => 8
	);
	if(array_key_exists($netmask, $dict)) {
		return $dict[$netmask];
	}else{
		return 24;
	}
}
