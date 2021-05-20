<?php
namespace Gt\Routing;

use Negotiation\Negotiator;
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
		$methodsArgument = $this->attribute->getArguments()["methods"] ?? [];

		$allowedMethods = match($this->attribute->getName()) {
			Any::class => HttpRouteMethod::METHODS_ALL,
			Get::class => [HttpRouteMethod::METHOD_GET],
			Post::class => [HttpRouteMethod::METHOD_POST],
// TODO: The other methods in HTTP.
			default => $methodsArgument,
		};
		$allowedMethods = array_map(
			"strtoupper",
			$allowedMethods
		);

		return in_array($requestMethod, $allowedMethods);
	}

	public function matchesPath(string $requestPath):bool {
		$pathArgument = $this->attribute->getArguments()["path"] ?? null;
		if(is_null($pathArgument)) {
			return true;
		}

		return $pathArgument === $requestPath;
	}

	public function matchesAccept(string $acceptHeader):bool {
		$acceptArgument = $this->attribute->getArguments()["accept"] ?? null;
		if(is_null($acceptArgument)) {
			return true;
		}

		$acceptedTypes = explode(",", $acceptArgument);
		$negotiator = new Negotiator();
		$best = $negotiator->getBest($acceptHeader, $acceptedTypes);
		if(!$best) {
			return false;
		}

		return true;
	}

	/** @return string[] */
	public function getAcceptedTypes():array {
		$acceptArgument = $this->attribute->getArguments()["accept"] ?? null;
		if(is_null($acceptArgument)) {
			return ["*/*"];
		}

		return explode(",", $acceptArgument);
	}
}
