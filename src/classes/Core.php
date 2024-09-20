<?php

namespace Sonet;


class Core extends Router {

	private static $instance;

	private $database;
	private $user;
	private $request;
	private $response;
	private $routers;


	private function __construct() {
		parent::__construct();

		try {
			$this->database = new \PDO(DB_DSN, DB_USER, DB_PASSWORD);
		} catch (\Exception $e) {
			exit('PDO ERROR: ' . $e->getMessage());
		}

		$this->user = new User($this->database);
		$this->request = new Request($this->user);
		$this->response = new Response();

		$this->mount(SONET_DIR . '/routes/system.php', '/system');
	}


	public static function getApp() {
		if (is_null(self::$instance))
			self::$instance = new self;

		return self::$instance;
	}


	public static function normalizePath(...$paths) {
		$results = [];

		foreach ($paths as $path) {
			foreach (explode('/', $path) as $value) {
				if (!empty($value))
					$results[] = $value;
			}
		}

		return "/" . join("/", $results);
	}


	public function mount($routes_file, $target = '') {
		$target = trim($target, '/');

		if (empty($target)) {
			$router = $this;
		} else {
			if (!isset($this->routers[$target]))
				$this->routers[$target] = new Router($target);

			$router = $this->routers[$target];
		}

		require_once $routes_file;

		return $router;
	}


	public function run()
	{
		foreach ($this->routers as $router) {
			if ($router->match($this->request->path)) {
				return $router->callRoute($this->request, $this->response);
			}
		}

		return $this->callRoute($this->request, $this->response);
	}

}
