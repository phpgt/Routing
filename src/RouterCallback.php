<?php
namespace Gt\Routing;

use Gt\Routing\Method\Connect;
use Gt\Routing\Method\Delete;
use Gt\Routing\Method\Head;
use Gt\Routing\Method\Options;
use Gt\Routing\Method\Patch;
use Gt\Routing\Method\Put;
use Gt\Routing\Method\Trace;
use Gt\ServiceContainer\Container;
use Gt\ServiceContainer\Injector;
use Negotiation\Negotiator;
use ReflectionAttribute;
use ReflectionMethod;
use Gt\Routing\Method\HttpRouteMethod;
use Gt\Routing\Method\Any;
use Gt\Routing\Method\Get;
use Gt\Routing\Method\Post;

class RouterCallback {
	private Container $container;
	private Injector $injector;

	public function __construct(
		private ReflectionMethod $method,
		private ReflectionAttribute $attribute,
		?Container $container = null,
		?Injector $injector = null,
	) {
		$this->container = $container ?? new Container();
		$this->injector = $injector ?? new Injector($this->container);
	}

	public function call(Router $router):void {
		$this->injector->invoke($router, $this->method->getName());
	}

	public function isAllowedMethod(string $requestMethod):bool {
		$methodsArgument = $this->attribute->getArguments()["methods"] ?? [];

		$allowedMethods = match($this->attribute->getName()) {
			Any::class => HttpRouteMethod::METHODS_ALL,
			Connect::class => [HttpRouteMethod::METHOD_CONNECT],
			Delete::class => [HttpRouteMethod::METHOD_DELETE],
			Get::class => [HttpRouteMethod::METHOD_GET],
			Head::class => [HttpRouteMethod::METHOD_HEAD],
			Options::class => [HttpRouteMethod::METHOD_OPTIONS],
			Patch::class => [HttpRouteMethod::METHOD_PATCH],
			Post::class => [HttpRouteMethod::METHOD_POST],
			Put::class => [HttpRouteMethod::METHOD_PUT],
			Trace::class => [HttpRouteMethod::METHOD_TRACE],
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
