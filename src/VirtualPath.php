<?php

namespace Sonet;


const PATH_REGULAR = "([\w\-_]*)";
const PATH_REQUIRED = "!([A-z_][\w_]*)";
const PATH_OPTIONAL = "\?([A-z_][\w_]*)";
const VALID_PATH = "#^((\/)|(((\/" . PATH_REGULAR . ")|(\/" . PATH_REQUIRED . "))+((\/" . PATH_OPTIONAL . ")|(\/" . PATH_REGULAR . "))*))$#";


final class VirtualPath {

	public array $segments = [];
	public bool $absolute = false;


	public function __construct(string ...$paths) {
		foreach ($paths as $path) {
			if (!empty($path)) {
				// If first character of any path is a '/' then it's an absolute path.
				// Any new absolute path overrides the previous path.
				if ($path[0] === "/") {
					$this->absolute = true;
					$this->segments = [];
				}

				foreach (explode('/', $path) as $segment) {
					if (!empty($segment) && $segment != '.') {
						$this->segments[] = $segment;
					}
				}
			}
		}
	}


	public function contains(string $haystack): bool {
		$haystack = new self($haystack);

		if (!isset($haystack->segments[0]) || !isset($this->segments[0])) return true;
		
		for ($i = 0; $i < count($this->segments); $i++) {
			if ($haystack->segments[$i] !== $this->segments[$i]) return false;
		}

		return true;
	}


	public function matches(string $uri): bool {
		$pattern = preg_replace("#" . PATH_REQUIRED . "#", '[^/]+', trim($this, '/'));
		return preg_match("#^$pattern$#", trim($uri, '/')) ? true : false;
	}


	public function resolve(): string {
		// Resolve parent directories
		for ($i = 0; $i < count($this->segments); $i++) {
			if ($this->segments[$i] == '..') {
				if ($i - 1 >= 0) {
					if ($this->segments[$i-1] == '..') continue;
					array_splice($this->segments, $i-1, 2);
					$i -= 2;
				}
			}
		}

		// Remove all parents at the start of an absolute path
		if ($this->absolute) {
			while (array_key_exists(0, $this->segments) && $this->segments[0] == '..') {
				array_splice($this->segments, 0, 1);
			}
		}

		return $this->normalize();
	}


	public function normalize(): string {
		$str = $this->absolute ? '/' : '';
		$str .= join("/", $this->segments);
		return $str;
	}

	/**
	 * Extracts params by comparing to URI
	 * @param string $uri
	 * @return \stdClass|null
	 */
	public function parse(string $uri): \stdClass|null {
		$params = new \stdClass();

		// Get params names
		if (!preg_match_all("#" . PATH_REQUIRED . "#", $this, $pnames)) return null;
		$pnames = $pnames[1];

		// Get params values
		$pattern = preg_replace("#" . PATH_REQUIRED . "#", '([^/]+)', trim($this, '/'));
		if (!preg_match("#^$pattern$#", trim($uri, '/'), $pvals)) return null;
		array_shift($pvals); // Remove the first item as it's not relevant

		// Assign params
		for ($i = 0; $i < count($pvals); $i++) {
			$params->{$pnames[$i]} = $pvals[$i];
		}

		return $params;
	}

	public function __toString(): string {
		return $this->normalize();
	}

	public static function compile(string ...$paths): array {
		$struct = new self(...$paths);

		/* Manage aliased path segments, if present */
		if (str_contains($struct, '|')) {
			$results = [];

			for ($i = 0; $i < count($struct->segments); $i++) {
				if (str_contains($struct->segments[$i], '|')) {
					$aliases = explode('|', $struct->segments[$i]);

					foreach ($aliases as $alias) {
						$ap = new self();
						$ap->absolute = $struct->absolute;
						for ($j = 0; $j < count($struct->segments); $j++) {
							$ap->segments[] = ($i === $j) ? $alias : $struct->segments[$j];
						}
						$results = array_merge($results, self::compile($ap));
					}
				}
			}

			return $results;
		}

		/* Manage optional path parameters, if present */
		$opt_pos = [];

		// Get positions of optional segments
		foreach ($struct->segments as $k => $segment) {
			if ($segment[0] == '?') {
				$opt_pos[] = $k;
			}
		}

		// Create optional paths
		$pos = 0;
		foreach ($opt_pos as $v) {
			$s = [];
			while ($pos < $v) {
				$s[] = str_replace('?', '!', $struct->segments[$pos++]);
			}
			$pos = 0;
			$opt = $struct->absolute ? '/': '';
			$results[] = $opt . join('/', $s);
		}

		$results[] = str_replace('?', '!', $struct);

		return $results;
	}

}
