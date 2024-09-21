<?php

namespace Sonet;


class User {
	
	private $database;
	private $messages = [];
	
	private $handlers = [
		'LoginSuccess' => null,
		'LoginError'   => null
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
		
		if (!isset($_SESSION['username'])) {
			if (isset($_COOKIE['Auth'])) {
				if (!$this->loginFromCookie()) $this->connect($GLOBALS['Sonet_default_user']);
			}
			else $this->connect($GLOBALS['Sonet_default_user']);
		}
	}
	
	
	public function getTitle($level = '') {
		if (empty($level)) $level = $_SESSION['user_level'];
		return SONET_USR_LVLS[$level]['title'];
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
		}
		else {
			$_SESSION['flash-' . $name] = $message;
			$this->messages[$name] = $message;
			return true;
		}
	}
	
	
	private function connect($user, $persistant = false) {
		if ($persistant) {
			$auth = $user['username'] . '\\\\--' . password_hash($this->getAuthString($user), PASSWORD_BCRYPT);
			$expire = time() + 30 * 86400;
			setcookie('Auth', $auth, $expire, '/', null, true, true);
		}
		
		$_SESSION['username']   = $user['username'];
		$_SESSION['user_level'] = $user['level'];
		$_SESSION['user_title'] = $this->getTitle();
	}
	
	
	public function on($status, $callback) {
		$keys = array_keys($this->handlers);
		
		if (in_array($status, $keys))
			$this->handlers[$status] = $callback;
		else {
			$values = implode(', ', $keys);
			trigger_error("Can not set callback for user status '$status'. Possible values are: $values", E_USER_ERROR);
		}
	}
	
	
	public function login() {
		$username   = trim($_POST[LOGIN_FIELD_USERNAME]);
		$password   = trim($_POST[LOGIN_FIELD_PASSWORD]);
		$persistant = isset($_POST[LOGIN_FIELD_PERSISTANT]) ? true : false;
	
		if (empty($username)) {
			if (is_callable($this->handlers['LoginError']))
				return call_user_func($this->handlers['LoginError'], $this, 'Veuillez entrer votre nom d\'utilisateur.');
		}
		if (empty($password)) {
			if (is_callable($this->handlers['LoginError']))
				return call_user_func($this->handlers['LoginError'], $this, 'Veuillez entrer votre mot de passe.');
		}
		
		$sql = "SELECT * FROM users WHERE username = '$username'";
		$result = $this->database->query($sql);
		
		if ($result->rowCount() !== 1) {
			if (is_callable($this->handlers['LoginError']))
				return call_user_func($this->handlers['LoginError'], $this, 'Il n\'y a pas de compte avec ce nom d\'utilisateur.');
		}

		$user = $result->fetch();
		
		if (!password_verify($password, $user['password'])) {
			if (is_callable($this->handlers['LoginError']))
				return call_user_func($this->handlers['LoginError'], $this, 'Le mot de passe ne correspond pas.');
		}

		$this->connect($user, $persistant);
		
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
		$user = $result->fetch();
		
		if (password_verify($this->getAuthString($user), $auth_key)) {
			$this->connect($user, true);
			return true;
		}
		
		return false;
	}
	
	
	public function logout() {
		if (isset($_COOKIE['Auth']))
			setcookie('Auth', '', time() - 3600, '/', null, true, true);
		
		$_SESSION = array();
		session_destroy();
	}
	
}
