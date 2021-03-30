<?php
namespace Gt\Routing;

use Gt\Config\ConfigSection;
use Gt\Http\ResponseStatusException\Redirection\HttpFound;
use Gt\Http\ResponseStatusException\Redirection\HttpMovedPermanently;
use Gt\Http\ResponseStatusException\Redirection\HttpMultipleChoices;
use Gt\Http\ResponseStatusException\Redirection\HttpNotModified;
use Gt\Http\ResponseStatusException\Redirection\HttpPermanentRedirect;
use Gt\Http\ResponseStatusException\Redirection\HttpSeeOther;
use Gt\Http\ResponseStatusException\Redirection\HttpTemporaryRedirect;
use Negotiation\Negotiator;
use Psr\Http\Message\RequestInterface;

class Router {
	private Handler $defaultHandler;
	/** @var Matcher[] */
	private array $matcherList;
	/** @var Handler[] */
	private array $handlerList;

	public function __construct(
		private ConfigSection $routerConfig
	) {
		$this->matcherList = [];
		$this->handlerList = [];
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

	public function addDefaultHandler(
		Matcher $matcher,
		Handler $handler
	):void {
		$this->addHandler($matcher, $handler);
		$this->defaultHandler = $handler;
	}

	public function addHandler(
		Matcher $matcher,
		Handler $handler
	):void {
		array_push($this->matcherList, $matcher);
		array_push($this->handlerList, $handler);
	}

	public function getRoute(
		RequestInterface $request,
		Negotiator $negotiator = null
	):Route {
		$negotiator = $negotiator ?? new Negotiator();
		$handler = $this->matchHandler($request, $negotiator);
//		return new Route(
//			$handler->get
//		)
	}

	private function matchHandler(
		RequestInterface $request,
		Negotiator $negotiator
	):Handler {
		$acceptHeader = $request->getHeaderLine("accept");
		$uriPath = $request->getUri()->getPath();

		foreach($this->matcherList as $i => $matcher) {
			$mediaType = $negotiator->getBest(
				$acceptHeader,
				$matcher->getAccepts()
			);
			if(!$mediaType) {
				continue;
			}

			if($uriPathPrefix = $matcher->getUriPathPrefix()) {
				if(str_starts_with($uriPath, $uriPathPrefix)) {
					continue;
				}
			}
			if($uriPathSuffix = $matcher->getUriPathSuffix()) {
				if(!str_ends_with($uriPathPrefix, $uriPathSuffix)) {
					continue;
				}
			}
			if($callback = $matcher->getCallback()) {
				if(!call_user_func($callback, $request)) {
					continue;
				}
			}

			return $this->handlerList[$i];
		}
	}
}
