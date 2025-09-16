<?php
namespace Gt\Routing\Test;

use Gt\Routing\Redirects;
use PHPUnit\Framework\TestCase;
use SplFileObject;

class RedirectsTest extends TestCase {
	public function testIterate_empty():void {
		$file = new SplFileObject("php://memory", "w");
		$sut = new Redirects($file);
		$key = $value = null;
		foreach($sut as $key => $value) {}
		self::assertNull($key);
		self::assertNull($value);
	}

	public function testIterate():void {
		$file = new SplFileObject("php://memory", "w");
		$file->fputcsv(["from1", "to1"], escape: "\\");
		$file->fputcsv(["from2", "to2"], escape: "\\");
		$file->fputcsv(["from3", "to3"], escape: "\\");

		$sut = new Redirects($file);
		$i = 0;
		foreach($sut as $from => $to) {
			$i++;
			self::assertSame("from$i", $from);
			self::assertSame("to$i", $to);
		}
	}

	public function testConstruct_stringFilePath():void {
		$sut = new Redirects("php://memory");
		self::assertFalse($sut->valid());
	}
}
