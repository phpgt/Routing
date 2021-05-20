<?php
namespace Gt\Routing;

use Gt\Config\ConfigSection;
use Gt\Http\ResponseStatusException\ClientError\HttpNotAcceptable;
use Gt\Http\ResponseStatusException\ClientError\HttpNotFound;
use Psr\Http\Message\RequestInterface;
use Gt\Http\ResponseStatusException\Redirection\HttpFound;
use Gt\Http\ResponseStatusException\Redirection\HttpMovedPermanently;
use Gt\Http\ResponseStatusException\Redirection\HttpMultipleChoices;
use Gt\Http\ResponseStatusException\Redirection\HttpNotModified;
use Gt\Http\ResponseStatusException\Redirection\HttpPermanentRedirect;
use Gt\Http\ResponseStatusException\Redirection\HttpSeeOther;
use Gt\Http\ResponseStatusException\Redirection\HttpTemporaryRedirect;
use ReflectionClass;

abstract class Router {
	private Assembly $viewAssembly;
	private Assembly $logicAssembly;

	public function __construct(
		protected ?ConfigSection $routerConfig = null,
		Assembly $viewAssembly = null,
		Assembly $logicAssembly = null,
	) {
		$this->viewAssembly = $viewAssembly ?? new Assembly();
		$this->logicAssembly = $logicAssembly ?? new Assembly();
	}

	public function handleRedirects(
		Redirects $redirects,
		RequestInterface $request
	):void {
		$responseCode = $this->routerConfig?->getInt("redirect_response_code");

		$responseClass = match($responseCode) {
			300 => HttpMultipleChoices::class,
			301 => HttpMovedPermanently::class,
			302 => HttpFound::class,
			303 => HttpSeeOther::class,
			304 => HttpNotModified::class,
			307 => HttpTemporaryRedirect::class,
			default => HttpPermanentRedirect::class
		};

		$uri = $request->getUri()->getPath();

		foreach($redirects as $old => $new) {
			if($old === $uri) {
				throw new $responseClass($new);
			}
		}
	}

	public function route(RequestInterface $request):void {
		/** @var array<RouterCallback> $validCallbackArray */
		$validCallbackArray = [];
// Find all callbacks that match the current request, filling the valid callback
// array. Then, the "best" callback will be matched using content negotiation.
		foreach($this->reflectRouterCallbacks() as $routerCallback) {
			if(!$routerCallback->isAllowedMethod($request->getMethod())) {
				continue;
			}
			if(!$routerCallback->matchesPath($request->getUri()->getPath())) {
				continue;
			}

			array_push($validCallbackArray, $routerCallback);
		}

		$matchingCallbackArray = $this->filterMatchingRouterCallbacks(
			$request,
			$validCallbackArray
		);

		if(empty($matchingCallbackArray)) {
			throw new HttpNotAcceptable();
		}

		$bestRouterCallback = $matchingCallbackArray[0];

// TODO: Call with the DI, so the callback can receive all the required params.
		$bestRouterCallback->call($this);
	}

	/**
	 * Use Reflection to find all callbacks on $this that have an HttpRoute
	 * Attribute associated, whether or not the route is valid.
	 * @return array<RouterCallback>
	 */
	private function reflectRouterCallbacks():array {
		/** @var array<RouterCallback> $routerCallbackArray */
		$routerCallbackArray = [];

		$class = new ReflectionClass($this);
		foreach($class->getMethods() as $method) {
			foreach($method->getAttributes() as $attribute) {
				$name = $attribute->getName();

				if(!is_a($name, HttpRoute::class, true)) {
					continue;
				}

				array_push(
					$routerCallbackArray,
					new RouterCallback($method, $attribute)
				);
			}
		}

		return $routerCallbackArray;
	}

	/**
	 * @param array<RouterCallback> $callbackArray
	 * @return array<RouterCallback>
	 */
	private function filterMatchingRouterCallbacks(
		RequestInterface $request,
		array $callbackArray
	):array {
		return $callbackArray;
	}
}
