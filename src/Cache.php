<?php

namespace Sonet;



class Cache {
	
	private $renderer;
	private $period;
	private $content = '';
	private $location;
	private $timelapse;
	private $cleanup_file;
	private $cleanup_timelapse;
	private $last_cleanup;
	
	public function __construct($renderer) {
		if (!$renderer instanceof Cacheable)
			trigger_error("Renderer must implement 'Sonet\Cacheable'.", E_USER_ERROR);
		
		$this->renderer          = $renderer;
		$this->period            = round($renderer->getPeriod());
		$this->cleanup_file      = CACHE_DIR . DIRECTORY_SEPARATOR . 'last_cleaned';
		$this->cleanup_timelapse = new Timelapse(round(CACHE_CLEANUP_INTERVAL) * 3600);
		
		if ($this->period > 0) {
			$this->timelapse = new Timelapse($this->period);
			$this->location  = $this->getCacheFileLocation();
		}
		
		$this->cleanupRoutine();
		$this->populate();
	}
	
	private function getCacheFileLocation() {
		$filename = sha1(CACHE_KEY . $_SERVER['REQUEST_URI'] . $this->timelapse->start);
		return CACHE_DIR . DIRECTORY_SEPARATOR . $filename;
	}
	
	private function populate() {
		if ($this->period > 0) {
			if (file_exists($this->location)) {
				$this->content = file_get_contents($this->location);
			}
			else {
				$content = $this->renderer->render();
				file_put_contents($this->location, $content);
				$this->removeOlderVersions();
				$this->content = $content;
			}
		}
		else {
			$this->content = $this->renderer->render();
		}
	}
	
	private function removeOlderVersions() {
		$iterations = ceil((time() - $this->last_cleanup) / $this->period);
		
		for ($i=0; $i<$iterations; $i++) {
			$this->timelapse->toPrevious();
			$file = $this->getCacheFileLocation();
			
			if (file_exists($file)) unlink($file);
		}
	}
	
	private function cleanupRoutine() {
		$this->last_cleanup = $this->getLastCleanup();
		
		if (false === $this->last_cleanup)
			$this->reset();
			
		else if ($this->last_cleanup < $this->cleanup_timelapse->start)
			$this->reset();
	}
	
	private function getLastCleanup() {
		if (file_exists($this->cleanup_file))
			return file_get_contents($this->cleanup_file);
		else
			return false;
	}
	
	private function reset() {
		$time = time();
		$dir_handle = opendir(CACHE_DIR);
		
		while (false !== ($file = readdir($dir_handle))) {
			if ($file != '.' && $file != '..')
				unlink(CACHE_DIR . DIRECTORY_SEPARATOR . $file);
		}
		
		file_put_contents($this->cleanup_file, $time);
		$this->last_cleanup = $time;
	}
	
	public function __toString() {
		return $this->content;
	}
	
}


class Timelapse {
	
	public $start;
	public $finish;
	public $duration;
	
	public function __construct($duration, $timebase = null) {
		if (is_null($timebase)) $timebase = time();
		
		$this->start    = floor($timebase / $duration) * $duration;
		$this->finish   = $this->start + $duration - 1;
		$this->duration = $duration;
	}
	
	public function toPrevious() {
		$this->start  -= $this->duration;
		$this->finish -= $this->duration;
	}
	
	public function toNext() {
		$this->start  += $this->duration;
		$this->finish += $this->duration;
	}
	
	public function __toString() {
		return 'From ' . date('r', $this->start) . ' to ' . date('r', $this->finish);
	}
	
}
