<?php

namespace Sonet;


class DataAPIResponse {//implements Cacheable {
	
	private $lifespan;
	
	public $status = '';
	public $errno  = 0;
	public $results;
	
	
	public function __construct($seconds = 0) {
		$this->lifespan = $seconds;
	}
	
	
	public function setLifespan($seconds) {
		$this->lifespan = $seconds;
	}
	
	
	public function getLifespan() {
		return $this->lifespan;
	}
	
	
	public function render() {
		return json_encode($this, JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK);
	}
	
}
