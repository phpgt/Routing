<?php
namespace App;

use Gt\Http\Request;
use Gt\Routing\Method\Any;
use Gt\Routing\Method\Get;
use Gt\Routing\Method\Post;
use Gt\Routing\DynamicPath;
use Gt\Routing\Router as BaseRouter;

class Router extends BaseRouter {
	#[Any(name: "api-route", accept: "application/json,application/xml")]
	public function api(Request $request):void {
		foreach($this->findMatchingFilePaths(
			"api",
			"php",
			$request->getUri()->getPath()
		) as $logicName => $path) {
			$this->addToLogicAssembly($path, $logicName);
		}
	}

	#[Get(name: "page-route", accept: "text/html,application/xhtml+xml")]
	public function page(Request $request):void {
		// TODO: add logic and view assembly in the api directory
		// (configured from $this->routerConfig)
	}

	#[Post(path: "/greet/@name", accept: "text/plain")]
	public function dynamicText(
		DynamicPath $dynamicPath
	):void {
		$this->addToLogicAssembly("class/Output/Greeter.php");
		$this->setAssemblyData(
			"name",
			$dynamicPath->getString("name")
		);
	}

	/** @return string[] Ordered list of file paths */
	private function findMatchingFilePaths(
		string $baseDir,
		string $extension,
		string $uriPath
	):array {

	}
}
