<?php
namespace GT\Routing\Test\Path;

use GT\Routing\Path\PathSorter;
use PHPUnit\Framework\TestCase;

class PathSorterTest extends TestCase {
	public function testCompareByDepth_ordersShallowerPathsFirst():void {
		$sut = new PathSorter();

		self::assertSame(
			-1,
			$sut->compareByDepth("page/index.php", "page/site/index.php")
		);
	}

	public function testCompareByDepth_ordersDeeperPathsLast():void {
		$sut = new PathSorter();

		self::assertSame(
			1,
			$sut->compareByDepth("page/site/index.php", "page/index.php")
		);
	}

	public function testCompareByDepth_returnsZeroForMatchingDepth():void {
		$sut = new PathSorter();

		self::assertSame(
			0,
			$sut->compareByDepth("page/one.php", "page/two.php")
		);
	}

	public function testCompareViewAssemblyPaths_placesHeadersFirst():void {
		$sut = new PathSorter();

		self::assertSame(
			-1,
			$sut->compareViewAssemblyPaths("page/_header.html", "page/index.html")
		);
	}

	public function testCompareViewAssemblyPaths_ordersHeadersByDepth():void {
		$sut = new PathSorter();

		self::assertSame(
			-1,
			$sut->compareViewAssemblyPaths("page/_header.html", "page/site/_header.html")
		);
	}

	public function testCompareViewAssemblyPaths_placesFootersLast():void {
		$sut = new PathSorter();

		self::assertSame(
			1,
			$sut->compareViewAssemblyPaths("page/_footer.html", "page/index.html")
		);
	}

	public function testCompareViewAssemblyPaths_ordersFootersByReverseDepth():void {
		$sut = new PathSorter();

		self::assertSame(
			-1,
			$sut->compareViewAssemblyPaths("page/site/_footer.html", "page/_footer.html")
		);
	}

	public function testCompareViewAssemblyPaths_recognisesHeaderWhenSecondArgumentIsHeader():void {
		$sut = new PathSorter();

		self::assertSame(
			1,
			$sut->compareViewAssemblyPaths("page/index.html", "page/_header.html")
		);
	}

	public function testCompareViewAssemblyPaths_recognisesFooterWhenSecondArgumentIsFooter():void {
		$sut = new PathSorter();

		self::assertSame(
			-1,
			$sut->compareViewAssemblyPaths("page/index.html", "page/_footer.html")
		);
	}

	public function testCompareViewAssemblyPaths_returnsZeroForNonMagicFiles():void {
		$sut = new PathSorter();

		self::assertSame(
			0,
			$sut->compareViewAssemblyPaths("page/one.html", "page/two.html")
		);
	}
}
