<?php
//////////////////////////////////////////////
//     BEGIN USER CONFIGURATION SECTION     //
//////////////////////////////////////////////

$GLOBALS['config'] = array(
	// DATABASE CONFIGURATION
	'database' => array(
		'type' => 'mysql',
		'host' => '127.0.0.1',
		'username' => 'db.username.default',
		'password' => 'db.password.default',
		'db' => 'vncp'
	),
	'instance' => array(
		'base' => 'http://localhost.localdomain', // BASE DOMAIN OF THIS PROXCP INSTALLATION
		'installed' => false, // HAS PROXCP BEEN INSTALLED?
		'l_salt' => 'default', // DO NOT CHANGE OR SHARE THESE VALUES - SALT 1
		'v_salt' => 'default', // DO NOT CHANGE OR SHARE THESE VALUES - SALT 2
		'vncp_secret_key' => 'default' // DO NOT CHANGE OR SHARE THESE VALUES - SECRET KEY
	),
	'admin' => array(
		'base' => 'admin.base.default' // BASE ADMIN FILE NAME WITHOUT FILE EXTENSION
	),
	// REMEMBER ME LOGIN SETTINGS
	'remember' => array(
		'cookie_name' => 'hash',
		'cookie_expiry' => 604800
	),
	// LOGIN SESSION SETTINGS
	'session' => array(
		'session_name' => 'user',
		'token_name' => 'token'
	)
);

//////////////////////////////////////////////
//      END USER CONFIGURATION SECTION      //
//////////////////////////////////////////////
