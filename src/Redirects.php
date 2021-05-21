<?php
namespace Gt\Routing;

use Iterator;
use SplFileObject;

/** @implements Iterator<string, string> */
class Redirects implements Iterator {
	/** @var array<string, string> Key = from url, value = to url */
	private array $redirectData;

	public function __construct(SplFileObject|string $file) {
		if(is_string($file)) {
			$file = new SplFileObject($file, "r");
		}

		$file->rewind();
		$this->redirectData = [];

		while(!$file->eof()) {
			$row = $file->fgetcsv();
			if(!isset($row[1])) {
				continue;
			}

			$this->redirectData[$row[0]] = $row[1];
		}
	}

	public function rewind():void {
		reset($this->redirectData);
	}

	public function valid():bool {
		return !is_null(key($this->redirectData));
	}

	public function current():string {
		return current($this->redirectData);
	}

	public function next():void {
		next($this->redirectData);
	}

	public function key():string {
		return key($this->redirectData);
	}
}
