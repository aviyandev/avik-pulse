<?php

declare(strict_types=1);

namespace Avik\Pulse\Http;

use Avik\Flow\Http\Request;
use Avik\Flow\Http\Response;
use Avik\Path\Dispatcher;
use Avik\Crate\Container;
use Avik\Pulse\Exceptions\HttpExceptionHandler;

class Kernel
{
    /**
     * The application's global HTTP middleware stack.
     */
    protected array $middleware = [];

    /**
     * The application's route middleware groups.
     */
    protected array $middlewareGroups = [];

    /**
     * The application's route middleware aliases.
     */
    protected array $middlewareAliases = [];

    public function __construct(
        private Container $container,
        private Dispatcher $dispatcher,
        private Pipeline $pipeline,
        private HttpExceptionHandler $exceptions
    ) {}

    public function handle(Request $request): Response
    {
        try {
            [$route, $params] = $this->dispatcher->dispatch(
                $request->method(),
                $request->path(),
                $this->container->make('routes')
            );

            $middleware = $this->gatherMiddleware($route);

            return $this->pipeline
                ->send($middleware)
                ->process(
                    $request,
                    fn(Request $req) =>
                    $this->container
                        ->make(ControllerDispatcher::class)
                        ->dispatch($route, $params, $req)
                );
        } catch (\Throwable $e) {
            return $this->exceptions->handle($request, $e);
        }
    }

    /**
     * Gather all middleware for the given route.
     */
    protected function gatherMiddleware($route): array
    {
        $middleware = $this->middleware;

        if (isset($route->middleware)) {
            foreach ((array) $route->middleware as $name) {
                $middleware = array_merge($middleware, $this->resolveMiddleware($name));
            }
        }

        return array_unique($middleware);
    }

    /**
     * Resolve the middleware name to its class(es).
     */
    protected function resolveMiddleware(string $name): array
    {
        $params = '';
        if (str_contains($name, ':')) {
            [$name, $params] = explode(':', $name, 2);
            $params = ':' . $params;
        }

        if (isset($this->middlewareGroups[$name])) {
            $resolved = [];
            foreach ($this->middlewareGroups[$name] as $middleware) {
                $resolved[] = $middleware . $params;
            }
            return $resolved;
        }

        if (isset($this->middlewareAliases[$name])) {
            return (array) ($this->middlewareAliases[$name] . $params);
        }

        return [$name . $params];
    }

    /**
     * Terminate the request lifecycle.
     */
    public function terminate(Request $request, Response $response): void
    {
        $this->pipeline->terminate($request, $response);
    }
}
