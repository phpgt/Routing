<?php
namespace GT\Routing\Path;

class PathSorter {
	public function compareByDepth(string $a, string $b):int {
		$aDepth = substr_count($a, "/");
		$bDepth = substr_count($b, "/");
		return $aDepth <=> $bDepth;
	}

	public function compareViewAssemblyPaths(string $a, string $b):int {
		$fileNameA = pathinfo($a, PATHINFO_FILENAME);
		$fileNameB = pathinfo($b, PATHINFO_FILENAME);

		if($fileNameA === "_header") {
			return $fileNameB === "_header"
				? $this->compareByDepth($a, $b)
				: -1;
		}

		if($fileNameA === "_footer") {
			return $fileNameB === "_footer"
				? $this->compareByDepth($b, $a)
				: 1;
		}

		if($fileNameB === "_header") {
			return 1;
		}

		if($fileNameB === "_footer") {
			return -1;
		}

		return 0;
	}
}
