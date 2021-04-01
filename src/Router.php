<?php
namespace Gt\Routing;

use Gt\Config\ConfigSection;
use Gt\Routing\Method\Any;
use Gt\Routing\Method\Get;
use Gt\Routing\Method\Post;
use Gt\Routing\Method\RouteMethod;
use Negotiation\Negotiator;
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
		protected ConfigSection $routerConfig
	) {
		$this->viewAssembly = new Assembly();
		$this->logicAssembly = new Assembly();
	}

	public function go(RequestInterface $request):void {
		$class = new ReflectionClass($this);
		foreach($class->getMethods() as $funcIndex => $func) {
			foreach($func->getAttributes() as $attribute) {
				$name = $attribute->getName();
				$args = $attribute->getArguments();

				if(!is_a($name, Route::class, true)) {
					continue;
				}

				$allowedMethods = match($name) {
					Any::class => RouteMethod::METHODS_ALL,
					Get::class => [RouteMethod::METHOD_GET],
					Post::class => [RouteMethod::METHOD_POST],
					default => $args["methods"] ?? [],
				};
				$allowedMethods = array_map(
					"strtoupper",
					$allowedMethods
				);

				$requestMethod = strtoupper($request->getMethod());
				$requestPath = $request->getUri()->getPath();

				if(!in_array($requestMethod, $allowedMethods)) {
					continue;
				}

				$funcName = "func$funcIndex";

				foreach($args as $key => $value) {
					if($key === "name") {
						$funcName = $value;
					}

					if($key === "path") {
						$pattern = $value;
						$pattern = str_replace("/", "\/", $pattern);
						$pattern = preg_replace("/@{?([a-z0-9]*)}?/", "(?P<\$1>[^\/]+)", $pattern);
						if(!preg_match("/$pattern/", $requestPath, $pathMatches)) {
							continue(2);
						}
					}

					if($key === "accept") {
						$acceptedTypes = explode(",", $value);
						$negotiator = new Negotiator();
						$best = $negotiator->getBest($request->getHeaderLine("accept"), $acceptedTypes);
						if(!$best) {
							continue(2);
						}
					}
				}

				$injectionParameters = [];
				foreach($func->getParameters() as $param) {
					$paramName = $param->getName();
					$paramType = $param->getType()->getName();
					if(is_a($paramType, RequestInterface::class, true)) {
						array_push(
							$injectionParameters,
							$request
						);
					}
					if(is_a($paramType, DynamicPath::class, true)) {
						$kvp = array_filter(
							$pathMatches,
							fn($key) => !is_numeric($key),
							ARRAY_FILTER_USE_KEY
						);

						$dynamicPath = new DynamicPath($kvp);
						array_push(
							$injectionParameters,
							$dynamicPath
						);
					}
					if($paramType === "string") {
						if($paramName === "name") {
							array_push(
								$injectionParameters,
								$funcName
							);
						}
					}
				}

				call_user_func(
					$func->getClosure($this),
					...$injectionParameters
				);
			}
		}
	}

	public function getViewAssembly():Assembly {
		return $this->viewAssembly;
	}

	public function getLogicAssembly():Assembly {
		return $this->logicAssembly;
	}

	public function handleRedirects(
		Redirects $redirects,
		RequestInterface $request,
		?int $responseCode = null
	):void {
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

	protected function setAssemblyData(string $name, mixed $value):void {
		$this->viewAssembly->setData($name, $value);
		$this->logicAssembly->setData($name, $value);
	}

	protected function addToViewAssembly(
		string $viewPath
	):void {
		$this->viewAssembly->add($viewPath);
	}

	protected function addToLogicAssembly(
		string $logicPath
	):void {
		$this->logicAssembly->add($logicPath);
	}

	protected function replaceViewAssembly(
		string $oldViewPath,
		string $newViewPath
	):void {
		$this->viewAssembly->replace($oldViewPath, $newViewPath);
	}

	protected function replaceLogicAssembly(
		string $oldLogicPath,
		string $newLogicPath
	):void {
		$this->logicAssembly->replace($oldLogicPath, $newLogicPath);
	}

	protected function suppressViewAssembly(
		string $viewPath
	):void {
		$this->viewAssembly->remove($viewPath);
	}

	protected function suppressLogicAssembly(
		string $logicPath
	):void {
		$this->logicAssembly->remove($logicPath);
	}
}
