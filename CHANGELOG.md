
### Unreleased

* Add ResponseFactory helper class for creating simple response objects.
* Add HealthOKHandler for returning a simple 200 OK response to a GET, and NotFoundHandler to return a plain-text
  404 response.
* Add RequestMethodGuardTrait that handlers can use to ensure that the incoming request is an allowed method, returns 405 if not.
* [BREAKING]: Handler factory functions now take HandlerFactoryArgs instead of LoggerInterface, this allows us to 
  also pass the request, to allow for simple routing to different handlers.
* [BREAKING]: Added strict types and array shapes throughout.
* [BREAKING]: FactoryFunction::call() no longer accepts or passes arguments to the callable.
* [Internal] Bump dev dependencies
* Require PHP 8.5
* Require ingenerator/php-utils version 3
* Support Guzzle PSR-7 3.x (as well as 2.x)

### v1.2.0 (2025-07-23)

* Require PHP 8.4

### v1.1.0 (2024-10-02)

* Support PHP 8.3

### v1.0.1 (2024-07-22)

* Add readme and license
* Add some blackbox testing
* Throw an exception instead of silently overwriting if response handlers attempt to send more than one value
  for the same header.
* Improve handling if code starts output before the framework even boots.


### v1.0.0 (2024-07-16)

* First version, extracted from host project
