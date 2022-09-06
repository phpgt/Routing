<?php
namespace Gt\Routing;

use Countable;
use Iterator;

/** @implements Iterator<int, string> */
class Assembly implements Iterator, Countable {
	const TYPE_LOGIC = "logic";
	const TYPE_VIEW = "view";

	/** @var string[] Ordered list of file paths to load. */
	private array $pathList;
	private int $iteratorIndex;

	public function __construct() {
		$this->pathList = [];
		$this->iteratorIndex = 0;
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
			if($fileName[0] !== "_") {
				return true;
			}
		}

		return false;
	}

	public function current():string {
		return $this->pathList[$this->iteratorIndex];
	}

	public function next():void {
		$this->iteratorIndex++;
	}

	public function key():int {
		return $this->iteratorIndex;
	}

	public function valid():bool {
		return isset($this->pathList[$this->iteratorIndex]);
	}

	public function rewind():void {
		$this->iteratorIndex = 0;
	}

	public function count():int {
		return count($this->pathList);
	}
}
