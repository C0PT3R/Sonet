<?php

namespace Sonet;


/* Cache configuration */
define('CACHE_DIR', 'cache/sonet');
define('CACHE_KEY', 'd12414bc18e5e8e4e922ad7e');
define('CACHE_CLEANUP_INTERVAL', 24);


class Config {

	private static $instance;
	public $database;
	public $login_errors;
	public $fieldnames;

	private function __construct() {
		$this->database = new \stdClass();
		$this->login_errors = new \stdClass();
		$this->fieldnames = new \stdClass();
		
		/* Read user config file */
		$config_file_path = getcwd() . DIRECTORY_SEPARATOR . "sonet.json";
		$config = json_decode(file_get_contents($config_file_path));

		$this->database->host = $config->database->host ?? "localhost";
		$this->database->port = $config->database->port ?? 3306;
		$this->database->user = $config->database->user ??"root";
		$this->database->password = $config->database->password ?? "";
		$this->database->name = $config->database->name ?? "sonet";
		$this->database->DSN = "mysql:host=" . $this->database->host . ";port=" . $this->database->port . ";dbname=" . $this->database->name . ";charset=utf8";

		$this->login_errors->username_missing = $config->login_errors->username_missing ?? "Please enter user name.";
		$this->login_errors->password_missing = $config->login_errors->password_missing ?? "Please enter user password.";
		$this->login_errors->no_such_user = $config->login_errors->no_such_user ?? "User doesn't exist.";
		$this->login_errors->wrong_password = $config->login_errors->wrong_password ?? "Wrong password.";

		$this->fieldnames->login_username = $config->fieldnames->login_username ?? "username";
		$this->fieldnames->login_password = $config->fieldnames->login_password ?? "password";
		$this->fieldnames->login_persistent = $config->fieldnames->login_persistent ?? "persistent";

		/* Create user groups */
		UserGroup::create("guest");
		UserGroup::create("administrateur");
		UserGroup::create("VIP");
		UserGroup::create("basique");
		
		/* Create user privileges */
		Privilege::create("ADMIN_ACCESS");
		Privilege::create("VIP_ACCESS");
		Privilege::create("BASIC_ACCESS");
		Privilege::create("CREATE_USER");
		Privilege::create("MODIFY_USER");
		Privilege::create("DELETE_USER");
		
		/* Assign user privileges */
		UserGroup::get("administrateur")->grantAll();
		UserGroup::get("VIP")->grant("VIP_ACCESS", "BASIC_ACCESS");
		UserGroup::get("basique")->grant("BASIC_ACCESS");
	}

	public static function get(): Config {
		if (!isset(self::$instance)) {
			self::$instance = new Config();
		}
		return self::$instance;
	}

}