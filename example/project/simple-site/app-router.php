<?php
namespace App;

use Gt\Http\Request;
use Gt\Routing\Method\Any;
use Gt\Routing\Method\Get;
use Gt\Routing\Method\Post;
use Gt\Routing\BaseRouter;
use Gt\Routing\Path\PathMatcher;
use Gt\Routing\Path\DynamicPath;

class AppRouter extends BaseRouter {
	#[Any(name: "api-route", accept: "application/json,application/xml")]
	public function api(Request $request, PathMatcher $pathMatcher):void {
		echo "API ROUTE CALLBACK", PHP_EOL;
		foreach($pathMatcher->findForUriPath(
			$request->getUri()->getPath(),
			"api/v1",
			"php"
		) as $logicName => $path) {
			$this->addToLogicAssembly($path);
		}
	}

	#[Get(name: "page-route", accept: "text/html,application/xhtml+xml")]
	public function page(PathMatcher $pathMatcher, Request $request):void {
		echo "PAGE ROUTE CALLBACK", PHP_EOL;
		// TODO: add logic and view assembly in the api directory
		// (configured from $this->routerConfig)

		$sortNestLevelCallback = fn(string $a, string $b) =>
			substr_count($a, "/") > substr_count($b, "/");
		$footerSort = fn(string $a, string $b) =>
			strtok(basename($a), ".") === "_footer" ? 1 : 0;

		$matchingLogics = $pathMatcher->findForUriPath(
			$request->getUri()->getPath(),
			"page",
			"php"
		);
		usort($matchingLogics, $sortNestLevelCallback);
		foreach($matchingLogics as $path) {
			$this->addToLogicAssembly($path);
		}

		$matchingViews = $pathMatcher->findForUriPath(
			$request->getUri()->getPath(),
			"page",
			"html"
		);
		usort($matchingViews, $sortNestLevelCallback);
		usort($matchingViews, $footerSort);
		foreach($matchingViews as $path) {
			$this->addToViewAssembly($path);
		}
	}

	#[Post(path: "/greet/@name", function: "greet", accept: "text/plain")]
	public function dynamicText(
		DynamicPath $dynamicPath
	):void {
		$this->addToLogicAssembly("class/Output/Greeter.php");
	}
}
