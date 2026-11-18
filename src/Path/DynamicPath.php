<?php
namespace GT\Routing\Path;

use GT\Routing\Assembly;

class DynamicPath {
	/** @var array<Assembly> */
	private array $assemblyList;

	public function __construct(
		private string $requestPath,
		Assembly...$assemblyList
	) {
		$this->assemblyList = $assemblyList;
	}

	/**
	 * With no key, return the deepest dynamic value across all assemblies.
	 * With a key, select its one-based occurrence, defaulting to the first.
	 * Return null if the requested occurrence is absent.
	 * Boolean indices retain the legacy positional extra argument.
	 */
	public function get(?string $key = null, int|bool $index = 1, bool $extra = false):?string {
		if(is_bool($index)) {
			$extra = $index;
			$index = 1;
		}
		$matches = $this->getMatchingParts($key, $extra);
		ksort($matches);
		if(is_null($key)) {
			return $matches ? end($matches) : null;
		}
		return array_values($matches)[$index - 1] ?? null;
	}

	/** @return array<int, string> */
	private function getMatchingParts(?string $key, bool $extra):array {
		$requestPathParts = explode("/", $this->requestPath);
		$matches = [];
		foreach($this->assemblyList as $assembly) {
			foreach($assembly as $filePath) {
				foreach(explode("/", $filePath) as $i => $part) {
					$placeholder = explode(".", $part)[0];
					if(!str_starts_with($placeholder, "@")) {
						continue;
					}
					if(!is_null($key) && ltrim($placeholder, "@") !== $key) {
						continue;
					}
					$value = $this->getPartValue($requestPathParts, $i, $placeholder, $extra);
					if(!is_null($value)) {
						$matches[$i] = $value;
					}
				}
			}
		}
		return $matches;
	}

	/** @param array<string> $requestPathParts */
	private function getPartValue(array $requestPathParts, int $index, string $placeholder, bool $extra):?string {
		if($extra) {
			return str_starts_with($placeholder, "@@")
				? rtrim(implode("/", array_slice($requestPathParts, $index + 1)), "/")
				: null;
		}
		$value = $requestPathParts[$index] ?? "";
		return $value === "" ? null : $value;
	}

	public function getUrl(string $viewBasePath):string {
		$path = "";

		foreach($this->assemblyList as $assembly) {
			foreach($assembly as $path) {
				$fileName = pathinfo($path, PATHINFO_FILENAME);
				$ext = pathinfo($path, PATHINFO_EXTENSION);
				if($ext !== "html") {
					continue(2);
				}

				if($fileName[0] === "_") {
					continue;
				}

				break(2);
			}
		}

		$url = "/" . trim(substr($path, strlen($viewBasePath)), "/");
		return strtok($url, ".");
	}

	public function getExtra():Extra {
		return new Extra($this->get(extra: true));
	}
}
