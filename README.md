Routes HTTP requests to logic and view files.
=============================================

Planning
--------

Main flow:

+ `Router` object is constructed with a PSR request.
+ `addHandler()` method assigns a `Match` to a `Handler`, a Match being a dynamic-specificity request matching object, a Handler being like a callback.
+ `getRoute()` method returns a `Route` object that describes the `Assembly` objects that are needed to build the response.
