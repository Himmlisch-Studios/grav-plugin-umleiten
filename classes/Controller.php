<?php

namespace Grav\Plugin\Umleiten;

/**
 * @property mixed $middlewares
 */
abstract class Controller
{
    public static function middlewares(): array
    {
        if (property_exists(static::class, 'middlewares')) {
            return static::$middlewares;
        }

        return [];
    }

    /* 
    * EXAMPLE
    * public function handle(ServerRequest $request): ?Page
    */
}
