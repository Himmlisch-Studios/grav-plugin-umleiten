<?php

namespace Grav\Plugin\Umleiten;

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

    public function boot(): mixed
    {
        $original = $request = $this->grav['request'];
        $request = $this->proccessMiddlewares($request, $this->middlewares);

        if ($original === $request) {
            return $this->bootFunctionArray($this->controllers, $request);
        }

        return $request;
    }

    function proccessMiddlewares(ServerRequest $request, array $middlewares, int $index = 0): mixed
    {
        if ($index >= count($middlewares)) {
            return $request;
        }

        $next = function (ServerRequest $request) use ($middlewares, $index) {
            // We call the next middleware on list
            return $this->proccessMiddlewares($request, $middlewares, $index + 1);
        };

        // Execute the current middleware with the $next
        return $this->bootFunction($middlewares[$index], $request, $next);
    }

    public function middleware(array|string|Controller|callable $middlewares): self
    {
        return $this->setMiddlewares($middlewares);
    }

    public function controller(array|string|Controller|callable $controllers): self
    {
        return $this->setControllers($controllers);
    }

    public function of($method): bool
    {
        return $this->method == $this->validateMethod($method);
    }

    public function is($path): bool
    {
        $path = '/' . trim($path, '/');
        return $this->path == $path;
    }

    protected function bootFunction(mixed $function, ...$args)
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

    protected function bootFunctionArray(array $array, ...$args): mixed
    {
        $callback = null;
        foreach ($array as $function) {
            $callback = $this->bootFunction($function, ...$args);

            if (!is_null($callback)) {
                break;
            }
        }
        return $callback;
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
