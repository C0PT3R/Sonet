<?php

namespace Sonet;


class Route {

	private Router $router;
	private string $path;
	private $handler;
	public array $requirements;
	private array $params = [];
	
	
	public function __construct(Router $router, string $path, callable $handler, array $requirements) {
		$this->router = $router;
		$this->path = $path;
		$this->handler = $handler;
		$this->requirements = $requirements;
	}
	
	
	public function match($request) {
		return VirtualPath::matchesURI($this->path, $request->uri);
	}
	
	
	private function checkUserPrivilege() {
		$group = (isset($_SESSION["user_group"]) && !empty($_SESSION["user_group"])) ? $_SESSION["user_group"] : "guest";
		return UserGroup::get($group)->hasPrivilege($this->requirements);
	}
	
	
	public function call($request, $response) {
		if ($this->checkUserPrivilege()) {
			$request->params = VirtualPath::parseParams($this->path, $request->uri);
			
			$response->status = 200;

			// Call the route handler
			return call_user_func($this->handler, $request, $response);
		} else {
			$response->status = 403;
			return false;
		}
	}
	
}
