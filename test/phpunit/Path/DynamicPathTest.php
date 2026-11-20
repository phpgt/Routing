<?php
namespace GT\Routing\Test\Path;

use GT\Routing\Assembly;
use GT\Routing\Path\DynamicPath;
use PHPUnit\Framework\TestCase;

class DynamicPathTest extends TestCase {
	public function testGet_deepestRepeatedKey():void {
		$assembly = new Assembly();
		$assembly->add("page/groups/@slug/_common.php");
		$assembly->add("page/groups/@slug/events/@slug/index.php");
		$sut = new DynamicPath("/groups/my-group/events/my-event", $assembly);
		self::assertSame("my-event", $sut->get());
	}

	public function testGet_deepestDistinctKeyAcrossAssemblies():void {
		$view = new Assembly();
		$view->add("page/groups/@group/index.html");
		$logic = new Assembly();
		$logic->add("page/groups/@group/events/@event/index.php");
		$sut = new DynamicPath("/groups/my-group/events/my-event", $view, $logic);
		self::assertSame("my-event", $sut->get());
	}

	public function testGet_repeatedKeyOccurrence():void {
		$view = new Assembly();
		$view->add("page/groups/@slug/_header.html");
		$view->add("page/groups/@slug/events/@slug/index.html");
		$logic = new Assembly();
		$logic->add("page/groups/@slug/events/@slug/index.php");
		$sut = new DynamicPath("/groups/my-group/events/my-event", $view, $logic);
		self::assertSame("my-group", $sut->get("slug"));
		self::assertSame("my-group", $sut->get("slug", 1));
		self::assertSame("my-event", $sut->get("slug", 2));
		self::assertNull($sut->get("slug", 3));
		self::assertNull($sut->get("slug", 0));
	}

	public function testGet_extraArgumentsWithSharedParent():void {
		$assembly = new Assembly();
		$assembly->add("page/shop/@category/_common.php");
		$assembly->add("page/shop/@category/@@item.php");
		$sut = new DynamicPath("/shop/phones/handset/64GB/refurbished", $assembly);
		self::assertSame("64GB/refurbished", $sut->get(extra: true));
		self::assertSame("64GB/refurbished", $sut->get(null, true));
		self::assertSame("handset", $sut->get(null, false));
		self::assertSame("64GB/refurbished", (string)$sut->getExtra());
	}

	public function testGet_zeroValue():void {
		$assembly = new Assembly();
		$assembly->add("page/@name/@name.php");
		$sut = new DynamicPath("/first/0", $assembly);
		self::assertSame("0", $sut->get());
		self::assertSame("0", $sut->get("name", 2));
	}

	public function testGet_noAssembly():void {
		$sut = new DynamicPath("/");
		self::assertNull($sut->get("something"));
	}

	public function testGet_withAssembly_noMatch():void {
		$assembly = new Assembly();
		$assembly->add("page/@dynamic/something.html");
		$assembly->add("page/@dynamic/something-else.html");
		$sut = new DynamicPath("/", $assembly);
		self::assertNull($sut->get("dynamic"));
	}

	public function testGet_byKey():void {
		$assembly = new Assembly();
		$assembly->add("page/shop/_common.php");
		$assembly->add("page/shop/@category/@itemName.php");
		$sut = new DynamicPath("/shop/OnePlus/6T", $assembly);
		self::assertEquals("OnePlus", $sut->get("category"));
		self::assertEquals("6T", $sut->get("itemName"));
	}

	public function testGet_noKey_shouldReturnDeepest():void {
		$assembly = new Assembly();
		$assembly->add("page/shop/_common.php");
		$assembly->add("page/shop/@category/@itemName.php");
		$sut = new DynamicPath("/shop/OnePlus/6T", $assembly);
		self::assertEquals("6T", $sut->get());
	}

	public function testGet_noKey_returnsRightMostDynamicPathPartAcrossMatches():void {
		$assembly = new Assembly();
		$assembly->add("page/shop/@category/index.php");
		$assembly->add("page/shop/@category/@itemName.php");
		$sut = new DynamicPath("/shop/OnePlus/6T", $assembly);
		self::assertSame("6T", $sut->get());
	}

	public function testGet_noKey_returnsRightMostStandardDynamicPartBeforeExtraPart():void {
		$assembly = new Assembly();
		$assembly->add("page/shop/@category/index.php");
		$assembly->add("page/shop/@category/@@itemName.php");
		$sut = new DynamicPath("/shop/OnePlus/6T/64GB", $assembly);
		self::assertSame("6T", $sut->get());
	}

	public function testGetUrl():void {
		$viewAssembly = new Assembly();
		$viewAssembly->add("page/_header.html");
		$viewAssembly->add("page/_footer.html");
		$viewAssembly->add("page/shop/@category/@itemName.html");
		$logicAssembly = new Assembly();
		$logicAssembly->add("page/_common.php");
		$logicAssembly->add("page/shop/_common.php");
		$logicAssembly->add("page/shop/@category/@itemName.php");
		$sut = new DynamicPath("/shop/OnePlus/6T", $viewAssembly, $logicAssembly);
		self::assertSame("/shop/@category/@itemName", $sut->getUrl("page/"));
	}

	public function testDeeperDynamicPages_noExtra():void {
		$assembly = new Assembly();
		$assembly->add("page/shop/@category/@@itemName.php");
		$sut = new DynamicPath("/shop/OnePlus/6T", $assembly);
		self::assertEquals("6T", $sut->get());
	}

	public function testDeeperDynamicPages_getByName():void {
		$assembly = new Assembly();
		$assembly->add("page/shop/@category/@@itemName.php");
		$sut = new DynamicPath("/shop/OnePlus/6T/64GB", $assembly);
		self::assertEquals("6T", $sut->get("itemName"));
	}

	public function testDeeperDynamicPages_getExtra():void {
		$assembly = new Assembly();
		$assembly->add("page/shop/@category/@@itemName.php");
		$sut = new DynamicPath("/shop/OnePlus/6T/64GB", $assembly);
		self::assertEquals("64GB", $sut->getExtra());
	}

	public function testDeeperDynamicPages_getExtraMultiple():void {
		$assembly = new Assembly();
		$assembly->add("page/shop/@category/@@itemName.php");
		$sut = new DynamicPath("/shop/OnePlus/6T/64GB/refurbished", $assembly);
		self::assertEquals("64GB/refurbished", $sut->getExtra());
	}

	public function testDeeperDynamicPages_getExtraMultiple_indexed():void {
		$assembly = new Assembly();
		$assembly->add("page/shop/@category/@@itemName.php");
		$sut = new DynamicPath("/shop/OnePlus/6T/64GB/refurbished", $assembly);
		self::assertEquals("64GB", $sut->getExtra()->getIndex(0));
	}
}
