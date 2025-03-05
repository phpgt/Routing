<?php
namespace Gt\Routing\Test\LogicStream;

use Gt\Routing\LogicStream\LogicStreamWrapper;
use Gt\Routing\RoutingException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LogicStreamWrapperTest extends TestCase {
	public function testFileDoesNotExist():void {
		$sut = new LogicStreamWrapper();
		self::expectException(RuntimeException::class);
		self::expectExceptionMessage("Failed to open stream: No such file or directory");
		$sut->stream_open("/does/not/exist");
	}

	public function testStreamRead_withNamespace():void {
		stream_wrapper_register("gt-routing-test", LogicStreamWrapper::class);
		$sourcePath = "TmpFiles/" . uniqid() . ".php";
		$source = <<<PHP
		<?php
		
		namespace Example;

		class Test {
		}
		PHP;
		if(!is_dir(dirname($sourcePath))) {
			mkdir(dirname($sourcePath), recursive: true);
		}

		file_put_contents($sourcePath, $source);

		$actualContents = file_get_contents("gt-routing-test://$sourcePath");
		unlink($sourcePath);
		rmdir(dirname($sourcePath));
		self::assertSame($source, $actualContents);
	}

	public function testStreamRead_withoutNamespace():void {
		stream_wrapper_register("gt-routing-test", LogicStreamWrapper::class);
		$uniqid = uniqid();
		$sourcePath = "TmpFiles/$uniqid.php";
		$source = <<<PHP
		<?php
		
		function example() {
			// This is just an example that should be wrapped in a class.
		}
		PHP;
		if(!is_dir($sourcePath)) {
			mkdir(dirname($sourcePath), recursive: true);
		}
		file_put_contents($sourcePath, $source);

		$actualContents = file_get_contents("gt-routing-test://$sourcePath");

		unlink($sourcePath);
		rmdir(dirname($sourcePath));

		$expectedContents = <<<PHP
		<?php
		
		namespace Gt\AppLogic\TmpFiles\\${uniqid}_php;	function example() {
			// This is just an example that should be wrapped in a class.
		}
		PHP;


		self::assertSame($expectedContents, $actualContents);
	}

	public function testStreamRead_withComment():void {
		stream_wrapper_register("gt-routing-test", LogicStreamWrapper::class);
		$uniqid = uniqid();
		$sourcePath = "TmpFiles/$uniqid.php";
		$source = <<<PHP
		<?php
		/**
		* This is an example docblock comment!
		*/
		function example() {
			// This is just an example that should be wrapped in a class.
		}
		PHP;
		if(!is_dir($sourcePath)) {
			mkdir(dirname($sourcePath), recursive: true);
		}
		file_put_contents($sourcePath, $source);

		$actualContents = file_get_contents("gt-routing-test://$sourcePath");

		unlink($sourcePath);
		rmdir(dirname($sourcePath));

		$expectedContents = <<<PHP
		<?php
		/**
		* This is an example docblock comment!
		*/
		namespace Gt\AppLogic\TmpFiles\\${uniqid}_php;	function example() {
			// This is just an example that should be wrapped in a class.
		}
		PHP;

		self::assertSame($expectedContents, $actualContents);
	}

	public function testStreamRead_notPhp():void {
		stream_wrapper_register("gt-routing-test", LogicStreamWrapper::class);
		$uniqid = uniqid();
		$sourcePath = "TmpFiles/$uniqid.php";
		$source = <<<PHP
		/**
		* This is a PHP file that doesn't start with the opening tags.
		*/
		<h1>Hello, World!</h1>
		PHP;
		if(!is_dir($sourcePath)) {
			mkdir(dirname($sourcePath), recursive: true);
		}
		file_put_contents($sourcePath, $source);

		$exception = null;
		try {
			file_get_contents("gt-routing-test://$sourcePath");
		}
		catch(RoutingException $exception) {}

		unlink($sourcePath);
		rmdir(dirname($sourcePath));

		self::assertInstanceOf(RoutingException::class, $exception);
		self::assertStringContainsString("Logic file at TmpFiles/$uniqid.php must start by opening a PHP tag", $exception->getMessage());
	}
}
