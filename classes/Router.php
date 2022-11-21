<?php

namespace Grav\Plugin\Umleiten;

use Exception;
use Grav\Common\Grav;
use Grav\Common\Page\Interfaces\PageInterface;

class Router
{
    protected Grav $grav;

    public function __construct(protected array $routes = [])
    {
        $this->grav = Grav::instance();
    }

    private function addRoutes(array $routes)
    {
        $this->routes += $routes;
    }

    public static function init()
    {
        if (!isset(Grav::instance()['router'])) {
            $router = new static();
            Grav::instance()['router'] = $router;
        }
    }

    public static function boot($root = null)
    {
        if (is_null($root)) {
            $path = debug_backtrace()[0]['file'];
            $root = str_replace(basename($path), '', $path);
        }

        $script = is_file($root) ? $root : DS . trim($root, DS) . DS . 'routes.php';

        if (!file_exists($root)) {
            throw new Exception("Couldn't find Routes file", 1);
        }

        self::init();

        /** @var self */
        $router = Grav::instance()['router'];
        $router->addRoutes(include $script);

        $path = $router->grav['uri']->path();
        $method = $router->grav['request']->getMethod();

        $found_route = null;

        /** @var Route */
        foreach ($router->routes as $route) {
            if ($route->is($path) && $route->of($method)) {
                $found_route = $route;
            }
        }

        if (is_null($found_route)) {
            return;
        }

        /** @var Pages */
        $pages = $router->grav['pages'];

        $page = $found_route->boot();

        if ($page instanceof PageInterface) {
            $pages->addPage($page, $path);
        }
    }
}
