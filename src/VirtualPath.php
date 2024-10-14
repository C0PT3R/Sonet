<?php

namespace Sonet;


const PATH_REGULAR = "([\w\-_]*)";
const PATH_REQUIRED = "!([A-z_][\w_]*)";
const PATH_OPTIONAL = "\?([A-z_][\w_]*)";
const VALID_PATH = "#^((\/)|(((\/" . PATH_REGULAR . ")|(\/" . PATH_REQUIRED . "))+((\/" . PATH_OPTIONAL . ")|(\/" . PATH_REGULAR . "))*))$#";


class PathStruct {
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


	public function normalize(): string {
		$str = $this->absolute ? '/' : '';
		$str .= join("/", $this->segments);
		return $str;
	}

	public function __toString(): string {
		return $this->normalize();
	}
}


final class VirtualPath {

	public static function compile(string ...$paths): array {
		$struct = new PathStruct(...$paths);

		if (str_contains($struct, '|')) {
			$results = [];

			for ($i = 0; $i < count($struct->segments); $i++) {
				if (str_contains($struct->segments[$i], '|')) {
					$aliases = explode('|', $struct->segments[$i]);

					foreach ($aliases as $alias) {
						$ap = new PathStruct();
						$ap->absolute = $struct->absolute;
						for ($j = 0; $j < count($struct->segments); $j++) {
							$ap->segments[] = ($i === $j) ? $alias : $struct->segments[$j];
						}
						$results = array_merge($results, self::compile($ap));
					}
				}
			}

			return $results;
		} else {
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


	public static function resolve(string ...$paths): string {
		$struct = new PathStruct(...$paths);
		
		// Resolve parent directories
		for ($i = 0; $i < count($struct->segments); $i++) {
			if ($struct->segments[$i] == '..') {
				if ($i - 1 >= 0) {
					if ($struct->segments[$i-1] == '..') continue;
					array_splice($struct->segments, $i-1, 2);
					$i -= 2;
				}
			}
		}

		// Remove all parents at the start of an absolute path
		if ($struct->absolute) {
			while (array_key_exists(0, $struct->segments) && $struct->segments[0] == '..') {
				array_splice($struct->segments, 0, 1);
			}
		}

		return $struct->normalize();
	}


	public static function parseParams(string $path, string $uri): \stdClass|null {
		$params = new \stdClass();

		// Get params names
		if (!preg_match_all("#" . PATH_REQUIRED . "#", $path, $pnames)) return null;
		$pnames = $pnames[1];

		// Get params values
		$pattern = preg_replace("#" . PATH_REQUIRED . "#", '([^/]+)', trim($path, '/'));
		if (!preg_match("#^$pattern$#", trim($uri, '/'), $pvals)) return null;
		array_shift($pvals); // Remove the first item as it's not relevant

		// Assign params
		for ($i = 0; $i < count($pvals); $i++) {
			$params->{$pnames[$i]} = $pvals[$i];
		}

		return $params;
	}


	public static function matchesURI(string $path, string $uri): bool {
		//var_dump($path);
		//var_dump($uri);
		$pattern = preg_replace("#" . PATH_REQUIRED . "#", '[^/]+', trim($path, '/'));
		return preg_match("#^$pattern$#", trim($uri, '/')) ? true : false;
	}

}
