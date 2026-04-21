<?php
namespace GT\Routing\Path\FileMatch;

class DefaultFileFilter {
	public function matches(
		string $filePath,
		string $uriPath,
		string $baseDir
	):bool {
		if($this->hasLiteralStaticSibling($filePath, $uriPath, $baseDir)) {
			return false;
		}

		$basicFileMatch = new BasicFileMatch($filePath, $baseDir);
		if($basicFileMatch->matches($uriPath)) {
			return true;
		}

		$magicFileMatch = new MagicFileMatch($filePath, $baseDir);
		return $magicFileMatch->matches($uriPath);
	}

	private function hasLiteralStaticSibling(
		string $filePath,
		string $uriPath,
		string $baseDir
	):bool {
		foreach(glob($baseDir . $uriPath . ".*") as $globMatch) {
			$uriContainer = pathinfo($uriPath, PATHINFO_DIRNAME);
			$trimBase = $baseDir . $uriContainer;
			if(str_starts_with($globMatch, $trimBase)) {
				$trimmed = substr($filePath, strlen($trimBase));
				if(str_contains($trimmed, "@")) {
					return true;
				}
			}
		}

		return false;
	}
}
