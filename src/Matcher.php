<?php
namespace Gt\Routing;

class Matcher {
	/** @var ?callable */
	private $callback;

	/** @param string[] $accepts */
	public function __construct(
		private string $baseDir,
		private array $accepts = ["*/*"],
		private ?string $uriPathPrefix = null,
		private ?string $uriPathSuffix = null,
		callable $callback = null
	) {
		$this->callback = $callback;
	}

	public function getBaseDir():string {
		return $this->baseDir;
	}

	/** @return string[] */
	public function getAccepts():array {
		return $this->accepts;
	}

	public function getUriPathPrefix():?string {
		return $this->uriPathPrefix;
	}

	public function getUriPathSuffix():?string {
		return $this->uriPathSuffix;
	}

	public function getCallback():?callable {
		return $this->callback;
	}
}
