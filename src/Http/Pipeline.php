<?php

declare(strict_types=1);

namespace Avik\Pulse\Http;

use Avik\Flow\Http\Request;
use Avik\Flow\Http\Response;
use Avik\Crate\Container;

final class Pipeline
{
    private array $middleware = [];

    public function __construct(
        private Container $container
    ) {}

    public function send(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function process(Request $request, callable $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            function ($next, $middleware) {
                return function ($req) use ($next, $middleware) {
                    [$class, $params] = $this->parseMiddleware($middleware);

                    $instance = is_string($class)
                        ? $this->container->make($class)
                        : $class;

                    if (!method_exists($instance, 'handle')) {
                        throw new \RuntimeException(sprintf('Middleware [%s] must implement handle method.', get_class($instance)));
                    }

                    return $instance->handle($req, $next, ...$params);
                };
            },
            $destination
        );

        return $pipeline($request);
    }

    /**
     * Parse the middleware string into class and parameters.
     */
    private function parseMiddleware(mixed $middleware): array
    {
        if (!is_string($middleware)) {
            return [$middleware, []];
        }

        if (!str_contains($middleware, ':')) {
            return [$middleware, []];
        }

        [$class, $params] = explode(':', $middleware, 2);

        return [$class, explode(',', $params)];
    }

    public function terminate(Request $request, Response $response): void
    {
        foreach ($this->middleware as $middleware) {
            [$class, $params] = $this->parseMiddleware($middleware);

            $instance = is_string($class)
                ? $this->container->make($class)
                : $class;

            if (method_exists($instance, 'terminate')) {
                $instance->terminate($request, $response);
            }
        }
    }
}
