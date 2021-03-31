<?php
namespace App;

use Gt\Http\Request;
use Gt\Routing\Router as BaseRouter;

class Router extends BaseRouter {
	#[Any(name: "api-route", accept: "application/json,application/xml")]
	public function api(Request $request):void {
		// TODO: add logic assembly in the api directory
		// (configured from $this->routerConfig)
	}

	#[Any(name: "page-route", accept: "text/html,application/xhtml+xml")]
	public function page(Request $request):void {
		// TODO: add logic and view assembly in the api directory
		// (configured from $this->routerConfig)
	}

	#[Get(accept: "text/plain", path: "/greet/{name}")]
	public function dynamicText(Request $request, DynamicPath $path):void {
		// TODO: add custom class to act as a logic
		// and execute it to output "Hello, $name!"... how to do this?
	}
}
