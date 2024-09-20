<?php

namespace Sonet;


class Request {
	
	public $method;
	public $path;
	public $user;
	public $params;
	
	public function __construct($user) {
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$this->params = new \stdClass();
		$this->user = $user;
	}

}
