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

	public function get(?string $key = null, bool $extra = false):?string {
		$requestPathParts = explode("/", $this->requestPath);

		foreach($this->assemblyList as $assembly) {
			foreach($assembly as $filePath) {
				$result = $this->processFilePath($filePath, $requestPathParts, $key, $extra);
				if(!is_null($result)) {
					return $result;
				}
			}
		}

		return null;
	}

	/** @param array<string> $requestPathParts */
	private function processFilePath(
		string $filePath,
		array $requestPathParts,
		?string $key,
		bool $extra,
	):?string {
		$filePathParts = explode("/", $filePath);

		foreach($filePathParts as $i => $f) {
			$f = strtok($f, ".");
			if($f[0] !== "@") {
				continue;
			}

			if(is_null($key)) {
				return $this->processNullKey($filePathParts, $requestPathParts, $extra);
			}

			if(ltrim($f, "@") !== $key) {
				continue;
			}

			$r = $requestPathParts[$i] ?? null;
			if($r) {
				return $r;
			}
		}

		return null;
	}

	/**
	 * @param array<string> $filePathParts
	 * @param array<string> $requestPathParts
	 */
	private function processNullKey(
		array $filePathParts,
		array $requestPathParts,
		bool $extra,
	):?string {
		if($extra) {
			$test = "";
			for($ppi = count($filePathParts), $len = count($requestPathParts); $ppi < $len; $ppi++) {
				$test .= $requestPathParts[$ppi];
				$test .= "/";
			}
			return rtrim($test, "/");
		}

		return $requestPathParts[count($filePathParts) - 1] ?? null;
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
