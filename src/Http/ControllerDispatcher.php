<?php

declare(strict_types=1);

namespace Avik\Pulse\Http;

use Avik\Flow\Http\Request;
use Avik\Flow\Http\Response;
use Avik\Crate\Container;
use Avik\Path\Route;

final class ControllerDispatcher
{
    public function __construct(
        private Container $container
    ) {}

    public function dispatch(
        Route $route,
        array $params,
        Request $request
    ): Response {
        $action = $route->action;

        if (is_array($action)) {
            [$class, $method] = $action;
            $controller = $this->container->make($class);

            return $this->container->call(
                [$controller, $method],
                $params + ['request' => $request]
            );
        }

        if (is_callable($action)) {
            return $this->container->call(
                $action,
                $params + ['request' => $request]
            );
        }

        throw new \RuntimeException('Invalid route action');
    }
}
