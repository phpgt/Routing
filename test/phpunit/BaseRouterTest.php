<?php
namespace Gt\Routing\Test;

use Exception;
use Gt\Config\ConfigSection;
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
use Gt\Routing\Assembly;
use Gt\Routing\Method\Any;
use Gt\Routing\Method\Get;
use Gt\Routing\Method\Put;
use Gt\Routing\BaseRouter;
use Gt\Routing\NotYetRoutedException;
use Gt\Routing\Redirects;
use JetBrains\PhpStorm\Deprecated;
use PHPUnit\Framework\TestCase;
use Throwable;

class BaseRouterTest extends TestCase {
	public function testHandleRedirects_none():void {
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);

		$redirects = self::createMock(Redirects::class);
		$sut = new class extends BaseRouter {};

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

		$sut = new class extends BaseRouter {};
		self::expectException(HttpPermanentRedirect::class);
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
		$sut = new class extends BaseRouter {};
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
		$request->method("getHeaderLine")
			->with("accept")
			->willReturn("text/plain");

		$sut = new class extends BaseRouter {
			#[Any]
			public function exampleRoute():void {
				throw new HttpNotFound();
			}
		};
		self::expectException(HttpNotFound::class);
		$sut->route($request);
	}

	public function testRoute_matchAny_noExceptionThrown():void {
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/nothing");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);
		$request->method("getMethod")->willReturn("GET");
		$request->method("getHeaderLine")
			->with("accept")
			->willReturn("text/plain");

		$exception = null;
		try {
			$sut = new class extends BaseRouter {
				#[Any]
				public function exampleRoute():void {
					// No exception here!
				}
			};
			$sut->route($request);
		}
		catch(Throwable $exception) {}

		self::assertNull($exception);
	}

	public function testRoute_matchHttpMethod():void {
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/something");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);
		$request->method("getMethod")->willReturn("PUT");
		$request->method("getHeaderLine")
			->with("accept")
			->willReturn("text/plain");

		$sut = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Get(path: "/something")]
			public function thisShouldNotMatch():void {
				throw new HttpNotFound();
			}

			#[Put(path: "/something")]
			public function thisShouldMatch():void {
				throw new Exception("Match!");
			}
		};

		self::expectExceptionMessage("Match!");
		$sut->route($request);
	}

	public function testRoute_matchPath():void {
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/something");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);
		$request->method("getMethod")->willReturn("GET");
		$request->method("getHeaderLine")
			->with("accept")
			->willReturn("text/plain");

		$sut = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Any(path: "/nothing")]
			public function thisShouldNotMatch():void {
				throw new HttpNotFound();
			}

			#[Any(path: "/something")]
			public function thisShouldMatch():void {
				throw new Exception("Match!");
			}
		};

		self::expectExceptionMessage("Match!");
		$sut->route($request);
	}

	public function testRoute_matchPath_multipleAttributes():void {
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/something");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);
		$request->method("getMethod")->willReturn("GET");
		$request->method("getHeaderLine")
			->with("accept")
			->willReturn("text/plain");

		$sut = new class extends BaseRouter {
			#[Deprecated(reason: "Just testing", replacement: "Nothing")]
			#[Any(path: "/something")]
			public function thisShouldMatch():void {
				throw new Exception("Match!");
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

		$sut = new class extends BaseRouter {
// Notice how we're using the default Firefox accept header here, and that it
// contains "application/xml". It has a quality of 0.9, so should not be
// preferred over the RouterCallback below.
			/** @noinspection PhpUnused */
			#[Any(path: "/something", accept: "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8")]
			public function thisShouldNotMatch():void {
				throw new HttpNotFound();
			}

			/** @noinspection PhpUnused */
			#[Any(path: "/something", accept: "fake/nothing")]
			public function thisShouldNotMatchBecauseItsNothing():void {
				throw new HttpNotFound();
			}

			/** @noinspection PhpUnused */
			#[Any(path: "/something", accept: "application/json,application/xml")]
			public function thisShouldMatch():void {
				throw new Exception("Match!");
			}

			/** @noinspection PhpUnused */
			#[Any(path: "/something", accept: "application/xhtml+xml,application/xml;q=0.8")]
			public function thisShouldNotMatchBecauseLessQuality():void {
				throw new HttpNotFound();
			}
		};
		self::expectExceptionMessage("Match!");
		$sut->route($request);
	}

	public function testRoute_matchesFirstRouteIfStarAccept():void {
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/something");
		$request = self::createMock(Request::class);
		$request->method("getUri")->willReturn($uri);
		$request->method("getMethod")->willReturn("GET");
		$request->method("getHeaderLine")
			->with("accept")
			->willReturn("*/*");

		$sut = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Any(path: "/something", accept: "text/html")]
			public function thisShouldNotMatch():void {
				throw new Exception("Route 1");
			}

			/** @noinspection PhpUnused */
			#[Any(path: "/something", accept: "fake/nothing")]
			public function thisShouldNotMatchBecauseItsNothing():void {
				throw new Exception("Route 2");
			}

			/** @noinspection PhpUnused */
			#[Any(path: "/something", accept: "application/json,application/xml")]
			public function thisShouldMatch():void {
				throw new Exception("Route 3");
			}

			/** @noinspection PhpUnused */
			#[Any(path: "/something", accept: "application/xhtml+xml,application/xml;q=0.8")]
			public function thisShouldNotMatchBecauseLessQuality():void {
				throw new Exception("Route 4");
			}
		};
		self::expectExceptionMessage("Route 1");
		$sut->route($request);
	}

	public function testGetViewClass_null():void {
		$sut = (new class extends BaseRouter {});
		self::assertNull($sut->getViewClass());
	}

	public function testGetViewClass():void {
		$sut = (new class extends BaseRouter {});
		$viewClass = "TEST";
		$sut->setViewClass($viewClass);
		self::assertSame($viewClass, $sut->getViewClass());
	}

	public function testGetLogicAssembly_notYetRouted():void {
		$logicAssembly = self::createMock(Assembly::class);
		$sut = (new class(logicAssembly: $logicAssembly) extends BaseRouter {});
		self::expectException(NotYetRoutedException::class);
		$sut->getLogicAssembly();
	}

	public function testGetLogicAssembly():void {
		$logicAssembly = self::createMock(Assembly::class);
		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/");
		$request = self::createMock(Request::class);
		$request->method("getMethod")->willReturn("GET");
		$request->method("getUri")->willReturn($uri);
		$request->method("getHeaderLine")->willReturn("test/example");
		$sut = (new class(logicAssembly: $logicAssembly) extends BaseRouter {
			#[Any(name: "api-route", accept: "test/example")]
			public function thisShouldBeRouted():void {
			}
		});
		$sut->route($request);
		self::assertSame($logicAssembly, $sut->getLogicAssembly());
	}#

	public function testGetViewAssembly():void {
		$viewAssembly = self::createMock(Assembly::class);
		$sut = (new class(viewAssembly: $viewAssembly) extends BaseRouter {});
		self::assertSame($viewAssembly, $sut->getViewAssembly());
	}

	public function testAddToViewAssembly():void {
		$viewAssembly = self::createMock(Assembly::class);
		$viewAssembly->expects(self::once())
			->method("add")
			->with("example/view/path");
		$sut = (new class(viewAssembly: $viewAssembly) extends BaseRouter {
			#[Any(name: "api-route", accept: "test/example")]
			public function thisShouldBeRouted():void {
				$this->addToViewAssembly("example/view/path");
			}
		});

		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/");
		$request = self::createMock(Request::class);
		$request->method("getMethod")->willReturn("GET");
		$request->method("getUri")->willReturn($uri);
		$request->method("getHeaderLine")->willReturn("test/example");
		$sut->route($request);
	}

	public function testAddToLogicAssembly():void {
		$logicAssembly = self::createMock(Assembly::class);
		$logicAssembly->expects(self::once())
			->method("add")
			->with("example/logic/path");
		$sut = (new class(logicAssembly: $logicAssembly) extends BaseRouter {
			#[Any(name: "api-route", accept: "test/example")]
			public function thisShouldBeRouted():void {
				$this->addToLogicAssembly("example/logic/path");
			}
		});

		$uri = self::createMock(Uri::class);
		$uri->method("getPath")->willReturn("/");
		$request = self::createMock(Request::class);
		$request->method("getMethod")->willReturn("GET");
		$request->method("getUri")->willReturn($uri);
		$request->method("getHeaderLine")->willReturn("test/example");
		$sut->route($request);
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

		$sut = new class($config) extends BaseRouter {};

		self::expectException($redirectClass);
		self::expectExceptionMessage("/new-path");
		$sut->handleRedirects($redirects, $request);
	}

	public static function data_redirectCode():array {
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
