<?php
namespace Gt\Routing;

use ReflectionAttribute;
use ReflectionMethod;
use Gt\Routing\Method\HttpRouteMethod;
use Gt\Routing\Method\Any;
use Gt\Routing\Method\Get;
use Gt\Routing\Method\Post;

class RouterCallback {
	public function __construct(
		private ReflectionMethod $method,
		private ReflectionAttribute $attribute
	) {
	}

	public function call(Router $router):void {
		$this->method->invoke($router);
	}

	public function isAllowedMethod(string $requestMethod):bool {
		$args = $this->attribute->getArguments();

		$allowedMethods = match($this->attribute->getName()) {
			Any::class => HttpRouteMethod::METHODS_ALL,
			Get::class => [HttpRouteMethod::METHOD_GET],
			Post::class => [HttpRouteMethod::METHOD_POST],
// TODO: The other methods in HTTP.
			default => $args["methods"] ?? [],
		};
		$allowedMethods = array_map(
			"strtoupper",
			$allowedMethods
		);

		return in_array($requestMethod, $allowedMethods);
	}

	public function matchesPath(string $requestPath):bool {
		$path = $this->attribute->getArguments()["path"] ?? null;
		if(is_null($path)) {
			return true;
		}

		return $path === $requestPath;
	}
}
