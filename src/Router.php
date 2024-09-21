<?php

namespace Sonet;


class Router {
	
	public $root_path;
	
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
	
	
	public function __construct($root_path = '') {
		$this->root_path = trim($root_path, '/');
	}
	
	
	public function get($path, $handler, $required_level = USER_LVL_GUEST) {
		return $this->createRoute('GET', $path, $handler, $required_level);
	}
	
	
	public function post($path, $handler, $required_level = USER_LVL_GUEST) {
		return $this->createRoute('POST', $path, $handler, $required_level);
	}
	
	
	public function put($path, $handler, $required_level = USER_LVL_GUEST) {
		return $this->createRoute('PUT', $path, $handler, $required_level);
	}
	
	
	public function delete($path, $handler, $required_level = USER_LVL_GUEST) {
		return $this->createRoute('DELETE', $path, $handler, $required_level);
	}
	
	
	private function createRoute($method, $path, $handler, $required_level) {
		if (is_array($path)) {
			foreach ($path as $p) {
				$routes[] = $this->createRoute($method, $p, $handler, $required_level);
			}
			return $routes;
		} else {
			$url = Core::normalizePath($this->root_path, $path);
			$route = new Route($url, $handler, $required_level);
			Router::$routes[$method][] = $route;
			return $route;
		}
	}
	
	
	public function on($status, $handler) {
		if (!is_callable($handler))
			trigger_error("Handler is not callable", E_USER_ERROR);
		
		if (is_string($status)) {
			$code = array_search($status, $this->aliases);
			
			if (!$code) {
				$values = implode(', ', $this->aliases);
				trigger_error("Can not set handler for status '$status'. Possible values are: $values", E_USER_ERROR);
			}
			
			$status = $code;
		}
		
		if (!array_key_exists($status, $this->handlers)) {
			$values = implode(', ', array_keys($this->handlers));
			trigger_error("Can not set handler for status '$status'. Possible values are: $values", E_USER_ERROR);
		}
		
		$this->handlers[$status] = $handler;
	}
	
	
	private function get_level_str($route) {
		if (is_array($route->required_level)) {
			foreach ($route->required_level as $level) {
				$levels[] = SONET_USR_LVLS[$level]['title'];
			}
			return implode(', ', $levels) . ' exclusivement';
		}
		else {
			return SONET_USR_LVLS[$route->required_level]['title'] . ' ou supérieur';
		}
	}
	
	
	public function match($request_path) {
		if (empty(trim($this->root_path, '/')))
			return true;
		
		$request_path = explode('/', trim($request_path, '/'));
		$router_path  = explode('/', trim($this->root_path, '/'));
		
		foreach ($router_path as $k=>$v) {
			if ($request_path[$k] != $v) return false;
		}
		
		return true;
	}
	
	
	// TODO: merge with Sonet\Core->call.
	public function callRoute($request, $response) {
		foreach (Router::$routes[$request->method] as $route) {
			if ($route->match($request->path)) {
				/* Set the root path for response */
				$response->setRootPath($this->root_path);

				$route->call($request, $response);
				
				if ($response->status == 200) return true;
				
				http_response_code($response->status);
				
				$response->required_level = $this->get_level_str($route);
				
				if (!is_null($this->handlers[$response->status]))
					call_user_func($this->handlers[$response->status], $request, $response);
				
				return false;
			}
		}
		
		http_response_code(404);
		
		if (!is_null($this->handlers[404]))
			call_user_func($this->handlers[404], $request, $response);
		
		return false;
	}
	
}
