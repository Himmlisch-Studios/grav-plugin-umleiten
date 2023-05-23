<?php

namespace Grav\Plugin\Umleiten;

use Closure;
use Exception;
use Grav\Common\Grav;
use Nyholm\Psr7\ServerRequest;

class Route
{
    protected static $methods = ['get', 'post', 'put', 'patch', 'delete'];
    protected array $controllers = [];
    protected array $middlewares = [];
    protected string $method = 'get';
    protected Grav $grav;

    protected function __construct(
        public string $path,
    ) {
        $this->grav = Grav::instance();
    }

    public static function make(string $path): self
    {
        return Router::instance()->add(new static($path));
    }

    public static function get($path, array|string|Controller|callable $controllers): self
    {
        return static::make($path)
            ->setControllers($controllers)
            ->setMethod('get');
    }

    public static function post($path, array|string|Controller|callable $controllers)
    {
        return static::make($path)
            ->setControllers($controllers)
            ->setMethod('post');
    }

    /* ================
     |   SETTERS
     ================== */

    public function middleware(array|string|Controller|callable $middlewares): self
    {
        return $this->setMiddlewares($middlewares);
    }

    public function controller(array|string|Controller|callable $controllers): self
    {
        return $this->setControllers($controllers);
    }

    /* ================
     |    HELPERS
     ================== */

    public function of($method): bool
    {
        return $this->method == $this->validateMethod($method);
    }

    public function is($path): bool
    {
        $path = '/' . trim($path, '/');
        return $this->path == $path;
    }

    /* ================
     |   INTERNAL
     ================== */

    public function boot(): mixed
    {
        $middlewares = $this->middlewares;

        return $this->processRequest($this->grav['request'], $middlewares, function ($request, ...$args) {
            return $this->proccessControllers($this->controllers, $request);
        });
    }

    protected function processRequest($request, array $middlewares, Closure $callback)
    {
        $original = $request;
        $request = $this->proccessMiddlewares($request, $middlewares);

        if ($request instanceof $original) {
            return $callback($request);
        }

        return $request;
    }

    protected function proccessControllers(array $array, $request, ...$args): mixed
    {
        $callback = null;
        foreach ($array as $function) {
            $callback = $this->processRequest(
                $request,
                $this->controllerMiddlewares($function),
                function ($request) use ($function, $args) {
                    return $this->executeFunction($function, $request, ...$args);
                }
            );

            if (!is_null($callback)) {
                break;
            }
        }
        return $callback;
    }

    protected function proccessMiddlewares(ServerRequest $request, array $middlewares, int $index = 0): mixed
    {
        if ($index >= count($middlewares)) {
            return $request;
        }

        $next = function (ServerRequest $request) use ($middlewares, $index) {
            // We call the next middleware on list
            return $this->proccessMiddlewares($request, $middlewares, $index + 1);
        };

        // Execute the current middleware with the $next
        return $this->executeFunction($middlewares[$index], $request, $next);
    }

    protected function executeFunction(mixed $function, ...$args)
    {
        if (is_array($function) && is_callable($function, true)) {
            if (is_string($function[0])) {
                $function[0] = new $function[0];
            }
            $callback = call_user_func($function, ...$args);
        }
        if (is_object($function)) {
            $callback = $function(...$args);
        }
        if (is_string($function)) {
            $callback = (new $function)(...$args);
        }
        return $callback;
    }

    protected function controllerMiddlewares($controller): array
    {
        $object = null;
        if (is_object($controller)) {
            $object = $controller;
        }

        if (is_array($controller)) {
            $object = $controller[0];
        }

        if ($object && method_exists($object, 'middlewares')) {
            $middlewares = $object::middlewares();

            return $middlewares;
        }
        return [];
    }

    protected function setMiddlewares(array|string|Middleware|callable $middlewares): self
    {
        if (is_array($middlewares)) {
            $this->middlewares = $middlewares;
            return $this;
        }

        $this->middlewares = [$middlewares];

        return $this;
    }

    protected function setControllers(array|string|Controller|callable $controllers): self
    {
        if (is_array($controllers)) {
            $this->controllers = $controllers;
        }

        $this->controllers = [$controllers];

        return $this;
    }

    protected function validateMethod($method)
    {
        $method = strtolower($method);
        if (!in_array($method, self::$methods)) {
            throw new Exception("Route method not allowed", 1);
        }
        return $method;
    }

    protected function setMethod(string $method = 'get'): self
    {
        $this->method = $this->validateMethod($method);

        return $this;
    }
}
