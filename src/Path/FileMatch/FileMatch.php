<?php
namespace Gt\Routing\Path\FileMatch;

abstract class FileMatch {
	/** @var array<array<string>> An array of paths parts for the siblings of the file path */
	protected array $siblingFilePathParts;

	/** @param null|array<string> $siblingFiles Sibling files on disk to the current file path */
	public function __construct(
		protected string $filePath,
		protected string $baseDir,
		?array $siblingFiles = null,
	) {
		$dirname = dirname($this->filePath);
		$ext = pathinfo($this->filePath, PATHINFO_EXTENSION);

		$this->siblingFilePathParts = [];
		foreach($siblingFiles ?? glob("$dirname/*.$ext") as $filePath) {
			$filePathNoBaseDir = substr($filePath, strlen($this->baseDir . "/"));
			$pathParts = explode("/", $filePathNoBaseDir);
			foreach($pathParts as $i => $pathPart) {
				if(str_contains($pathPart, "@")) {
					$pathParts[$i] = "@";
				}
				else {
					$dotPos = strpos($pathPart, ".");
					if($dotPos !== false) {
						$pathParts[$i] = substr($pathPart, 0, $dotPos);
					}
				}
			}

			array_push(
				$this->siblingFilePathParts,
				$pathParts,
			);
		}
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
		$matchingSibling = in_array($uriPathParts, $this->siblingFilePathParts);

		foreach($uriPathParts as $i => $uriPathPart) {
			if(!isset($filePathParts[$i])) {
				break;
			}

			$filePathPart = $filePathParts[$i];

// On the last iteration, don't convert if there's a sibling match.
			if(!isset($filePathParts[$i + 1]) && !$matchingSibling) {
				if($filePathPart === "@") {
					$uriPathParts[$i] = "@";
				}
			}
		}

		return $uriPathParts;
	}
}
