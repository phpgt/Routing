<?php
namespace Gt\Routing\Test;

use Gt\Config\ConfigSection;
use Gt\Http\Header\RequestHeaders;
use Gt\Http\Request;
use Gt\Http\ResponseStatusException\ClientError\HttpNotAcceptable;
use Gt\Http\ResponseStatusException\ClientError\HttpNotFound;
use Gt\Http\ResponseStatusException\Redirection\HttpFound;
use Gt\Http\ResponseStatusException\Redirection\HttpMovedPermanently;
use Gt\Http\ResponseStatusException\Redirection\HttpMultipleChoices;
use Gt\Http\ResponseStatusException\Redirection\HttpNotModified;
use Gt\Http\ResponseStatusException\Redirection\HttpPermanentRedirect;
use Gt\Http\ResponseStatusException\Redirection\HttpSeeOther;
use Gt\Http\ResponseStatusException\Redirection\HttpTemporaryRedirect;
use Gt\Http\Uri;
use Gt\Routing\Method\Any;
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

	/**
	 * Usually, the HttpNotAcceptable (406) response code should never be
	 * seen by the user, because when content negotiation fails, we should
	 * fall back to a default content-type - but this test is covering the
	 * occasion when the developer hasn't added ANY routes yet.
	 */
	public function testRoute_failContentNegotiation():void {
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/nothing");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);
		$sut = new class extends Router {};
		self::expectException(HttpNotAcceptable::class);
		$sut->route($request);
	}

	/**
	 * Here we have a RouterCallback that will match all requests - but it
	 * will throw an HttpNotFound once invoked.
	 */
	public function testRoute_matchAny():void {
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/nothing");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);
		$request->method("getMethod")->willReturn("GET");

		$sut = new class extends Router {
			#[Any]
			public function exampleRoute():void {
				throw new HttpNotFound();
			}
		};
		self::expectException(HttpNotFound::class);
		$sut->route($request);
	}

	public function testRoute_matchPath():void {
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/something");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);
		$request->method("getMethod")->willReturn("GET");

		$sut = new class extends Router {
			#[Any(path: "/nothing")]
			public function thisShouldNotMatch():void {
				throw new HttpNotFound();
			}

			#[Any(path: "/something")]
			public function thisShouldMatch():void {
				throw new \Exception("Match!");
			}
		};

		self::expectExceptionMessage("Match!");
		$sut->route($request);
	}

	/**
	 * Here we have two RouterCallbacks that both match the same path, but
	 * we will use content negotiation to determine which callback is
	 * regarded as "best".
	 */
	public function testRoute_matchAccept():void {
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/something");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);
		$request->method("getMethod")->willReturn("GET");
		$request->method("getHeaderLine")
			->with("accept")
			->willReturn("application/xml");

		$sut = new class extends Router {
// Notice how we're using the default Firefox accept header here, and that it
// contains "application/xml". It has a quality of 0.9, so should not be
// preferred over the RouterCallback below.
			#[Any(path: "/something", accept: "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8")]
			public function thisShouldNotMatch():void {
				throw new HttpNotFound();
			}

			#[Any(path: "/something", accept: "application/json,application/xml")]
			public function thisShouldMatch():void {
				throw new \Exception("Match!");
			}
		};
		self::expectExceptionMessage("Match!");
		$sut->route($request);
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
