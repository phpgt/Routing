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
use Gt\Routing\LogicStream\LogicStreamNamespace;
use Gt\Routing\LogicStream\LogicStreamWrapper;
use Gt\Routing\Path\DynamicPath;
use Gt\Routing\Path\FileMatch\BasicFileMatch;
use Gt\Routing\Path\FileMatch\MagicFileMatch;
use Gt\Routing\Path\PathMatcher;
use Gt\Routing\Redirects;
use Gt\ServiceContainer\Container;

require(__DIR__ . "/../vendor/autoload.php");

// The config is used to explain what file and class is used to contain the
// application-specific router that will handle the request.
$config = ConfigFactory::createFromPathName(
	__DIR__ . "/project/simple-site/config.ini"
);

// Request 1: A page request, as if it is sent from a web browser.
$pageRequest = new Request(
	"GET",
	new Uri("/shop/furniture/chair"),
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

$container = new Container();
$container->set($pageRequest);
$baseAssemblyDirectory = "page";

$pathMatcher = new PathMatcher($baseAssemblyDirectory);
$pathMatcher->addFilter(function(string $filePath, string $uriPath, string $baseDir):bool {
// There are three types of matching files: Basic, Magic and Dynamic.
// Basic is where a URI matches directly to a file on disk.
// Magic is where a URI matches a PHP.Gt-specific file, like _common or _header.
// Dynamic is where a URI matches a file/directory marked as dynamic with "@".
	$basicFileMatch = new BasicFileMatch($filePath, $baseDir);
	if($basicFileMatch->matches($uriPath)) {
		return true;
	}

	$magicFileMatch = new MagicFileMatch($filePath, $baseDir);
	if($magicFileMatch->matches($uriPath)) {
		return true;
	}

	return false;
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

$logicAssembly = $router->getLogicAssembly();
$viewAssembly = $router->getViewAssembly();
$dynamicPath = new DynamicPath(
	$pageRequest->getUri()->getPath(),
	$logicAssembly,
	$viewAssembly
);
$container->set($dynamicPath);

foreach($logicAssembly as $logicPathname) {
	echo "Loading logic class: $logicPathname", PHP_EOL;
	require("gt-logic-stream://$logicPathname");

// TODO: Build $className: it's either going to be path-matched with the application
// namespace, or it's going to be automatically generated from the gt-logic-stream.
// Need to look for either classes, load it, reference the "go", etc.

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
	$logicStreamNamespace = new LogicStreamNamespace(
		$logicPathname,
		LogicStreamWrapper::NAMESPACE_PREFIX
	);

	$class = null;
	$goFunctionName = "go";
	/** @var ReflectionMethod|ReflectionFunction|null $refFunction */
	$refFunction = null;

	if(class_exists($className)) {
		$class = new $className();
		$refFunction = new ReflectionMethod($class, $goFunctionName);
	}
	else {
		$refFunctionFQN = "$logicStreamNamespace\\$goFunctionName";

		if(function_exists($refFunctionFQN)) {
			$refFunction = new ReflectionFunction($refFunctionFQN);
		}
	}

	if(is_null($refFunction)) {
		die("ERROR! Can't load go function!");
	}

	if($class) {
		/** @var ReflectionMethod $refFunction */
		$closure = $refFunction->getClosure($class);
	}
	else {
		/** @var ReflectionFunction $refFunction */
		$closure = $refFunction->getClosure();
	}

	$injector->invoke($class, $closure);
}
foreach($viewAssembly as $viewPathname) {
	echo "Loading view part: $viewPathname", PHP_EOL;
}
