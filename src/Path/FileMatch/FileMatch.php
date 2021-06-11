<?php
namespace Gt\Routing\Path\FileMatch;

abstract class FileMatch {
	public function __construct(
		protected string $filePath,
		protected string $baseDir,
	) {
	}

	abstract public function matches(string $uriPath):bool;
}
