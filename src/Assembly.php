<?php
namespace Gt\Routing;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Gt\Routing\Path\FileMatch\MagicFileMatch;

/** @implements IteratorAggregate<int, string> */
class Assembly implements IteratorAggregate, Countable {
	const TYPE_LOGIC = "logic";
	const TYPE_VIEW = "view";

	/** @var string[] Ordered list of file paths to load. */
	private array $pathList;

	public function __construct() {
		$this->pathList = [];
	}

	public function add(string $path):void {
		array_push($this->pathList, $path);
	}

	public function replace(string $oldPath, string $newPath):void {
		$key = array_search($oldPath, $this->pathList);
		$this->pathList[$key] = $newPath;
	}

	public function remove(string $path):void {
		$key = array_search($path, $this->pathList);
		unset($this->pathList[$key]);
		$this->pathList = array_values($this->pathList);
	}

	public function containsDistinctFile():bool {
		foreach($this->pathList as $path) {
			$fileName = pathinfo($path, PATHINFO_FILENAME);
			if(!str_starts_with($fileName, "_")
				|| !in_array($fileName, MagicFileMatch::MAGIC_FILENAME_ARRAY)) {
				return true;
			}
		}
		return false;
	}

	/** @return ArrayIterator<int, string> */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator($this->pathList);
	}

	public function count(): int {
		return count($this->pathList);
	}
}
