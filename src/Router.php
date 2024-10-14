<?php

namespace Sonet;


class Router {
	
	public VirtualPath $path;
	
	private static $routes = [
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
	
	
	public function __construct(string $path) {
		$this->path = new VirtualPath($path);
	}
	
	
	public function get(string $path, callable $handler, array $requirements = []): Route {
		return $this->createRoute('GET', $path, $handler, $requirements);
	}
	
	
	public function post(string $path, callable $handler, array $requirements = []): Route {
		return $this->createRoute('POST', $path, $handler, $requirements);
	}
	
	
	public function put(string $path, callable $handler, array $requirements = []): Route {
		return $this->createRoute('PUT', $path, $handler, $requirements);
	}
	
	
	public function delete(string $path, callable $handler, array $requirements = []): Route {
		return $this->createRoute('DELETE', $path, $handler, $requirements);
	}
	
	
	private function createRoute(string $method, string $path, callable $handler, array $privileges): Route {
		$v_paths = VirtualPath::compile($this->path, $path);

		foreach ($v_paths as $vp) {
			$route = new Route($vp, $handler, $privileges);
			self::$routes[$method][] = $route;
		}
		
		return $route;
	}
	
	
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
	
	
	public function match(string $uri): bool {
		return $this->path->contains($uri);
	}
	
	
	public function callRoute(Request $request, Response $response): bool {
		foreach (self::$routes[$request->method] as $route) {
			if ($route->match($request)) {
				/* Set the root path for response */
				$response->setPath($this->path);

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
