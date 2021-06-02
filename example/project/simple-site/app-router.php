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
			"api",
			"php"
		) as $logicName => $path) {
			$this->addToLogicAssembly($path, $logicName);
		}
	}

	#[Get(name: "page-route", accept: "text/html,application/xhtml+xml")]
	public function page(PathMatcher $pathMatcher, Request $request):void {
		echo "PAGE ROUTE CALLBACK", PHP_EOL;
		// TODO: add logic and view assembly in the api directory
		// (configured from $this->routerConfig)

		foreach($pathMatcher->findForUriPath(
			$request->getUri()->getPath(),
			"page",
			"php"
		) as $logicName => $path) {
			$this->addToLogicAssembly($path, $logicName);
		}
	}

	#[Post(path: "/greet/@name", function: "greet", accept: "text/plain")]
	public function dynamicText(
		DynamicPath $dynamicPath
	):void {
		$this->addToLogicAssembly("class/Output/Greeter.php");
		$this->setAssemblyData(
			"name",
			$dynamicPath->getString("name")
		);
	}
}
