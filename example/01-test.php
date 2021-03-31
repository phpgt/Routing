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
	new Uri("/v1/shop/item/chair"),
	new RequestHeaders([
		"Accept" => "application/xml,application/json",
		"Authorization: token 0123456789abcdef"
	])
);
$greetRequest = new Request(
	"GET",
	new Uri("/greet/Greg"),
	new RequestHeaders([
// An example accept header from Firefox when requesting a normal link:
		"Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"
	])
);

// redirects.csv will allow simple administration of redirects by adding oldUrl
// in first column, newUrl in second. The response code that will be sent can be
// set in the config - autoRedirectStatusCode=308
$routerConfig = $config->getSection("router");

require(__DIR__ . "/project/simple-site/router.php");
$router = new App\Router($routerConfig);

if(is_file("redirects.csv")) {
	$redirects = new Redirects("redirects.csv");
	$router->handleRedirects(
		$redirects,
		$pageRequest,
		$routerConfig->getInt("autoRedirectStatusCode")
	);
}

$router->go($apiRequest);

foreach($router->getLogicAssembly() as $logicPathname) {
	echo "Loading logic class: $logicPathname", PHP_EOL;
}
foreach($router->getViewAssembly() as $viewPathname) {
	echo "Loading view part: $viewPathname", PHP_EOL;
}
