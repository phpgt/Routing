<?php
namespace Gt\Routing\Path\FileMatch;

class MagicFileMatch extends FileMatch {
	const MAGIC_FILENAME_ARRAY = [
		"_common",
		"_header",
		"{current}", // this will be replaced by the current page name
		"_footer",
	];

	public function matches(string $uriPath):bool {
		$uriPath = trim($uriPath, "/");
		$uriPathParts = explode("/", $uriPath);

		$filePathTrimmed = $this->getTrimmedFilePath();
		$uriPathParts = $this->filterDynamicPathParts($filePathTrimmed, $uriPathParts);

		$searchDir = "";
		foreach($uriPathParts as $pathPart) {
			foreach(self::MAGIC_FILENAME_ARRAY as $magicFilename) {
				$searchFilepath = trim("$searchDir/$magicFilename", "/");
				if($searchFilepath === $filePathTrimmed) {
					return true;
				}
			}

			$searchDir .= "/$pathPart";
		}

		return false;
	}
}
