<?php
namespace Gt\Routing\Test\Path\FileMatch;

use Gt\Routing\Path\FileMatch\MagicFileMatch;
use PHPUnit\Framework\TestCase;

class MagicFileMatchTest extends TestCase {
	public function testMatches_none():void {
		$sut = new MagicFileMatch(
			"basedir/something/nested.txt",
			"basedir"
		);
		self::assertFalse($sut->matches("/"));
	}

	public function testMatches_noDirectMatch():void {
		$sut = new MagicFileMatch(
			"basedir/something/nested.txt",
			"basedir"
		);
		self::assertFalse($sut->matches("/something/nested"));
	}

	public function testMatches_sameDir():void {
		$sut = new MagicFileMatch(
			"basedir/something/_common.php",
			"basedir"
		);
		self::assertTrue($sut->matches("/something/nested"));
	}

	public function testMatches_parentDir():void {
		$sut = new MagicFileMatch(
			"basedir/_common.php",
			"basedir"
		);
		self::assertTrue($sut->matches("/something/nested"));
	}

	public function testMatches_ancestorDir():void {
		$sut = new MagicFileMatch(
			"basedir/something/_common.php",
			"basedir"
		);
		self::assertTrue($sut->matches("/something/very/deeply/nested"));
	}
}
