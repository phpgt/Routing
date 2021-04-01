<?php

use Gt\Config\ConfigFactory;
use Gt\Http\Header\RequestHeaders;
use Gt\Http\Request;
use Gt\Http\Uri;
use Gt\Routing\Handler;
use Gt\Routing\Matcher;
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
	"POST",
	new Uri("/greet/Greg"),
	new RequestHeaders([
// An example accept header from Firefox when requesting a normal link:
		"Accept" => "text/plain"
	])
);

// redirects.csv will allow simple administration of redirects by adding oldUrl
// in first column, newUrl in second. The response code that will be sent can be
// set in the config - autoRedirectStatusCode=308
$routerConfig = $config->getSection("router");

// Set the current directory to the base directory of simple-site project.
chdir(__DIR__ . "/project/simple-site");

require($routerConfig->getString("app_router_path") ?? "router.php");
$router = new App\Router($routerConfig);

if(is_file("redirects.csv")) {
	$redirects = new Redirects("redirects.csv");
	$router->handleRedirects(
		$redirects,
		$pageRequest,
		$routerConfig->getInt("autoRedirectStatusCode")
	);
}

// This part emulates how a web framework will call the files.

$router->go($greetRequest);
echo "Done go", PHP_EOL;

foreach($logicAssembly = $router->getLogicAssembly() as $logicPathname) {
	echo "Loading logic class: $logicPathname", PHP_EOL;
	require($logicPathname);
	$className = $config->getSection("app")->getString("namespace");
	$className .= "\\";
	$className .= substr(
		$logicPathname,
		strlen("class/")
	);
	$className = substr($className, 0, strpos($className, "."));
	$className = str_replace("/", "\\", $className);
	$className = "\\$className";
	$class = new $className();
	$refGo = new ReflectionMethod($class, "go");
	$data = $logicAssembly->getData();
	$injectionParameters = [];
	foreach($refGo->getParameters() as $param) {
		$paramName = $param->getName();
		$paramType = $param->getType()->getName();
		if(!isset($data[$paramName])) {
			continue;
		}
		if(gettype($data[$paramName]) !== $paramType) {
			continue;
		}
		array_push($injectionParameters, $data[$paramName]);
	}

	call_user_func([$class, "go"], ...$injectionParameters);
}
foreach($router->getViewAssembly() as $viewPathname) {
	echo "Loading view part: $viewPathname", PHP_EOL;
}
