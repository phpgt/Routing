Routes HTTP requests to logic and view files.
=============================================

As HTTP requests are received by an application, the appropriate logic methods need to be called, along with certain files being loaded to construct the correct view for the request. This is done by taking the HTTP request and _routing_ it to the matching areas of the application source code.

This repository breaks down the concept of routing into three areas of responsibility:

1. Matching an HTTP request to an appropriate callback function.
2. Creating an Assembly of logic and view files that match the request.
3. Processing the Assemblies, in order to create the correct HTTP response.

When referring to Requests within this repository, we are always referring to a [PSR-7 HTTP Message][psr-7].

***

<a href="https://github.com/PhpGt/Routing/actions" target="_blank">
	<img src="https://badge.status.php.gt/routing-build.svg" alt="Build status" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Routing" target="_blank">
	<img src="https://badge.status.php.gt/routing-quality.svg" alt="Code quality" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Routing" target="_blank">
	<img src="https://badge.status.php.gt/routing-coverage.svg" alt="Code coverage" />
</a>
<a href="https://packagist.org/packages/PhpGt/Routing" target="_blank">
	<img src="https://badge.status.php.gt/routing-version.svg" alt="Current version" />
</a>
<a href="http://www.php.gt/routing" target="_blank">
	<img src="https://badge.status.php.gt/routing-docs.svg" alt="PHP.G/Routing documentation" />
</a>

Planning
--------

Main flow:

+ Your application can have a `router.php` in project root, but is completely optional due to `router.default.php` being provided by WebEngine. You can use the router.php file to add complicated rules that define which source files are used to build up the response, rather than always being limited to using path-based rules, as is currently the case with WebEngine. 
+ The name of the application's router is defined in config by `app_router_path` key.
+ The application router defines a class called `Router` in the application's root namespace, which extends `Gt\Routing\AbstractRouter`.
+ BaseRouter's `go()` method is called, supplying a PSR-7 `RequestInterface` which will call functions of the application's router.
+ The application router can have any number of functions. They are made "routable" by adding an Attribute that extends `Route`. Available Attributes include `Get`, `Post` (and other HTTP verbs) or `Any` to match on all verbs.
+ The simplest routable function has the Any attribute with no parameters (`#[Any()]`), which will be executed for every request.
+ Available Attribute parameters include: `name` to provide the route a name for future reference, `accept` to provide a Content-type to match the Request's `Accept` header with, `path` to define a matching path (with pattern matching), `function` to define the function to call.
+ Only the functions that have Attributes that match the incoming request will be executed.
+ It's the job of the application's routable functions to add appropriate view and logic files to the Router's `Assembly` objects. The framework (WebEngine in my case) will then use the Assembly objects to build up the appropriate `View` and execute the appropriate `Logic` objects in the correct order.

TODO List:
----------

+ [x] Perform content negotiation when there are multiple matches. For example: an API route might explicitly accept "application/xml" but the default accept header of web browsers also sends this for page requests, but it gives it a q=0.9 - as long as there is a route with text/html or application/xhtml+xml with a higher q value, it should be preferred.
+ [ ] Take a RequestInterface and a project directory, and construct the appropriate Assembly objects - matching the URL path to directory paths, extracting dynamic paths where appropriate.

[psr-7]: https://www.php-fig.org/psr/psr-7/

# Proudly sponsored by

[JetBrains Open Source sponsorship program](https://www.jetbrains.com/community/opensource/)

[![JetBrains logo.](https://resources.jetbrains.com/storage/products/company/brand/logos/jetbrains.svg)](https://www.jetbrains.com/community/opensource/)
