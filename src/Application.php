<?php

namespace Sonet;


final class Application extends Router {

	private static $instance;
	private $database;
	private $user;
	private $request;
	private $response;
	private $routers = [];


	private function __construct() {
		// Application router is mounted on '/'
		parent::__construct('/');

		$db = Config::get()->database;

		try {
			$this->database = new \PDO($db->DSN, $db->user, $db->password);
		} catch (\Exception $e) {
			exit('PDO ERROR: ' . $e->getMessage());
		}

		$this->user = new User($this->database);
		$this->request = new Request($this->user);
		$this->response = new Response();

		$this->mount(__DIR__ . DIRECTORY_SEPARATOR . 'system_routes.php', '/system');
	}


	public static function getApp(): Application {
		if (is_null(self::$instance))
			self::$instance = new self;

		return self::$instance;
	}


	public function mount($routes_file, $path = '/'): Application|Router {
		if ($path == '/') {
			$router = $this;
		} else {
			if (!isset($this->routers[$path]))
				$this->routers[$path] = new Router($path);

			$router = $this->routers[$path];
		}

		require_once $routes_file;

		return $router;
	}


	public function run(): bool {
		foreach ($this->routers as $router) {
			if ($router->match($this->request->uri)) {
				return $router->callRoute($this->request, $this->response);
			}
		}

		// If URI doesn't match any router, call application router.
		return $this->callRoute($this->request, $this->response);
	}

}
