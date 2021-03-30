<?php
namespace Gt\Routing;

use Iterator;
use SplFileObject;

/** @implements Iterator<string, string> key=old uri, value=new uri */
class Redirects implements Iterator {
	private SplFileObject $file;
	private string $iteratorKey;
	private string $iteratorValue;

	public function __construct(string $pathname) {
		$this->file = new SplFileObject($pathname, "r");
	}

	public function current():string {
		if(!isset($this->iteratorValue)) {
			$this->readNextLine();
		}

		return $this->iteratorValue;
	}

	public function next():void {
		$this->clearIteratorKeyValue();
		$this->readNextLine();
	}

	public function key():string {
		if(!isset($this->iteratorKey)) {
			$this->readNextLine();
		}

		return $this->iteratorKey;
	}

	public function valid():bool {
		return !$this->file->eof();
	}

	public function rewind():void {
		$this->file->rewind();
		$this->clearIteratorKeyValue();
	}

	private function readNextLine():void {
		$line = $this->file->fgetcsv();
		$this->iteratorKey = $line[0] ?? "";
		$this->iteratorValue = $line[1] ?? "";
	}

	private function clearIteratorKeyValue():void {
		unset($this->iteratorKey);
		unset($this->iteratorValue);
	}
}
