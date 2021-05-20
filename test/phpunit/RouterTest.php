<?php
namespace Gt\Routing\Test;

use Gt\Config\ConfigSection;
use Gt\Http\Request;
use Gt\Http\ResponseStatusException\Redirection\HttpFound;
use Gt\Http\ResponseStatusException\Redirection\HttpMovedPermanently;
use Gt\Http\ResponseStatusException\Redirection\HttpMultipleChoices;
use Gt\Http\ResponseStatusException\Redirection\HttpNotModified;
use Gt\Http\ResponseStatusException\Redirection\HttpPermanentRedirect;
use Gt\Http\ResponseStatusException\Redirection\HttpSeeOther;
use Gt\Http\ResponseStatusException\Redirection\HttpTemporaryRedirect;
use Gt\Http\Uri;
use Gt\Routing\Router;
use Gt\Routing\Redirects;
use PHPUnit\Framework\TestCase;
use Throwable;

class RouterTest extends TestCase {
	public function testHandleRedirects_none():void {
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);

		$redirects = self::createMock(Redirects::class);
		$sut = new class extends Router {};

		$exception = null;
		try {
			$sut->handleRedirects($redirects, $request);
		}
		catch(Throwable $exception) {}
		self::assertNull($exception);
	}

	public function testHandleRedirects():void {
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/old-path");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);

		$redirects = self::createMock(Redirects::class);
		$redirects->method("current")
			->willReturn("/new-path");
		$redirects->method("key")
			->willReturn("/old-path");
		$redirects->method("valid")
			->willReturnOnConsecutiveCalls(true, false);

		$sut = new class extends Router {};
		self::expectException(HttpPermanentRedirect::class);
		self::expectExceptionMessage("/new-path");
		$sut->handleRedirects($redirects, $request);
	}

	/** @dataProvider data_redirectCode */
	public function testHandleRedirects_responseCodeFromConfig(
		int $code,
		string $redirectClass
	):void {
		$config = self::createMock(ConfigSection::class);
		$config->method("getInt")
			->with("redirect_response_code")
			->willReturnOnConsecutiveCalls($code);

		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/old-path");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);

		$redirects = self::createMock(Redirects::class);
		$redirects->method("current")
			->willReturn("/new-path");
		$redirects->method("key")
			->willReturn("/old-path");
		$redirects->method("valid")
			->willReturnOnConsecutiveCalls(true, false);

		$sut = new class($config) extends Router {};

		self::expectException($redirectClass);
		self::expectExceptionMessage("/new-path");
		$sut->handleRedirects($redirects, $request);
	}

	public function data_redirectCode():array {
		$responseCodes = [
			300 => HttpMultipleChoices::class,
			301 => HttpMovedPermanently::class,
			302 => HttpFound::class,
			303 => HttpSeeOther::class,
			304 => HttpNotModified::class,
			307 => HttpTemporaryRedirect::class,
			308 => HttpPermanentRedirect::class,
		];

		$data = [];
		foreach($responseCodes as $code => $class) {
			array_push($data, [$code, $class]);
		}

		return $data;
	}
}
