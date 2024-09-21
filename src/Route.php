<?php

namespace Sonet;


class Route {
	
	private $path;
	private $handler;
	public  $required_level;
	private $params = [];
	
	
	public function __construct($path, $handler, $required_level) {
		$this->path = $path;
		$this->handler = $handler;
		$this->required_level = $required_level;
	}
	
	
	private function getParamNames() {
		preg_match_all('#:([\w\-\_]+)#', $this->path, $params);
		return $params[1];
	}
	
	
	public function match($url) {
		$pattern = '#^' . preg_replace('#:([\w\-\_]+)#', '([^/]+)', trim($this->path, '/')) . '$#i';
			
		if (!preg_match($pattern, trim($url, '/'), $params)) return false;
		
		array_shift($params);
		$this->params = $params;
		return true;
	}
	
	
	private function checkUserLevel() {
		$user_level = $_SESSION['user_level'];
		
		if (is_array($this->required_level))
			return in_array($user_level, $this->required_level);
		else
			return ($user_level >= $this->required_level) ? true : false;
	}
	
	
	public function call($request, $response) {
		if ($this->checkUserLevel()) {
			$pnames = $this->getParamNames();
			
			for ($i = 0; $i < count($this->params); $i++) {
				$request->params->{$pnames[$i]} = $this->params[$i];
			}
			
			$response->status = 200;

			// Call the route handler
			return call_user_func($this->handler, $request, $response);
		} else {
			$response->status = 403;
			return false;
		}
	}
	
}
