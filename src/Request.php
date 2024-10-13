<?php

namespace Sonet;


class Request {
	
	public $method;
	public $uri;
	public $user;
	public $params;
	
	public function __construct($user) {
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->uri   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$this->user = $user;
	}

}
