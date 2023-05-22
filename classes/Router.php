<?php

namespace Grav\Plugin\Umleiten;

use Exception;
use Grav\Common\Grav;
use Grav\Common\Page\Interfaces\PageInterface;
use Grav\Common\Page\Page;
use Grav\Framework\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;

class Router
{
    protected static $instance;

    protected Grav $grav;
    protected bool $initialized;

    protected array $routes = [];

    public function __construct()
    {
        $this->grav = Grav::instance();
    }

    public static function instance()
    {
        if (isset(self::$instance)) {
            return self::$instance;
        }

        return self::$instance = new static();
    }

    public function addMany(array $routes)
    {
        foreach ($routes as $route) {
            $this->add($route);
        }
    }

    public function add(Route $route): Route
    {
        if (!$this->initialized) {
            throw new Exception("Router should be called after initilization");
        }

        return $this->routes[] = $route;
    }

    public static function init(): self
    {
        if (!isset(Grav::instance()['router'])) {
            Grav::instance()['router'] = $instance = self::instance();
            $instance->initialized = true;
        }

        return Grav::instance()['router'];
    }

    public function process()
    {
        $path = $this->grav['uri']->path();
        $method = $this->grav['request']->getMethod();

        $fountRoute = null;

        /** @var Route */
        foreach ($this->routes as $route) {
            if ($route->is($path) && $route->of($method)) {
                $fountRoute = $route;
            }
        }

        if (is_null($fountRoute)) {
            return;
        }

        $response = $fountRoute->boot();

        if (is_null($response)) {
            return;
        }

        $this->resolveResponse($response);
    }

    protected function resolveResponse(mixed $response)
    {
        if ($response instanceof Page) {
            $path = $this->grav['uri']->path();
            $response->route($path);
            $this->grav['pages']->addPage($response, $path);
        } else if ($response instanceof ServerRequestInterface) {
            $this->grav['request'] = $response;
        } else {
            try {
                $this->grav->close(new Response(200, [], (string) $response));
            } catch (\Throwable $th) {
            }
        }
    }
}
