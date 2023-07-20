<?php
namespace Gt\Routing\LogicStream;

use Stringable;

class LogicStreamNamespace implements Stringable {
	public function __construct(
		private string $path,
		private string $namespacePrefix = ""
	) {
	}

	public function __toString() {
		$namespace = $this->namespacePrefix;
		$pathParts = explode(DIRECTORY_SEPARATOR, $this->path);
		foreach($pathParts as $part) {
			$namespace .= "\\$part";
		}

		return str_replace(
			["@", "-", "+", "~", "%", "."],
			"_",
			$namespace
		);
	}
}
