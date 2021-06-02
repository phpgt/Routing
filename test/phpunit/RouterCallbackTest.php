<?php
namespace Gt\Routing\Test;

use DateTime;
use DateTimeInterface;
use Gt\Http\Request;
use Gt\Routing\HttpRoute;
use Gt\Routing\Method\Any;
use Gt\Routing\Method\Connect;
use Gt\Routing\Method\Delete;
use Gt\Routing\Method\Get;
use Gt\Routing\Method\Head;
use Gt\Routing\Method\HttpRouteMethod;
use Gt\Routing\Method\Options;
use Gt\Routing\Method\Patch;
use Gt\Routing\Method\Post;
use Gt\Routing\Method\Put;
use Gt\Routing\Method\Trace;
use Gt\Routing\BaseRouter;
use Gt\Routing\RouterCallback;
use Gt\ServiceContainer\Container;
use Gt\ServiceContainer\Injector;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;
use stdClass;

class RouterCallbackTest extends TestCase {
	public function testCall():void {
		$routerClass = new class extends BaseRouter {
			public int $exampleMethodCallCount = 0;

			/** @noinspection PhpUnused */
			#[Any]
			public function exampleMethodWithAttribute() {
				$this->exampleMethodCallCount++;
			}
		};

		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("exampleMethodWithAttribute");
		$attribute = $method->getAttributes()[0];

		$sut = new RouterCallback($method, $attribute);
		$sut->call($routerClass);
		self::assertSame(1, $routerClass->exampleMethodCallCount);
	}

	public function testCall_parameterInjection():void {
		$routerClass = new class extends BaseRouter {
			public array $exampleMethodCalls = [];

			#[Any]
			public function exampleMethod(RequestInterface $request) {
				array_push($this->exampleMethodCalls, $request);
			}
		};

		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("exampleMethod");
		$attribute = $method->getAttributes()[0];

		$request = self::createMock(Request::class);
		$container = self::createMock(Container::class);
		$container->method("get")
			->with(RequestInterface::class)
			->willReturn($request);

		$sut = new RouterCallback($method, $attribute, $container);
		$sut->call($routerClass);
		self::assertCount(1, $routerClass->exampleMethodCalls);
	}

