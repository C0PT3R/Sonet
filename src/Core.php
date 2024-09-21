<?php

namespace Sonet;

//require_once getcwd() . DIRECTORY_SEPARATOR . "sonet_config.php";

$config_file_path = getcwd() . DIRECTORY_SEPARATOR . "sonet.json";
$config = json_decode(file_get_contents($config_file_path));

define("DB_HOST", $config->database->host);
define("DB_PORT", $config->database->port);
define("DB_USER", $config->database->user);
define("DB_PASS", $config->database->password);
define("DB_NAME", $config->database->name);

foreach ($config->{"user-levels"} as $lvl) {
	define($lvl->name, $lvl->level);
}

/* Define user levels */
const SONET_USR_LVLS = [
	0 => [
		'const' => 'USER_LVL_GUEST',
		'title' => 'invité'
	],
	1 => [
		'const' => 'USER_LVL_BASIC',
		'title' => 'basique'
	],
	5 => [
		'const' => 'USER_LVL_VIP',
		'title' => 'VIP'
	],
	10 => [
		'const' => 'USER_LVL_ADMIN',
		'title' => 'administrateur'
	]
];


require_once "default.php";


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
			$this->database = new \PDO(DB_DSN, DB_USER, DB_PASS);
		} catch (\Exception $e) {
			exit('PDO ERROR: ' . $e->getMessage());
		}

		$this->user = new User($this->database);
		$this->request = new Request($this->user);
		$this->response = new Response();

		$this->mount(__DIR__ . '\\system_routes.php', '/system');
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
