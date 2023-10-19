<?php
namespace Gt\Routing\Test\Path\FileMatch;

use Gt\Routing\Path\FileMatch\BasicFileMatch;
use Gt\Routing\Path\FileMatch\FileMatch;
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

	public function testMatches_dynamicDir():void {
		$sut = new BasicFileMatch(
			"page/shop/@category/@item.html",
			"page"
		);
		self::assertTrue($sut->matches("/shop/cakes/chocolate"));
	}

	public function testMatches_dynamicDir_doesNotMatchIndex():void {
		$sut = new BasicFileMatch(
			"page/shop/@category/@item.html",
			"page"
		);
		self::assertFalse($sut->matches("/shop/cakes"));
	}

	public function testMatches_dynamicDir_doesNotMatchDeeper():void {
		$sut = new BasicFileMatch(
			"page/shop/@category/@item.html",
			"page"
		);
		self::assertFalse($sut->matches("/shop/cakes/chocolate/nothing"));
	}

	public function testMatches_dynamicDir_matchesIndex():void {
		$sut = new BasicFileMatch(
			"page/shop/@category/index.html",
			"page"
		);
		self::assertTrue($sut->matches("/shop/cakes"));
	}

	public function testMatches_dynamicPath_siblingMatchesIndex():void {
		$sut = new BasicFileMatch(
			"page/request/@request-id.html",
			"page",
			[
				"page/request/index.html",
				"page/request/secrets.html",
			]
		);
		self::assertTrue($sut->matches("/request/dynamic-example"));
		self::assertFalse($sut->matches("/request/secrets"));
	}
}
