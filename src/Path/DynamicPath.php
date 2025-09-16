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
				$filePathParts = explode("/", $filePath);

				foreach($filePathParts as $i => $f) {
					$f = strtok($f, ".");
					if($f[0] !== "@") {
						continue;
					}

					if(is_null($key)) {
						return $this->handleNullKey(
							$filePathParts,
							$requestPathParts,
							$extra,
						);
					}

					if($this->matchesKey($f, $key)) {
						$matchedKey = $this->getMatchedKey(
							$requestPathParts,
							$i,
						);
						if(!$matchedKey) {
							continue;
						}
						return $matchedKey;
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param array<string> $filePathParts
	 * @param array<string> $requestPathParts
	 */
	private function handleNullKey(
		array $filePathParts,
		array $requestPathParts,
		bool $extra,
	):?string {
		if($extra) {
			return $this->getExtraPath($filePathParts, $requestPathParts);
		}

		return $requestPathParts[count($filePathParts) - 1] ?? null;
	}

	/**
	 * @param array<string> $filePathParts
	 * @param array<string> $requestPathParts
	 */
	private function getExtraPath(
		array $filePathParts,
		array $requestPathParts,
	):string {
		$test = "";
		for($ppi = count($filePathParts), $len = count($requestPathParts); $ppi < $len; $ppi++) {
			$test .= $requestPathParts[$ppi] . "/";
		}
		return rtrim($test, "/");
	}

	private function matchesKey(string $placeholder, string $key):bool {
		return ltrim($placeholder, "@") === $key;
	}

	/** @param array<string> $requestPathParts */
	private function getMatchedKey(
		array $requestPathParts,
		int $index,
	):?string {
		$matchedKey = $requestPathParts[$index] ?? null;
		if(!$matchedKey) {
			return null;
		}
		return $matchedKey;
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
