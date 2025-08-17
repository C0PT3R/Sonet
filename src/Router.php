<?php

namespace Sonet;


class Router {
	
	private VirtualPath $rootPath;
	
	private $routes = [
		'GET'    => [],
		'POST'   => [],
		'PUT'    => [],
		'DELETE' => []
	];
	
	private $handlers = [
		403 => null,
		404 => null
	];
	
	private $aliases = [
		403 => 'AccessDenied',
		404 => 'NotFound'
	];
	
	
	/**
	 * Users SHOULD NOT manually create a Router, as it will not be registered, and hence will never be called.
	 * @param string $path
	 */
	public function __construct(string $rootPath) {
		$this->rootPath = new VirtualPath($rootPath);
	}
	
	
	/**
	 * Creates a route in GET mode.
	 * @param string $path A virtual path that the route should handle.
	 * @param callable $handler A callable that accepts Request and Response.
	 * @param array $requirements An array of required Privileges.
	 * @return \Sonet\Route
	 */
	public function get(string $path, callable $handler, array $requirements = []): Route {
		return $this->createRoute('GET', $path, $handler, $requirements);
	}
	
	
	/**
	 * Creates a route in POST mode.
	 * @param string $path A virtual path that the route should respond to.
	 * @param callable $handler A callable that accepts Request and Response.
	 * @param array $requirements An array of required Privileges.
	 * @return \Sonet\Route
	 */
	public function post(string $path, callable $handler, array $requirements = []): Route {
		return $this->createRoute('POST', $path, $handler, $requirements);
	}
	
	
	/**
	 * Creates a route in PUT mode.
	 * @param string $path A virtual path that the route should respond to.
	 * @param callable $handler A callable that accepts Request and Response.
	 * @param array $requirements An array of required Privileges.
	 * @return \Sonet\Route
	 */
	public function put(string $path, callable $handler, array $requirements = []): Route {
		return $this->createRoute('PUT', $path, $handler, $requirements);
	}
	
	
	/**
	 * Creates a route in DELETE mode.
	 * @param string $path A virtual path that the route should respond to.
	 * @param callable $handler A callable that accepts Request and Response.
	 * @param array $requirements An array of required Privileges.
	 * @return \Sonet\Route
	 */
	public function delete(string $path, callable $handler, array $requirements = []): Route {
		return $this->createRoute('DELETE', $path, $handler, $requirements);
	}
	

	/**
	 * Handles creation of routes.
	 * @param string $method
	 * @param string $path
	 * @param callable $handler
	 * @param array $privileges
	 * @return \Sonet\Route
	 */
	private function createRoute(string $method, string $path, callable $handler, array $privileges): Route {
		foreach (VirtualPath::compile($this->rootPath, VirtualPath::secure($path)) as $vp) {
			$route = new Route($vp, $handler, $privileges);
			$this->routes[$method][] = $route;
		}
		
		return $route;
	}
	
	
	/**
	 * Assigns a handler to a status event.
	 * @param int|string $status Can be an HTTP status number or corresponding alias.
	 * @param callable $handler	A callable that accepts Request and Response.
	 * @return void
	 */
	public function on(int|string $status, callable $handler): void {
		if (!is_callable($handler))
			trigger_error("Handler is not callable", E_USER_ERROR);
		
		if (is_string($status)) {
			$code = array_search($status, $this->aliases);
			
			if (!$code) {
				$values = join(', ', $this->aliases);
				trigger_error("Can not set handler for status '$status'. Possible values are: $values", E_USER_ERROR);
			}
			
			$status = $code;
		}
		
		if (!array_key_exists($status, $this->handlers)) {
			$values = join(', ', array_keys($this->handlers));
			trigger_error("Can not set handler for status '$status'. Possible values are: $values", E_USER_ERROR);
		}
		
		$this->handlers[$status] = $handler;
	}


	/**
	 * Checks wether a router should handle a call or not.
	 * @param string $path
	 * @return bool
	 */
	public function shouldHandle(string $path): bool {
		if ($path === '/') return false; // Only main application router can handle root path.

		$path = new VirtualPath($path);
		
		for ($i = 0; $i < count($this->rootPath->segments); $i++) {
			if ($this->rootPath->segments[$i] !== $path->segments[$i]) return false;
		}

		return true;
	}
	
	
	public function handle(Request $request, Response $response): bool {
		foreach ($this->routes[$request->method] as $route) {
			if ($route->match($request->path)) {
				/* Set the root path for response */
				$response->setRootPath($this->rootPath);

				$response->route = $route;

				$route->call($request, $response);
				
				if ($response->status == 200) return true;
				
				http_response_code($response->status);
				
				if (!is_null($this->handlers[$response->status])) {
					$this->handlers[$response->status]($request, $response);
				}

				return false;
			}
		}
		
		http_response_code(404);
		
		// Call 404 handler if it's set
		if (!is_null($this->handlers[404])) {
			$this->handlers[404]($request, $response);
		}
		
		return false;
	}
	
}
