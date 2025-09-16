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
use Negotiation\Accept;
use Negotiation\Negotiator;
use ReflectionAttribute;
use ReflectionMethod;
use Gt\Routing\Method\HttpRouteMethod;
use Gt\Routing\Method\Any;
use Gt\Routing\Method\Get;
use Gt\Routing\Method\Post;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RouterCallback {
	private Container $container;
	private Injector $injector;
	private Negotiator $negotiator;

	/** @param ReflectionAttribute<HttpRouteMethod> $attribute */
	public function __construct(
		private ReflectionMethod $method,
		private ReflectionAttribute $attribute,
		?Container $container = null,
		?Injector $injector = null,
		?Negotiator $negotiator = null,
	) {
		$this->container = $container ?? new Container();
		$this->injector = $injector ?? new Injector($this->container);
		$this->negotiator = $negotiator ?? new Negotiator();
	}

	public function call(BaseRouter $router):void {
		$this->injector->invoke($router, $this->method->getName());
	}

	public function isAllowedMethod(string $requestMethod):bool {
		$allowedMethods = $this->getAllowedMethods();
		$allowedMethods = $this->normalizeMethods($allowedMethods);
		return $this->isMethodAllowed($requestMethod, $allowedMethods);
	}

	/** @return array<string> */
	private function getAllowedMethods():array {
		$methodsArgument = $this->attribute->getArguments()["methods"] ?? [];
		return $this->mapAttributeToMethods($this->attribute->getName(), $methodsArgument);
	}

	/**
	 * @param array<string> $methodsArgument
	 * @return array<string>
	 */
	private function mapAttributeToMethods(
		string $attributeName,
		array $methodsArgument,
	):array {
		$attributeMethodMap = $this->getAttributeMethodMap();
		return $attributeMethodMap[$attributeName] ?? $methodsArgument;
	}

	/** @return array<class-string, array<string>> */
	private function getAttributeMethodMap():array {
		return [
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
		];
	}

	/**
	 * @param array<string> $methods
	 * @return array<string>
	 */
	private function normalizeMethods(array $methods):array {
		return array_map("strtoupper", $methods);
	}

	/**
	 * @param array<string> $allowedMethods
	 */
	private function isMethodAllowed(
		string $requestMethod,
		array $allowedMethods,
	):bool {
		return in_array(strtoupper($requestMethod), $allowedMethods, true);
	}

	public function matchesPath(string $requestPath):bool {
		$pathArgument = $this->attribute->getArguments()["path"] ?? null;
		if(is_null($pathArgument)) {
			return true;
		}

		return $pathArgument === $requestPath;
	}

	public function matchesAccept(string $acceptHeader):bool {
		if(!$acceptHeader) {
			$acceptHeader = "*/*";
		}

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
	public function getAcceptedTypes(string $acceptHeader = ""):array {
		$acceptArgument = $this->attribute->getArguments()["accept"] ?? null;
		if(is_null($acceptArgument)) {
			return ["*/*"];
		}

		$acceptHeaderParts = explode(",", $acceptHeader);
		$acceptArgumentArray = explode(",", $acceptArgument);
		foreach($acceptArgumentArray as $i => $arg) {
			foreach($acceptHeaderParts as $acceptHeaderItem) {
				if(str_starts_with($acceptHeaderItem, $arg)) {
					$acceptArgumentArray[$i] = $acceptHeaderItem;
				}
			}
		}

		return $acceptArgumentArray;
	}

	public function getBestNegotiation(string $acceptHeader):?Accept {
		if(!$acceptHeader) {
			$acceptHeader = "*/*";
		}
		/** @var Accept $mediaType */
		$mediaType = $this->negotiator->getBest(
			$acceptHeader,
			$this->getAcceptedTypes($acceptHeader)
		);
		return $mediaType;
	}
}
