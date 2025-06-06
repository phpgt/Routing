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

	public function get(?string $key = null, ?bool $extra = false):?string {
		$requestPathParts = explode("/", $this->requestPath);

		foreach($this->assemblyList as $assembly) {
			foreach($assembly as $filePath) {
				$filePathParts = explode("/", $filePath);
				$result = $this->processFilePathParts($filePathParts, $requestPathParts, $key, $extra);
				if($result !== null) {
					return $result;
				}
			}
		}

		return null;
	}

	/**
	 * Process file path parts to find matching parameters
	 *
	 * @param array<string> $filePathParts Parts of the file path
	 * @param array<string> $requestPathParts Parts of the request path
	 * @param string|null $key The key to look for
	 * @param bool|null $extra Whether to include extra path parts
	 * @return string|null The matched parameter or null if no match
	 */
	private function processFilePathParts(
		array $filePathParts,
		array $requestPathParts,
		?string $key,
		?bool $extra,
	): ?string {
		foreach($filePathParts as $i => $filePart) {
			$filePart = strtok($filePart, ".");
			if($filePart[0] !== "@") {
				continue;
			}

			if(is_null($key)) {
				return $this->handleNullKey($filePathParts, $requestPathParts, $extra);
			}

			$result = $this->handleNamedKey($filePart, $requestPathParts, $i, $key);
			if($result !== null) {
				return $result;
			}
		}

		return null;
	}

	/**
	 * Handle the case when the key is null
	 *
	 * @param array<string> $filePathParts Parts of the file path
	 * @param array<string> $requestPathParts Parts of the request path
	 * @param bool|null $extra Whether to include extra path parts
	 * @return string|null The matched parameter
	 */
	private function handleNullKey(
		array $filePathParts,
		array $requestPathParts,
		?bool $extra,
	): ?string {
		if($extra) {
			return $this->getExtraPathParts($filePathParts, $requestPathParts);
		}

		return $requestPathParts[count($filePathParts) - 1] ?? null;
	}

	/**
	 * Get extra path parts beyond the file path
	 *
	 * @param array<string> $filePathParts Parts of the file path
	 * @param array<string> $requestPathParts Parts of the request path
	 * @return string The extra path parts
	 */
	private function getExtraPathParts(array $filePathParts, array $requestPathParts): string {
		$test = "";
		for($ppi = count($filePathParts), $len = count($requestPathParts);
		$ppi < $len; $ppi++) {
			$test .= $requestPathParts[$ppi];
			$test .= "/";
		}
		return rtrim($test, "/");
	}

	/**
	 * Handle the case when a specific key is provided
	 *
	 * @param string $filePart The current file path part
	 * @param array<string> $requestPathParts Parts of the request path
	 * @param int $i The current index
	 * @param string $key The key to look for
	 * @return string|null The matched parameter or null if no match
	 */
	private function handleNamedKey(
		string $filePart,
		array $requestPathParts,
		int $i,
		string $key,
	): ?string {
		if(ltrim($filePart, "@") !== $key) {
			return null;
		}

		$requestPart = $requestPathParts[$i] ?? null;
		if(!$requestPart) {
			return null;
		}

		return $requestPart;
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
