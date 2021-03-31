Routes HTTP requests to logic and view files.
=============================================

Planning
--------

Main flow:

+ `router.php` defined in project root, but is completely optional due to `router.default.php` being provided by WebEngine.
+ `class Router extends Gt\Routing\Router` has a function `go()` (like most Gt classes) which will execute for every request if it exists.
+ Any other public functions can be added with annotations to match the request.
+ DI passes Router functions any parameters it requires (`Request $request`, for example). 
+ // TODO: How to set the optional view parts and optional logic parts?
