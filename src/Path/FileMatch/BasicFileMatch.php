<?php
namespace Gt\Routing\Path\FileMatch;

/**
 * A "Basic" FileMatch is one that directly matches a Uri path to a
 * file path by name. Uris are
 */
class BasicFileMatch extends FileMatch {
	public function matches(string $uriPath):bool {
		$uriPath = trim($uriPath, "/");
		$uriPathIndex = trim("$uriPath/index", "/");

		$filePathTrimmed = implode("/", [
			pathinfo($this->filePath, PATHINFO_DIRNAME),
			pathinfo($this->filePath, PATHINFO_FILENAME),
		]);
		$filePathTrimmed = substr($filePathTrimmed, strlen($this->baseDir . "/"));

		if($uriPath === $filePathTrimmed
		|| $uriPathIndex === $filePathTrimmed) {
			return true;
		}

		return false;
	}
}
