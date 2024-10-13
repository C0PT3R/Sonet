<?php

namespace Sonet;


final class Core extends Router {

	private static $instance;
	private $database;
	private $user;
	private $request;
	private $response;
	private $routers = [];


	private function __construct() {
		parent::__construct('/');

		$dbconf = Config::get()->database;

		try {
			$this->database = new \PDO($dbconf->DSN, $dbconf->user, $dbconf->password);
		} catch (\Exception $e) {
			exit('PDO ERROR: ' . $e->getMessage());
		}

		$this->user = new User($this->database);
		$this->request = new Request($this->user);
		$this->response = new Response();

		$this->mount(__DIR__ . DIRECTORY_SEPARATOR . 'system_routes.php', '/system');
	}


	public static function getApp() {
		if (is_null(self::$instance))
			self::$instance = new self;

		return self::$instance;
	}


	public function mount($routes_file, $target = '/') {
		if ($target == '/') {
			$router = $this;
			$this->directory = $target;
		} else {
			if (!isset($this->routers[$target]))
				$this->routers[$target] = new Router($target);

			$router = $this->routers[$target];
		}

		require_once $routes_file;

		return $router;
	}


	public function run() {
		foreach ($this->routers as $router) {
			if ($router->match($this->request)) {
				return $router->callRoute($this->request, $this->response);
			}
		}

		return $this->callRoute($this->request, $this->response);
	}

}
