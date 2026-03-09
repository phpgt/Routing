<?php
namespace GT\Routing\Test\LogicStream;

use DateTime;
use GT\Routing\LogicStream\LogicStreamWrapper;
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

		$namespaceLine = "namespace GT\AppLogic\\\\tmp\\phpgt_routing_example_$uniqid;";
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

	public function testStreamOpen_runtimeCall_UnqualifiedBuiltInClass():void {
		$uniqid = uniqid();
		$tmpFile = "phpgt-routing-example-" . $uniqid;
		$logicPath = "phpgt-test://$tmpFile";
		$contents = <<<PHP
		<?php
		function example():DateTime {
			return new DateTime();
		}
		PHP;
		$cwd = getcwd();
		chdir(sys_get_temp_dir());
		file_put_contents($tmpFile, $contents);

		try {
			$sut = new LogicStreamWrapper();
			$sut->stream_open($logicPath);
			$wrappedContents = $sut->stream_read(4096);

			eval(substr($wrappedContents, strlen("<?php")));
			$namespace = preg_match('/namespace\\s+([^;]+);/', $wrappedContents, $matches)
				? $matches[1]
				: "";
			$callable = $namespace . "\\example";

			self::assertInstanceOf(DateTime::class, $callable());
		}
		finally {
			if($cwd !== false) {
				chdir($cwd);
			}
			unlink(sys_get_temp_dir() . "/" . $tmpFile);
		}
	}
}
