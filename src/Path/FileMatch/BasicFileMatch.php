<?php
namespace Gt\Routing\Path\FileMatch;

/**
 * A "Basic" FileMatch is one that directly matches a Uri path to a
 * file path by name.
 */
class BasicFileMatch extends FileMatch {
	public function matches(string $uriPath):bool {
		$uriPath = trim($uriPath, "/");
		$uriPathParts = explode("/", $uriPath);
		$filePathTrimmed = $this->getTrimmedFilePath();
		$uriPathParts = $this->filterDynamicPathParts($filePathTrimmed, $uriPathParts);
		$uriPath = implode("/", $uriPathParts);
		$uriPathIndex = trim("$uriPath/index", "/");

		if($uriPath === $filePathTrimmed
		|| $uriPathIndex === $filePathTrimmed) {
			return true;
		}

		return false;
	}
}
