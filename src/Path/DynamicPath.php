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

	public function get(string $key):?string {
		$requestPathParts = explode("/", $this->requestPath);

		foreach($this->assemblyList as $assembly) {
			foreach($assembly as $filePath) {
				$filePathParts = explode("/", $filePath);
				foreach($filePathParts as $i => $f) {
					$f = strtok($f, ".");
					if($f[0] !== "@") {
						continue;
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
}
