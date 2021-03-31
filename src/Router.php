<?php
namespace Gt\Routing;

use Gt\Config\ConfigSection;
use Psr\Http\Message\RequestInterface;
use Gt\Http\ResponseStatusException\Redirection\HttpFound;
use Gt\Http\ResponseStatusException\Redirection\HttpMovedPermanently;
use Gt\Http\ResponseStatusException\Redirection\HttpMultipleChoices;
use Gt\Http\ResponseStatusException\Redirection\HttpNotModified;
use Gt\Http\ResponseStatusException\Redirection\HttpPermanentRedirect;
use Gt\Http\ResponseStatusException\Redirection\HttpSeeOther;
use Gt\Http\ResponseStatusException\Redirection\HttpTemporaryRedirect;

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
// TODO: Find the matching functions on extended classes via ATTRIBUTES that
// match the incoming request.
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

	protected function addToViewAssembly(
		string $viewPath,
		string $viewName = null
	):void {

	}

	protected function addToLogicAssembly(
		string $logicPath,
		string $logicName = null
	):void {

	}

	protected function replaceViewAssembly(
		string $viewName,
		string $newViewPath
	):void {

	}

	protected function replaceLogicAssembly(
		string $logicName,
		string $newLogicPath
	):void {

	}

	protected function suppressViewAssembly(
		string $viewName
	):void {

	}

	protected function suppressLogicAssembly(
		string $logicName
	):void {

	}
}
