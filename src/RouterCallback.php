<?php
namespace Gt\Routing;

use Gt\Routing\Method\HttpMethodHandler;
use Gt\ServiceContainer\Container;
use Gt\ServiceContainer\Injector;
use Negotiation\Accept;
use Negotiation\Negotiator;
use ReflectionAttribute;
use ReflectionMethod;
use Gt\Routing\Method\HttpRouteMethod;

class RouterCallback {
	private Container $container;
	private Injector $injector;
	private ContentNegotiator $contentNegotiator;
	private HttpMethodHandler $httpMethodHandler;

	/** @param ReflectionAttribute<HttpRouteMethod> $attribute */
	public function __construct(
		private ReflectionMethod $method,
		private ReflectionAttribute $attribute,
		?Container $container = null,
		?Injector $injector = null,
		?ContentNegotiator $contentNegotiator = null,
		?HttpMethodHandler $httpMethodHandler = null,
	) {
		$this->container = $container ?? new Container();
		$this->injector = $injector ?? new Injector($this->container);
		$negotiator = new Negotiator();
		$this->contentNegotiator = $contentNegotiator ?? new ContentNegotiator($negotiator);
		$this->httpMethodHandler = $httpMethodHandler ?? new HttpMethodHandler();
	}

	public function call(BaseRouter $router):void {
		$this->injector->invoke($router, $this->method->getName());
	}

	public function isAllowedMethod(string $requestMethod):bool {
		$methodsArgument = $this->attribute->getArguments()["methods"] ?? [];
		return $this->httpMethodHandler->isAllowedMethod(
			$requestMethod,
			$this->attribute->getName(),
			$methodsArgument
		);
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
		return $this->contentNegotiator->matchesAccept($acceptHeader, $acceptArgument);
	}

	/** @return string[] */
	public function getAcceptedTypes(string $acceptHeader = ""):array {
		$acceptArgument = $this->attribute->getArguments()["accept"] ?? null;
		return $this->contentNegotiator->getAcceptedTypes($acceptHeader, $acceptArgument);
	}

	public function getBestNegotiation(string $acceptHeader):?Accept {
		$acceptArgument = $this->attribute->getArguments()["accept"] ?? null;
		return $this->contentNegotiator->getBestNegotiation($acceptHeader, $acceptArgument);
	}
}
