<?php
namespace Gt\Routing;

use Gt\Routing\Method\Any;
use Gt\Routing\Method\Connect;
use Gt\Routing\Method\Delete;
use Gt\Routing\Method\Get;
use Gt\Routing\Method\Head;
use Gt\Routing\Method\HttpMethodHandler;
use Gt\Routing\Method\Options;
use Gt\Routing\Method\Patch;
use Gt\Routing\Method\Post;
use Gt\Routing\Method\Put;
use Gt\Routing\Method\Trace;
use Gt\ServiceContainer\Container;
use Gt\ServiceContainer\Injector;
use Negotiation\Accept;
use Negotiation\Negotiator;
use ReflectionAttribute;
use ReflectionMethod;
use Gt\Routing\Method\HttpRouteMethod;


/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @phpcs:disable Generic.Metrics.CyclomaticComplexity
 */
class RouterCallback {
	private Container $container;
	private Injector $injector;
	private ContentNegotiator $contentNegotiator;
	/** @phpstan-ignore-next-line */
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

		$allowedMethods = match($this->attribute->getName()) {
			Any::class => HttpRoute::METHODS_ALL,
			Connect::class => [HttpRoute::METHOD_CONNECT],
			Delete::class => [HttpRoute::METHOD_DELETE],
			Get::class => [HttpRoute::METHOD_GET],
			Head::class => [HttpRoute::METHOD_HEAD],
			Options::class => [HttpRoute::METHOD_OPTIONS],
			Patch::class => [HttpRoute::METHOD_PATCH],
			Post::class => [HttpRoute::METHOD_POST],
			Put::class => [HttpRoute::METHOD_PUT],
			Trace::class => [HttpRoute::METHOD_TRACE],
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
