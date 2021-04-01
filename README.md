Routes HTTP requests to logic and view files.
=============================================

Planning
--------

Main flow:

+ Your application can have a `router.php` in project root, but is completely optional due to `router.default.php` being provided by WebEngine. You can use the router.php file to add complicated rules that define which source files are used to build up the response, rather than always being limited to using path-based rules, as is currently the case with WebEngine. 
+ The name of the application's router is defined in config by `app_router_path` key.
+ The application router defines a class called `Router` in the application's root namespace, which extends `Gt\Routing\BaseRouter`.
+ BaseRouter's `go()` method is called, supplying a PSR-7 `RequestInterface` which will call functions of the application's router.
+ The application router can have any number of functions. They are made "routable" by adding an Attribute that extends `Route`. Available Attributes include `Get`, `Post` (and other HTTP verbs) or `Any` to match on all verbs.
+ The simplest routable function has the Any attribute with no parameters (`#[Any()]`), which will be executed for every request.
+ Available Attribute parameters include: `name` to provide the route a name for future reference, `accept` to provide a Content-type to match the Request's `Accept` header with, `path` to define a matching path (with pattern matching).
+ Only the functions that have Attributes that match the incoming request will be executed.
+ It's the job of the application's routable functions to add appropriate view and logic files to the Router's `Assembly` objects. The framework (WebEngine in my case) will then use the Assembly objects to build up the appropriate `View` and execute the appropriate `Logic` objects in the correct order.
