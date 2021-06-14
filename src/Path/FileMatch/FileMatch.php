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
}
