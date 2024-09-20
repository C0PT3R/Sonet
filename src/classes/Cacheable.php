<?php

namespace Sonet;


interface Cacheable {
	
	/**
	 * Set cache period in seconds
	 */
	public function setPeriod($seconds);
	
	
	/**
	 * Get cache period
	 * MUST return integer
	 */
	public function getPeriod();
	
	
	/**
	 * The result of this function is what will be put in cache
	 * MUST return string
	 */
	public function render();
	
}
