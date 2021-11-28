<?php
namespace Gt\Routing\Path;

use Gt\Routing\Assembly;

class DynamicPath {
	/** @var array<Assembly> */
	private array $assemblyList;

	public function __construct(
		private string $requestPath,
		Assembly...$assemblyList
	) {
		$this->assemblyList = $assemblyList;
	}

	public function get(string $key = null):?string {
		$requestPathParts = explode("/", $this->requestPath);

		foreach($this->assemblyList as $assembly) {
			foreach($assembly as $filePath) {
				$filePathParts = explode("/", $filePath);
				foreach($filePathParts as $i => $f) {
					$f = strtok($f, ".");
					if($f[0] !== "@") {
						continue;
					}

					if(is_null($key)) {
						return $requestPathParts[count($filePathParts) - 1] ?? null;
					}

					if("@$key" !== $f) {
						continue;
					}

					$r = $requestPathParts[$i] ?? null;
					if(!$r) {
						continue;
					}

					return $r;
				}
			}
		}

		return null;
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

				break;
			}
		}

		$url = "/" . trim(substr($path, strlen($viewBasePath)), "/");
		$url = strtok($url, ".");
		return $url;
	}
}
