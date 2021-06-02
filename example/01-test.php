<?php
/**
 * This example shows how a project can be laid out, within the ./project
 * directory, similar to how WebEngine works. Three different HTTP requests
 * are passed to the Router which loads the appropriate Assembly of view/logic
 * files, then executes the correct methods, injecting the correct parameters.
 */

use Gt\Config\ConfigFactory;
use Gt\Http\Header\RequestHeaders;
use Gt\Http\Request;
use Gt\Http\Uri;
use Gt\Routing\BaseRouter;
use Gt\Routing\LogicStreamWrapper;
use Gt\Routing\Redirects;

require(__DIR__ . "/../vendor/autoload.php");

// The config is used to explain what file and class is used to contain the
// application-specific router that will handle the request.
$config = ConfigFactory::createFromPathName(
	__DIR__ . "/project/simple-site/config.ini"
);

// Request 1: A page request, as if it is sent from a web browser.
$pageRequest = new Request(
	"GET",
	new Uri("/shop/item/chair"),
	new RequestHeaders([
// An example accept header from Firefox when requesting a normal link:
		"Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"
	])
);
// Request 2: An API request, as if it were sent via cURL.
$apiRequest = new Request(
	"GET",
	new Uri("/v1/shop/item/chair"),
	new RequestHeaders([
		"Accept" => "application/xml,application/json",
		"Authorization: token 0123456789abcdef"
	])
);
// Request 3: Another request but working with text/plain for example's sake.
$greetRequest = new Request(
	"POST",
	new Uri("/greet/Greg"),
	new RequestHeaders([
		"Accept" => "text/plain"
	])
);

// redirects.csv will allow simple administration of redirects by adding oldUrl
// in first column, newUrl in second. The response code that will be sent can be
// set in the config - autoRedirectStatusCode=308
$routerConfig = $config->getSection("router");

// Set the current directory to the base directory of simple-site project.
chdir(__DIR__ . "/project/simple-site");

$container = new \Gt\ServiceContainer\Container();
$container->set($pageRequest);

$pathMatcher = new \Gt\Routing\Path\PathMatcher();
$pathMatcher->addFilter(function(string $filePath, string $uriPath):bool {
	var_dump($uriPath);
});
$container->set($pathMatcher);
$injector = new \Gt\ServiceContainer\Injector($container);

require($routerConfig->getString("app_router_path") ?? "router.php");
$routerClass = $routerConfig->getString("app_router_class") ?? "Router";
/** @var BaseRouter $router */
$router = new $routerClass($routerConfig);
$router->setContainer($container);
$router->setInjector($injector);

if(is_file("redirects.csv")) {
	$redirects = new Redirects("redirects.csv");
	$router->handleRedirects(
		$redirects,
		$pageRequest
	);
}

// This part emulates how a web framework will call the files.
$router->route($pageRequest);
echo "Router::route() complete", PHP_EOL;

// TEMPORARY MANUAL TESTING:
// TODO: If there is no "namespace" declaration in the top of the file,
// import it into memory and add a namespace yourself (namespace according to
// the current request).
stream_wrapper_register("gt-logic-stream", LogicStreamWrapper::class);
//$logicFilePath = "page/shop/@category/@itemName.php";
//$logicCommonFilePath = "page/shop/_common.php";
//require("gt-logic-stream://$logicFilePath");
//require("gt-logic-stream://$logicCommonFilePath");
////////////////////////////

foreach($logicAssembly = $router->getLogicAssembly() as $logicPathname) {
	echo "Loading logic class: $logicPathname", PHP_EOL;
	require($logicPathname);
	$className = $config->getSection("app")->getString("namespace");
	$className .= "\\";

// TODO: if it's in class, remove "class" from the namespace, otherwise
// just build up the whole path as a namespace.
	if(strpos($logicPathname, "class/") === 0) {
		$className .= substr(
			$logicPathname,
			strlen("class/")
		);
	}
	else {
		$className .= $logicPathname;
	}

	$className = substr($className, 0, strpos($className, "."));
	$className = str_replace("/", "\\", $className);
	$className = "\\$className";

	if(strpos($logicPathname, "api/") === 0) {
		$className .= "Api";
	}
	elseif(strpos($logicPathname, "page/") === 0) {
		$className .= "Page";
	}

	$className = str_replace("@", "_", $className);

	$class = new $className();
	$goFunctionName = $logicAssembly->getFunctionName() ?? "go";
	$refFunction = new ReflectionMethod($class, $goFunctionName);
	$data = $logicAssembly->getData();
	$injectionParameters = [];
	foreach($refFunction->getParameters() as $param) {
		$paramName = $param->getName();
		$paramType = $param->getType()->getName();
		if(!isset($data[$paramName])) {
			continue;
		}
		if(gettype($data[$paramName]) !== $paramType
		&& get_class($data[$paramName]) !== $paramType) {
			continue;
		}
		array_push($injectionParameters, $data[$paramName]);
	}

	call_user_func([$class, $goFunctionName], ...$injectionParameters);
}
foreach($router->getViewAssembly() as $viewPathname) {
	echo "Loading view part: $viewPathname", PHP_EOL;
}
