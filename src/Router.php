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
}
