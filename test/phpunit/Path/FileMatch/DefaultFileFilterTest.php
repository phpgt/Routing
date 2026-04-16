<?php
namespace GT\Routing\Test\Path\FileMatch;

use GT\Routing\Path\FileMatch\DefaultFileFilter;
use PHPUnit\Framework\TestCase;

class DefaultFileFilterTest extends TestCase {
	/** @var array<string> */
	private array $tempDirectoryList = [];

	protected function tearDown():void {
		foreach($this->tempDirectoryList as $directory) {
			$this->deleteDirectory($directory);
		}

		$this->tempDirectoryList = [];
	}

	public function testMatches_returnsFalseWhenLiteralStaticSiblingBlocksDynamicRoute():void {
		$baseDir = $this->createRouteFixture([
			"site/@slug/index.html",
			"site/@slug/vnc.html",
			"site/@slug/@field/index.html",
		]);
		$sut = new DefaultFileFilter();

		self::assertFalse($sut->matches(
			"$baseDir/site/@slug/@field/index.html",
			"/site/belper/vnc/",
			$baseDir
		));
	}

	public function testMatches_returnsTrueForBasicMatches():void {
		$baseDir = $this->createRouteFixture([
			"blog/index.html",
		]);
		$sut = new DefaultFileFilter();

		self::assertTrue($sut->matches(
			"$baseDir/blog/index.html",
			"/blog/",
			$baseDir
		));
	}

	public function testMatches_returnsTrueForMagicMatches():void {
		$baseDir = $this->createRouteFixture([
			"blog/_header.html",
		]);
		$sut = new DefaultFileFilter();

		self::assertTrue($sut->matches(
			"$baseDir/blog/_header.html",
			"/blog/post",
			$baseDir
		));
	}

	public function testMatches_returnsFalseWhenNothingMatches():void {
		$baseDir = $this->createRouteFixture([
			"blog/index.html",
		]);
		$sut = new DefaultFileFilter();

		self::assertFalse($sut->matches(
			"$baseDir/blog/index.html",
			"/shop/",
			$baseDir
		));
	}

	/** @param array<string> $relativeFiles */
	private function createRouteFixture(array $relativeFiles):string {
		$baseDir = sys_get_temp_dir() . "/routing-default-file-filter-" . uniqid();
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
