<?php
namespace Gt\Routing\Test\Path;

use Generator;
use Gt\Routing\Path\DirectoryExpander;
use Gt\Routing\Path\PathMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SplFileObject;

class PathMatcherTest extends TestCase {
	public function testFindForUriPath_noRestrictions():void {
		/** @var array<string, SplFileObject> $fakeFiles */
		$fakeFiles = [
			"one.html",
			"two.html",
			"three.html",
			"subdir/four.html",
		];

		$expander = self::mockExpander(...$fakeFiles);

		$sut = new PathMatcher($expander);
		$matches = $sut->findForUriPath("/", "example-dir");
		self::assertNotEmpty($matches);
		foreach($fakeFiles as $filePath) {
			self::assertContains($filePath, $matches);
		}
	}

	public function testGetAllFiles_restrictExtensionToPhp():void {
		$fakeFiles = [
			"one.html",
			"two.html",
			"subdir1/one.php",
			"three.html",
			"subdir2/two.php",
			"three.php"
		];
		$expander = self::mockExpander(...$fakeFiles);

		$sut = new PathMatcher($expander);
		$matches = $sut->findForUriPath(
			"/",
			"example-dir",
			"php"
		);
		self::assertNotEmpty($matches);
		foreach($fakeFiles as $filePath => $file) {
			if(str_ends_with($filePath, ".php")) {
				self::assertContains($filePath, $matches);
			}
			else {
				self::assertNotContains($filePath, $matches);
			}
		}
	}

	public function testGetAllFiles_filter():void {
		$fakeFiles = [
			"_common.php",
			"index.php",
			"subdir1/one.php",
			"subdir1/two.php",
			"subdir1/_common.php",
			"three.html",
			"subdir2/two.php",
			"three.php",
			"four.html",
		];
		$expander = self::mockExpander(...$fakeFiles);

		$sut = new PathMatcher($expander);
		$sut->addFilter(function(string $filePath, string $uriPath):bool {
// Really simple filter function that kinda looks how WebEngine does it (but
// extremely simplified).
			$baseName = pathinfo($filePath, PATHINFO_BASENAME);
			if($baseName === "_common.php") {
				return true;
			}

			$fileNameNoExt = strtok($filePath, ".");
			return $fileNameNoExt === ltrim($uriPath, "/");
		});
		$matches = $sut->findForUriPath(
			"/subdir1/two",
			"example-dir",
			"php"
		);
		self::assertNotEmpty($matches);
		self::assertContains("_common.php", $matches);
		self::assertContains("subdir1/_common.php", $matches);
		self::assertContains("subdir1/two.php", $matches);
	}

	protected function mockExpander(string...$files):DirectoryExpander {
		$splFileObjectArray = [];
		foreach($files as $f) {
			$splFileObjectArray[$f] = self::fakeFile($f);
		}

		$expander = self::createMock(DirectoryExpander::class);
		$expander->method("generate")
			->willReturn(self::arrayAsGenerator($splFileObjectArray));
		return $expander;
	}

	protected function arrayAsGenerator(array $array):Generator {
		foreach($array as $key => $value) {
			yield $key => $value;
		}
	}

	protected function fakeFile(string $filePath):SplFileObject {
		/** @var MockObject|SplFileObject $file */
		$file = self::getMockBuilder(SplFileObject::class)
			->enableOriginalConstructor()
			->setConstructorArgs(["php://memory"])
			->getMock();
		$file->method("getFilename")
			->willReturn(pathinfo($filePath, PATHINFO_FILENAME));
		$file->method("getExtension")
			->willReturn(pathinfo($filePath, PATHINFO_EXTENSION));

		return $file;
	}
}
