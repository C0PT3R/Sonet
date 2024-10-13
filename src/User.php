<?php

namespace Sonet;


class User {
	
	private $database;
	private $messages = [];
	
	private $handlers = [
		'LoginSuccess' => "",
		'LoginError'   => ""
	];
	
	
	public function __construct($database) {
		session_start();
		
		$this->database = $database;
		
		// Load flash messages
		foreach ($_SESSION as $k=>$v) {
			if (substr($k, 0, 6) === 'flash-') {
				$this->messages[substr($k, 6)] = $v;
				unset($_SESSION[$k]);
			}
		}
	}
	
	
	private function getAuthString($user) {
		return $user['username'] . $user['password'] . $_SERVER['REMOTE_ADDR'];
	}
	
	
	public function flash($name, $message = null) {
		if (is_null($message)) {
			if (isset($this->messages[$name]))
				return $this->messages[$name];
			else
				return null;
		} else {
			$_SESSION['flash-' . $name] = $message;
			$this->messages[$name] = $message;
			return true;
		}
	}
	
	
	private function connect($db_user, $persistant = false) {
		if ($persistant) {
			$auth = $db_user['username'] . '\\\\--' . password_hash($this->getAuthString($db_user), PASSWORD_BCRYPT);
			$expire = time() + 30 * 86400;
			setcookie('Auth', $auth, $expire, '/', "", true, true);
		}
		
		$_SESSION['username'] = $db_user['username'];
		$_SESSION['user_group'] = $db_user['group'];
	}
	
	
	public function on($status, callable $callback) {
		$keys = array_keys($this->handlers);
		
		if (in_array($status, $keys)) {
			$this->handlers[$status] = $callback;
		} else {
			$values = join(', ', $keys);
			trigger_error("Can not set callback for user status '$status'. Possible values are: $values", E_USER_ERROR);
		}
	}
	
	
	public function login() {
		$fields = Config::get()->fieldnames;
		$login_errors = Config::get()->login_errors;

		$username = trim($_POST[$fields->login_username]);
		$password = trim($_POST[$fields->login_password]);
		$persistant = isset($_POST[$fields->login_persistent]) ? true : false;
	
		if (empty($username)) {
			if (is_callable($this->handlers['LoginError']))
				return call_user_func($this->handlers['LoginError'], $this, $login_errors->username_missing);
		}
		if (empty($password)) {
			if (is_callable($this->handlers['LoginError']))
				return call_user_func($this->handlers['LoginError'], $this, $login_errors->password_missing);
		}
		
		$sql = "SELECT * FROM users WHERE username = '$username'";
		$result = $this->database->query($sql);
		
		if ($result->rowCount() !== 1) {
			if (is_callable($this->handlers['LoginError']))
				return call_user_func($this->handlers['LoginError'], $this, $login_errors->no_such_user);
		}

		$db_user = $result->fetch();
		
		if (!password_verify($password, $db_user['password'])) {
			if (is_callable($this->handlers['LoginError']))
				return call_user_func($this->handlers['LoginError'], $this, $login_errors->wrong_password);
		}

		$this->connect($db_user, $persistant);
		
		if (is_callable($this->handlers['LoginSuccess']))
			call_user_func($this->handlers['LoginSuccess']);
		
		return true;
	}
	
	
	private function loginFromCookie() {
		$key = explode('\\\\--', $_COOKIE['Auth']);
		$username = $key[0];
		$auth_key = $key[1];
		
		$sql = "SELECT * FROM users WHERE username = '$username'";
		$result = $this->database->query($sql);
		$db_user = $result->fetch();
		
		if (password_verify($this->getAuthString($db_user), $auth_key)) {
			$this->connect($db_user, true);
			return true;
		}
		
		return false;
	}
	
	
	public function logout() {
		if (isset($_COOKIE['Auth']))
			setcookie('Auth', '', 0, '/', "", true, true);
		
		$_SESSION = [];
		session_destroy();
	}
	
}
