<?php
namespace GT\Routing\Test\Path\FileMatch;

use GT\Routing\Path\FileMatch\BasicFileMatch;
use PHPUnit\Framework\TestCase;

class BasicFileMatchTest extends TestCase {
	/** @var array<string> */
	private array $tempDirectoryList = [];

	protected function tearDown():void {
		foreach($this->tempDirectoryList as $directory) {
			$this->deleteDirectory($directory);
		}

		$this->tempDirectoryList = [];
	}

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

	public function testMatches_nestedDynamicPath_siblingMatchesIndex():void {
		$sut = new BasicFileMatch(
			"page/request/@share-id/@request-id.html",
			"page",
			[
				"page/request/@share-id/index.html",
				"page/request/@share-id/secrets.html",
			]
		);
		self::assertTrue($sut->matches("/request/share123/dynamic-example"));
		self::assertFalse($sut->matches("/request/share123/secrets"));
	}

	public function testMatches_dynamicIndex_doesNotMatchStaticSiblingAtParentLevel():void {
		$sut = new BasicFileMatch(
			"page/site/@slug/@field/index.html",
			"page",
			[
				"page/site/@slug/index.html",
				"page/site/@slug/vnc.html",
			]
		);
		self::assertFalse($sut->matches("/site/belper/vnc/"));
	}

	public function testMatches_dynamicIndex_discoversParentLevelStaticSiblingsFromDisk():void {
		$baseDir = $this->createRouteFixture([
			"site/@slug/index.html",
			"site/@slug/vnc.html",
			"site/@slug/@field/index.html",
		]);

		$sut = new BasicFileMatch(
			"$baseDir/site/@slug/@field/index.html",
			$baseDir
		);

		self::assertFalse($sut->matches("/site/belper/vnc/"));
	}

	public function testMatches_dynamicIndex_discoversParentLevelStaticSiblingsWithoutBlockingDynamicMatches():void {
		$baseDir = $this->createRouteFixture([
			"site/@slug/index.html",
			"site/@slug/vnc.html",
			"site/@slug/@field/index.html",
		]);

		$sut = new BasicFileMatch(
			"$baseDir/site/@slug/@field/index.html",
			$baseDir
		);

		self::assertTrue($sut->matches("/site/belper/FIELD_01KM41GW10FTYTJYRGSX/"));
	}

	/** @param array<string> $relativeFiles */
	private function createRouteFixture(array $relativeFiles):string {
		$baseDir = sys_get_temp_dir() . "/routing-basic-file-match-" . uniqid();
		mkdir($baseDir, recursive: true);
		$this->tempDirectoryList[] = $baseDir;

		foreach($relativeFiles as $relativeFile) {
			$absoluteFile = "$baseDir/$relativeFile";
			$absoluteDir = dirname($absoluteFile);
			if(!is_dir($absoluteDir)) {
				mkdir($absoluteDir, recursive: true);
			}

			touch($absoluteFile);
		}

		return $baseDir;
	}

	private function deleteDirectory(string $directory):void {
		if(!is_dir($directory)) {
			return;
		}

		foreach(scandir($directory) ?: [] as $item) {
			if($item === "." || $item === "..") {
				continue;
			}

			$path = "$directory/$item";
			if(is_dir($path)) {
				$this->deleteDirectory($path);
				continue;
			}

			unlink($path);
		}

		rmdir($directory);
	}
}
