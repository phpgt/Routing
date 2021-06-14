<?php
namespace Gt\Routing\Path\FileMatch;

abstract class FileMatch {
	public function __construct(
		protected string $filePath,
		protected string $baseDir,
	) {
	}

	abstract public function matches(string $uriPath):bool;

	protected function getTrimmedFilePath(
		bool $includeBaseDir = false
	):string {
		$filePathTrimmed = implode("/", [
			pathinfo($this->filePath, PATHINFO_DIRNAME),
			pathinfo($this->filePath, PATHINFO_FILENAME),
		]);
		$filePathTrimmed = preg_replace(
			"/\/@[^\/]+/",
			"/@",
			$filePathTrimmed
		);

		if($includeBaseDir) {
			return $filePathTrimmed;
		}
		else {
			return substr(
				$filePathTrimmed,
				strlen($this->baseDir . "/")
			);
		}
	}

	/**
	 * @param string $filePath
	 * @param string[] $uriPathParts
	 * @return string[]
	 */
	protected function filterDynamicPathParts(
		string $filePath,
		array $uriPathParts
	):array {
		$filePathParts = explode("/", $filePath);
		foreach($uriPathParts as $i => $uriPathPart) {
			if(!isset($filePathParts[$i])) {
				break;
			}

			$filePathPart = $filePathParts[$i];
			if($filePathPart === "@") {
				$uriPathParts[$i] = "@";
			}
		}

		return $uriPathParts;
	}
}
