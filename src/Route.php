<?php

namespace Sonet;


class Route {

	private VirtualPath $path;
	private $handler;
	public array $requirements;
	private array $params = [];
	
	
	public function __construct(string $path, callable $handler, array $requirements) {
		$this->path = new VirtualPath($path);
		$this->handler = $handler;
		$this->requirements = $requirements;
	}
	
	
	public function match(string $uri): bool {
		return $this->path->matches($uri);
	}
	
	
	private function checkUserPrivilege() {
		$group = (isset($_SESSION["user_group"]) && !empty($_SESSION["user_group"])) ? $_SESSION["user_group"] : "guest";
		return UserGroup::get($group)->hasPrivilege($this->requirements);
	}
	
	
	public function call($request, $response) {
		if ($this->checkUserPrivilege()) {
			$request->params = $this->path->parse($request->uri);
			
			$response->status = 200;

			// Call the route handler
			return call_user_func($this->handler, $request, $response);
		} else {
			$response->status = 403;
			return false;
		}
	}
	
}
