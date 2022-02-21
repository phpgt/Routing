<?php
namespace Gt\Routing\Path;

use Stringable;

class Extra implements Stringable {
	public function __construct(private string $path) {
	}

	public function getIndex(int $index):?string {
		$parts = explode("/", $this->path);
		return $parts[$index] ?? null;
	}

	public function __toString():string {
		return $this->path;
	}
}
