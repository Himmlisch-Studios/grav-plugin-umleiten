<?php

namespace Grav\Plugin\Umleiten;

use Grav\Common\Page\Page;
use Nyholm\Psr7\ServerRequest;

abstract class Middleware
{
    public abstract function __invoke(ServerRequest $request): ?Page;
}
