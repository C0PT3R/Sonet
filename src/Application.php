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
		/* Application router is mounted on '/' */
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

		/* Mount system router */
		$this->mount(__DIR__ . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'routes.php', '/system');
	}


	public static function getApp(): Application {
		if (is_null(self::$instance))
			self::$instance = new self;

		return self::$instance;
	}


	/**
	 * Creates the routes defined in $routes_file in the corresponding Router.
	 * If no Router is mounted on $path, one will be created and mounted.
	 * If $path is not specified, routes will be created in the main application router.
	 * @param string $routes_file The path to the routes file.
	 * @param string $path The virtual path that the Router should handle.
	 * @return \Sonet\Application|\Sonet\Router The Router wich has been used.
	 */
	public function mount(string $routes_file, string $path = '/'): Application|Router {
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


	/**
	 * Runs the application. MUST be called at the end of your program.
	 * @return bool
	 */
	public function run(): bool {
		/* Check for a sub-router that matches the requested URI */
		foreach ($this->routers as $router) {
			if ($router->shouldHandle($this->request->uri)) {
				return $router->handle($this->request, $this->response);
			}
		}

		/* If none of the sub-routers matches URI, use main application router. */
		return $this->handle($this->request, $this->response);
	}

}
