<?php
namespace Gt\Routing\Test\LogicStream;

use Gt\Routing\LogicStream\LogicStreamWrapper;
use PHPUnit\Framework\TestCase;

class LogicStreamWrapperTest extends TestCase {
	public function testStreamOpen_notPhpFile():void {
		$tmpFile = "/tmp/phpgt-routing-example-" . uniqid();
		$logicPath = "phpgt-test://$tmpFile";

		$contents = bin2hex(random_bytes(16));
		file_put_contents($tmpFile, $contents);

		$sut = new LogicStreamWrapper();
		self::expectExceptionMessage("Logic file at $tmpFile must start by opening a PHP tag.");
		$sut->stream_open($logicPath);
	}

	public function testStreamOpen():void {
		$uniqid = uniqid();
		$tmpFile = "/tmp/phpgt-routing-example-" . $uniqid;
		$logicPath = "phpgt-test://$tmpFile";

		$contents = "<?php\n" . bin2hex(random_bytes(16));
		file_put_contents($tmpFile, $contents);

		$sut = new LogicStreamWrapper();
		$sut->stream_open($logicPath);
		self::assertSame(0, $sut->stream_tell());

		$namespaceLine = "namespace Gt\\AppLogic\\\\tmp\\phpgt_routing_example_$uniqid;";
		$contentsWithNamespace = substr_replace($contents, "$namespaceLine\n", strpos($contents, "\n") + 1, 0);
		$contentsWithNamespace = substr_replace($contentsWithNamespace, "\t", strpos($contentsWithNamespace, "\n"), 1);

		self::assertSame($contentsWithNamespace, $sut->stream_read(1024));
	}

	public function testStreamRead():void {
		$uniqid = uniqid();
		$tmpFile = "/tmp/phpgt-routing-example-" . $uniqid;
		$logicPath = "phpgt-test://$tmpFile";

		$contents = <<<PHP
		<?php
		use Something;
		use SomethingElse;
		
		function example():void {
			// This is line 6
		}
		PHP;
		file_put_contents($tmpFile, $contents);
		
		$sut = new LogicStreamWrapper();
		$sut->stream_open($logicPath);
		$actualContents = $sut->stream_read(1024);

		self::assertStringContainsString(
			"// This is line 6",
			explode("\n", $actualContents)[5],
		);
	}
}