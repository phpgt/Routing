<?php

use Gt\Http\Header\RequestHeaders;
use Gt\Http\Request;
use Gt\Http\Uri;
use Gt\Routing\Handler;
use Gt\Routing\Matcher;
use Gt\Routing\Router;
use Gt\Config\ConfigFactory;
use Gt\Routing\Redirects;

require(__DIR__ . "/../vendor/autoload.php");

$config = ConfigFactory::createFromPathName(
	__DIR__ . "/project/simple-site/config.ini"
);

$pageRequest = new Request(
	"GET",
	new Uri("/shop/item/chair"),
	new RequestHeaders([
// An example accept header from Firefox when requesting a normal link:
		"Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"
	])
);
$apiRequest = new Request(
	"GET",
	new Uri("/shop/item/chair"),
	new RequestHeaders([
// An example accept header from Firefox when requesting a normal link:
		"Accept" => "application/json",
		""
	])
);

// redirects.csv will allow simple administration of redirects by adding oldUrl
// in first column, newUrl in second. The response code that will be sent can be
// set in the config - autoRedirectStatusCode=308
$routerConfig = $config->getSection("router");
$r = new Router($routerConfig);
if(is_file("redirects.csv")) {
	$redirects = new Redirects("redirects.csv");
	$r->handleRedirects(
		$redirects,
		$pageRequest,
		$routerConfig->getInt("autoRedirectStatusCode")
	);
}

$pageMatcher = new Matcher(
// first parameter is base directory
	"page",
// second parameter is an array of matching accept headers, in priority order
	["text/html", "application/xhtml+xml"]
);
$apiMatcher = new Matcher(
	"api",
	["application/json", "application/xml"]
);
// A Matcher without an accept header will match */*, and it's then up to the
// content negotiation to decide on the most appropriate Matcher to use.
$dynamicImageMatcher = new Matcher(
	"dynamic/img",
// Passing the named parameter uriPathPrefix will match when the provided string
// exists at the start of the URI's path.
	uriPathPrefix: "/dynamic-image/"
);
// If a match can't be decided upon from an accept header or uri path
// prefix/suffix, then a callback can be passed
//

// Handlers take their View's extension as the only constructor parameter.
// Logic files are always php extensions.
$pageHandler = new Handler("Webpage", "html");
// If no basename is supplied, handler will use this filename.
$pageHandler->setDefaultBasename("index");

// The API and image handlers don't use templated views, as they build the
// response up directly from the logic.
$apiHandler = new Handler("API");
$dynamicImageHandler = new Handler("Dynamic Image");

// Default handler is added as normal, will execute if it matches, but it will
// also execute if all the other handlers DO NOT match.
$r->addDefaultHandler($pageMatcher, $pageHandler);
$r->addHandler($apiMatcher, $apiHandler);
$r->addHandler($dynamicImageMatcher, $dynamicImageHandler);

$route = $r->getRoute($pageRequest);

foreach($route->getLogicAssembly() as $logicPathname) {
	echo "Loading logic class: $logicPathname", PHP_EOL;
}
foreach($route->getViewAssembly() as $viewPathname) {
	echo "Loading view part: $viewPathname", PHP_EOL;
}
