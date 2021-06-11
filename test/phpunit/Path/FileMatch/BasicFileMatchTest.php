<?php
namespace Gt\Routing\Test\Path\FileMatch;

use Gt\Routing\Path\FileMatch\BasicFileMatch;
use PHPUnit\Framework\TestCase;

class BasicFileMatchTest extends TestCase {
	public function testMatches_notNested():void {
		$sut = new BasicFileMatch(
			"basedir/something/nested.txt",
			"basedir"
		);
		self::assertFalse($sut->matches("/"));
	}

	public function testMatches_shouldMatchExact():void {
		$sut = new BasicFileMatch(
			"basedir/something/nested.txt",
			"basedir"
		);
		self::assertTrue($sut->matches("/something/nested"));
	}

	public function testMatches_shouldMatchIndex():void {
		$sut = new BasicFileMatch(
			"basedir/something/index.txt",
			"basedir"
		);
		self::assertTrue($sut->matches("/something"));
	}

	public function testMatches_shouldMatchIndex_trailingSlash():void {
		$sut = new BasicFileMatch(
			"basedir/something/index.txt",
			"basedir"
		);
		self::assertTrue($sut->matches("/something/"));
	}

	public function testMatches_shouldMatchIndexNoNesting():void {
		$sut = new BasicFileMatch(
			"page/index.html",
			"page"
		);
		self::assertTrue($sut->matches("/"));
	}
}
