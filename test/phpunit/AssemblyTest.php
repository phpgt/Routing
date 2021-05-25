<?php
namespace Gt\Routing\Test;

use Gt\Routing\Assembly;
use PHPUnit\Framework\TestCase;

class AssemblyTest extends TestCase {
	public function testAdd():void {
		$sut = new Assembly();
		$i = null;
		/** @noinspection PhpStatementHasEmptyBodyInspection */
		foreach($sut as $i => $item) {}
		self::assertNull($i);
		$sut->add("/example/path");
		foreach($sut as $i => $item) {
			self::assertEquals("/example/path", $item);
		}
		self::assertSame(0, $i);
	}

	public function testReplace():void {
		$sut = new Assembly();
		$sut->add("/old/path");
		$sut->replace("/old/path", "/new/path");
		$i = null;
		foreach($sut as $i => $item) {
			self::assertSame("/new/path", $item);
		}
		self::assertSame(0, $i);
	}

	public function testRemove():void {
		$sut = new Assembly();
		$sut->add("/example/path/one");
		$sut->add("/example/path/two");
		$sut->remove("/example/path/one");

		$i = null;
		foreach($sut as $i => $item) {
			self::assertSame("/example/path/two", $item);
		}
		self::assertNotNull($i);

		$sut->remove("/example/path/two");

		$i = null;
		/** @noinspection PhpStatementHasEmptyBodyInspection */
		foreach($sut as $i => $item) {}
		self::assertNull($i);
	}

	public function testIterator():void {
		$pathList = [
			"/var/www/dir1",
			"/var/www/dir2",
			"/var/www/dir3",
			"/home/example/dir",
		];
		$sut = new Assembly();
		foreach($pathList as $path) {
			$sut->add($path);
		}

		foreach($sut as $i => $path) {
			self::assertSame($pathList[$i], $path);
		}
	}
}
