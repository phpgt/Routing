<?php
namespace Gt\Routing\Test\Path;

use Gt\Routing\Assembly;
use Gt\Routing\Path\DynamicPath;
use PHPUnit\Framework\TestCase;

class DynamicPathTest extends TestCase {
	public function testGet_noAssembly():void {
		$sut = new DynamicPath("/");
		self::assertNull($sut->get("something"));
	}

	public function testGet_withAssembly_noMatch():void {
		$assembly = self::createMock(Assembly::class);
		$assembly->method("current")
			->willReturnOnConsecutiveCalls(
				"page/@dynamic/something.html",
				"page/@dynamic/something-else.html"
			);
		$assembly->method("valid")
			->willReturn(
				true,
				true,
				false
			);
		$sut = new DynamicPath("/", $assembly);
		self::assertNull($sut->get("dynamic"));
	}

	public function testGet_byKey():void {
		$assembly = self::createMock(Assembly::class);
		$assembly->method("current")
			->willReturnOnConsecutiveCalls(
				"page/shop/_common.php",
				"page/shop/@category/@itemName.php",
				"page/shop/_common.php",
				"page/shop/@category/@itemName.php",
			);
		$assembly->method("valid")
			->willReturn(true);
		$sut = new DynamicPath("/shop/OnePlus/6T", $assembly);
		self::assertEquals("OnePlus", $sut->get("category"));
		self::assertEquals("6T", $sut->get("itemName"));
	}

	public function testGet_noKey_shouldReturnDeepest():void {
		$assembly = self::createMock(Assembly::class);
		$assembly->method("current")
			->willReturnOnConsecutiveCalls(
				"page/shop/_common.php",
				"page/shop/_common.php",
				"page/shop/@category/@itemName.php",
			);
		$assembly->method("valid")
			->willReturn(true);
		$sut = new DynamicPath("/shop/OnePlus/6T", $assembly);
		self::assertEquals("6T", $sut->get());
	}

	public function testGetUrl():void {
		$viewAssembly = self::createMock(Assembly::class);
		$viewAssembly->method("current")
			->willReturnOnConsecutiveCalls(
				"page/_header.html",
				"page/_footer.html",
				"page/shop/@category/@itemName.html",
			);
		$viewAssembly->method("valid")
			->willReturn(true);
		$logicAssembly = self::createMock(Assembly::class);
		$logicAssembly->method("current")
			->willReturnOnConsecutiveCalls(
				"page/_common.php",
				"page/shop/_common.php",
				"page/shop/@category/@itemName.php",
			);
		$logicAssembly->method("valid")
			->willReturn(true);
		$sut = new DynamicPath("/shop/OnePlus/6T", $viewAssembly, $logicAssembly);
		self::assertSame("/shop/@category/@itemName", $sut->getUrl("page/"));
	}

	public function testDeeperDynamicPages_noExtra():void {
		$assembly = self::createMock(Assembly::class);
		$assembly->method("current")
			->willReturn("page/shop/@category/@@itemName.php");
		$assembly->method("valid")
			->willReturn(true);
		$sut = new DynamicPath("/shop/OnePlus/6T", $assembly);
		self::assertEquals("6T", $sut->get());
	}

	public function testDeeperDynamicPages_getByName():void {
		$assembly = self::createMock(Assembly::class);
		$assembly->method("current")
			->willReturn("page/shop/@category/@@itemName.php");
		$assembly->method("valid")
			->willReturn(true);
		$sut = new DynamicPath("/shop/OnePlus/6T/64GB", $assembly);
		self::assertEquals("6T", $sut->get("itemName"));
	}

	public function testDeeperDynamicPages_getExtra():void {
		$assembly = self::createMock(Assembly::class);
		$assembly->method("current")
			->willReturn("page/shop/@category/@@itemName.php");
		$assembly->method("valid")
			->willReturn(true);
		$sut = new DynamicPath("/shop/OnePlus/6T/64GB", $assembly);
		self::assertEquals("64GB", $sut->getExtra());
	}

	public function testDeeperDynamicPages_getExtraMultiple():void {
		$assembly = self::createMock(Assembly::class);
		$assembly->method("current")
			->willReturn("page/shop/@category/@@itemName.php");
		$assembly->method("valid")
			->willReturn(true);
		$sut = new DynamicPath("/shop/OnePlus/6T/64GB/refurbished", $assembly);
		self::assertEquals("64GB/refurbished", $sut->getExtra());
	}

	public function testDeeperDynamicPages_getExtraMultiple_indexed():void {
		$assembly = self::createMock(Assembly::class);
		$assembly->method("current")
			->willReturn("page/shop/@category/@@itemName.php");
		$assembly->method("valid")
			->willReturn(true);
		$sut = new DynamicPath("/shop/OnePlus/6T/64GB/refurbished", $assembly);
		self::assertEquals("64GB", $sut->getExtra()->getIndex(0));
	}
}
