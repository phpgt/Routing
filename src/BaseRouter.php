<?php
namespace Gt\Routing;

use Gt\Config\ConfigSection;
use Gt\Http\ResponseStatusException\ClientError\HttpNotAcceptable;
use Gt\Routing\Redirect\RedirectExceptionFactory;
use Gt\ServiceContainer\Container;
use Gt\ServiceContainer\Injector;
use Negotiation\Accept;
use Psr\Http\Message\RequestInterface;
use ReflectionClass;

abstract class BaseRouter {
	private Assembly $viewAssembly;
	private Assembly $logicAssembly;
	private Container $container;
	private Injector $injector;
	private string $viewClassName;
	private bool $routeCompleted;
	private RedirectExceptionFactory $redirectExceptionFactory;

	public function __construct(
		protected ?ConfigSection $routerConfig = null,
		?Assembly $viewAssembly = null,
		?Assembly $logicAssembly = null,
		?RedirectExceptionFactory $redirectExceptionFactory = null,
	) {
		$this->viewAssembly = $viewAssembly ?? new Assembly();
		$this->logicAssembly = $logicAssembly ?? new Assembly();
		$this->redirectExceptionFactory = $redirectExceptionFactory ?? new RedirectExceptionFactory();
		$this->routeCompleted = false;
	}

	public function setContainer(Container $container):void {
		$this->container = $container;
	}

	public function setInjector(Injector $injector):void {
		$this->injector = $injector;
	}

	public function handleRedirects(
		Redirects $redirects,
		RequestInterface $request
	):void {
		$responseCode = $this->routerConfig?->getInt("redirect_response_code");
		$uri = $request->getUri()->getPath();

		foreach($redirects as $old => $new) {
			if($old === $uri) {
				throw $this->redirectExceptionFactory->create($responseCode, $new);
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
			if(!$routerCallback->matchesAccept($request->getHeaderLine("accept"))) {
				continue;
			}

			array_push($validCallbackArray, $routerCallback);
		}

		$bestRouterCallback = $this->negotiateBestCallback(
			$request,
			$validCallbackArray
		);

		if(!$bestRouterCallback) {
			throw new HttpNotAcceptable();
		}

// TODO: Call with the DI, so the callback can receive all the required params.
		$bestRouterCallback->call($this);
		$this->routeCompleted = true;
	}

	public function setViewClass(string $className):void {
		$this->viewClassName = $className;
	}

	public function getViewClass():?string {
		return $this->viewClassName ?? null;
	}

	public function getLogicAssembly():Assembly {
		if(!$this->routeCompleted) {
			throw new NotYetRoutedException();
		}
		return $this->logicAssembly;
	}

	public function getViewAssembly():Assembly {
		return $this->viewAssembly;
	}

	protected function addToLogicAssembly(
		string $relativePath
	):void {
		$this->addToAssembly(
			Assembly::TYPE_LOGIC,
			$relativePath
		);
	}

	protected function addToViewAssembly(
		string $relativePath
	):void {
		$this->addToAssembly(
			Assembly::TYPE_VIEW,
			$relativePath
		);
	}

	private function addToAssembly(
		string $type,
		string $path
	):void {
		$assembly = ($type === Assembly::TYPE_LOGIC)
			? $this->logicAssembly
			: $this->viewAssembly;

		$assembly->add($path);
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
					new RouterCallback(
						$method,
						$attribute,
						$this->container ?? null,
						$this->injector ?? null
					)
				);
			}
		}

		return $routerCallbackArray;
	}

	/** @param array<RouterCallback> $callbackArray */
	private function negotiateBestCallback(
		RequestInterface $request,
		array $callbackArray
	):?RouterCallback {
		$bestQuality = -1;
		$bestCallback = null;
		foreach($callbackArray as $callback) {
			/** @var Accept|null $best */
			$best = $callback->getBestNegotiation(
				$request->getHeaderLine("accept")
			);

			$quality = $best?->getQuality() ?? 0;
			if($quality <= $bestQuality) {
				continue;
			}

			$bestQuality = $quality;
			$bestCallback = $callback;
		}

		return $bestCallback;
	}
}
