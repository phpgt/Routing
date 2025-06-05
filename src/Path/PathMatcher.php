<?php
namespace Gt\Routing\Path;

class PathMatcher {
	private DirectoryExpander $expander;
	/** @var callable[] */
	private array $filterArray;

	public function __construct(
		private string $baseDirectory,
		?DirectoryExpander $expander = null,
	) {
		$this->expander = $expander ?? new DirectoryExpander();
		$this->filterArray = [];
	}

	public function addFilter(callable $filter):void {
		array_push($this->filterArray, $filter);
	}

	/**
	 * Matches a provided URI path with the recursive contents of the
	 * provided directory. Only matches the provided extensions, unless no
	 * extensions are provided, in which case all extensions are matched.
	 *
	 * @return array<int, string> The returned array is ordered in
	 * directory-depth order, meaning that files that are deeper nested will
	 * come after files within the root of the $dirPath.
	 */
	public function findForUriPath(
		string $uriPath,
		string $dirPath,
		string...$extensions
	):array {
		$matches = $this->getAllFiles($dirPath, ...$extensions);
		return $this->filterUri($matches, $uriPath, $dirPath);
	}

	/**
	 * @return array<int, string> An un-ordered array of file paths. If any
	 * extensions are provided, only those files with given extensions will
	 * be added to the array.
	 */
	private function getAllFiles(
		string $dirPath,
		string...$extensions
	):array {
		$allFiles = [];

		foreach($this->expander->generate($dirPath) as $filePath => $file) {
			$ext = $file->getExtension();
			if(!empty($extensions)) {
				if(!in_array($ext, $extensions)) {
					continue;
				}
			}

			array_push($allFiles, $filePath);
		}

		return $allFiles;
	}

	/**
	 * This function takes a file path array and a URI path, and returns a
	 * filtered file path array that contains only file paths that match
	 * the provided URI path. This is done by calling the user-supplied
	 * filter function(s) added by addFilter().
	 *
	 * Each filter function will be called in turn with the file path and
	 * URI path as the two parameters. The filter functions must return true
	 * to keep the file path in the array, false otherwise.
	 *
	 * @param string[] $filePathArray
	 * @param string $uriPath A URI path to match against.
	 * @return string[] The filtered array of file paths.
	 */
	private function filterUri(
		array $filePathArray,
		string $uriPath,
		string $subDir
	):array {
		$filteredFilePathArray = $filePathArray;

		foreach($this->filterArray as $filter) {
			$filteredFilePathArray = array_filter(
				$filteredFilePathArray,
				fn(string $filePath):bool => call_user_func(
					$filter,
					$filePath,
					$uriPath,
					$this->baseDirectory,
					$subDir
				)
			);
		}

		return $filteredFilePathArray;
	}
}