	public function testIsAllowedMethod_any():void {
		$routerClass = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Any]
			public function example() {}
		};
		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("example");
		$attribute = $method->getAttributes()[0];

		$sut = new RouterCallback($method, $attribute);
		foreach(HttpRouteMethod::METHODS_ALL as $httpMethodName) {
			self::assertTrue($sut->isAllowedMethod($httpMethodName));
		}
	}

	public function testIsAllowedMethod_methodsListed():void {
		$routerClass = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[HttpRoute(methods: ["GET", "HEAD", "OPTIONS"])]
			public function example() {}
		};
		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("example");
		$attribute = $method->getAttributes()[0];

		$sut = new RouterCallback($method, $attribute);
		foreach(HttpRouteMethod::METHODS_ALL as $httpMethodName) {
			if($httpMethodName === "GET"
			|| $httpMethodName === "HEAD"
			|| $httpMethodName === "OPTIONS") {
				self::assertTrue($sut->isAllowedMethod($httpMethodName));
			}
			else {
				self::assertFalse($sut->isAllowedMethod($httpMethodName));
			}
		}
	}

	public function testIsAllowedMethod_connect():void {
		$routerClass = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Connect]
			public function example() {}
		};
		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("example");
		$attribute = $method->getAttributes()[0];

		$sut = new RouterCallback($method, $attribute);
		foreach(HttpRouteMethod::METHODS_ALL as $httpMethodName) {
			if($httpMethodName === "CONNECT") {
				self::assertTrue($sut->isAllowedMethod($httpMethodName));
			}
			else {
				self::assertFalse($sut->isAllowedMethod($httpMethodName));
			}
		}
	}

	public function testIsAllowedMethod_delete():void {
		$routerClass = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Delete]
			public function example() {}
		};
		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("example");
		$attribute = $method->getAttributes()[0];

		$sut = new RouterCallback($method, $attribute);
		foreach(HttpRouteMethod::METHODS_ALL as $httpMethodName) {
			if($httpMethodName === "DELETE") {
				self::assertTrue($sut->isAllowedMethod($httpMethodName));
			}
			else {
				self::assertFalse($sut->isAllowedMethod($httpMethodName));
			}
		}
	}

	public function testIsAllowedMethod_get():void {
		$routerClass = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Get]
			public function example() {}
		};
		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("example");
		$attribute = $method->getAttributes()[0];

		$sut = new RouterCallback($method, $attribute);
		foreach(HttpRouteMethod::METHODS_ALL as $httpMethodName) {
			if($httpMethodName === "GET") {
				self::assertTrue($sut->isAllowedMethod($httpMethodName));
			}
			else {
				self::assertFalse($sut->isAllowedMethod($httpMethodName));
			}
		}
	}

	public function testIsAllowedMethod_head():void {
		$routerClass = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Head]
			public function example() {}
		};
		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("example");
		$attribute = $method->getAttributes()[0];

		$sut = new RouterCallback($method, $attribute);
		foreach(HttpRouteMethod::METHODS_ALL as $httpMethodName) {
			if($httpMethodName === "HEAD") {
				self::assertTrue($sut->isAllowedMethod($httpMethodName));
			}
			else {
				self::assertFalse($sut->isAllowedMethod($httpMethodName));
			}
		}
	}

	public function testIsAllowedMethod_options():void {
		$routerClass = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Options]
			public function example() {}
		};
		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("example");
		$attribute = $method->getAttributes()[0];

		$sut = new RouterCallback($method, $attribute);
		foreach(HttpRouteMethod::METHODS_ALL as $httpMethodName) {
			if($httpMethodName === "OPTIONS") {
				self::assertTrue($sut->isAllowedMethod($httpMethodName));
			}
			else {
				self::assertFalse($sut->isAllowedMethod($httpMethodName));
			}
		}
	}

	public function testIsAllowedMethod_patch():void {
		$routerClass = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Patch]
			public function example() {}
		};
		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("example");
		$attribute = $method->getAttributes()[0];

		$sut = new RouterCallback($method, $attribute);
		foreach(HttpRouteMethod::METHODS_ALL as $httpMethodName) {
			if($httpMethodName === "PATCH") {
				self::assertTrue($sut->isAllowedMethod($httpMethodName));
			}
			else {
				self::assertFalse($sut->isAllowedMethod($httpMethodName));
			}
		}
	}

	public function testIsAllowedMethod_post():void {
		$routerClass = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Post]
			public function example() {}
		};
		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("example");
		$attribute = $method->getAttributes()[0];

		$sut = new RouterCallback($method, $attribute);
		foreach(HttpRouteMethod::METHODS_ALL as $httpMethodName) {
			if($httpMethodName === "POST") {
				self::assertTrue($sut->isAllowedMethod($httpMethodName));
			}
			else {
				self::assertFalse($sut->isAllowedMethod($httpMethodName));
			}
		}
	}

	public function testIsAllowedMethod_put():void {
		$routerClass = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Put]
			public function example() {}
		};
		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("example");
		$attribute = $method->getAttributes()[0];

		$sut = new RouterCallback($method, $attribute);
		foreach(HttpRouteMethod::METHODS_ALL as $httpMethodName) {
			if($httpMethodName === "PUT") {
				self::assertTrue($sut->isAllowedMethod($httpMethodName));
			}
			else {
				self::assertFalse($sut->isAllowedMethod($httpMethodName));
			}
		}
	}

	public function testIsAllowedMethod_trace():void {
		$routerClass = new class extends BaseRouter {
			/** @noinspection PhpUnused */
			#[Trace]
			public function example() {}
		};
		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("example");
		$attribute = $method->getAttributes()[0];

		$sut = new RouterCallback($method, $attribute);
		foreach(HttpRouteMethod::METHODS_ALL as $httpMethodName) {
			if($httpMethodName === "TRACE") {
				self::assertTrue($sut->isAllowedMethod($httpMethodName));
			}
			else {
				self::assertFalse($sut->isAllowedMethod($httpMethodName));
			}
		}
	}

	/**
	 * This test mimics the way WebEngine's inbuilt router will call the
	 * go/do logic functions - these functions are written by the developer,
	 * so WebEngine will never know beforehand what parameters there are
	 * going to be.
	 */
	public function testCallbackCanCallOtherCallbacksAndInjectServices():void {
		$logicClass = new class extends StdClass {
			public array $exampleGoCalls = [];

			public function go(DateTimeInterface $date) {
				array_push($this->exampleGoCalls, $date);
			}
		};

		$routerClass = new class extends BaseRouter {
			#[Any]
			public function processWebEngineRequest(Injector $injector, StdClass $logic) {
				$injector->invoke($logic, "go");
			}
		};

		$refClass = new ReflectionClass($routerClass);
		$method = $refClass->getMethod("processWebEngineRequest");
		$attribute = $method->getAttributes()[0];

// TODO: Add injected parameter into container. Add injector back itno container
// then somehow make processWebEngineRequest call the go function.
		$container = new Container();
		$injector = new Injector($container);

		$now = new DateTime();
		$container->set(Injector::class, $injector);
		$container->set(StdClass::class, $logicClass);
		$container->set(DateTime::class, $now);

		$sut = new RouterCallback($method, $attribute, $container, $injector);
		$sut->call($routerClass);

		self::assertCount(1, $logicClass->exampleGoCalls);
		self::assertSame($now, $logicClass->exampleGoCalls[0]);
	}
}
