<?php

declare(strict_types=1);

namespace Avik\Pulse\Http;

use Avik\Flow\Http\Request;
use Avik\Flow\Http\Response;
use Avik\Flow\Middleware\Pipeline as FlowPipeline;
use Avik\Path\Dispatcher;
use Avik\Path\RouteCollection;
use Avik\Crate\Container;
use Avik\Pulse\Exceptions\HttpExceptionHandler;

final class Kernel
{
    /** Global middleware (runs on every request) */
    protected array $middleware = [];

    /** Middleware groups: 'web', 'api', etc. */
    protected array $middlewareGroups = [
        'web' => [],
        'api' => [],
    ];

    /** Named middleware aliases */
    protected array $middlewareAliases = [];

    public function __construct(
        private Container $container,
        private Dispatcher $dispatcher,
        private FlowPipeline $pipeline,           // ← Reusing Flow Pipeline
        private HttpExceptionHandler $exceptions
    ) {}

    public function handle(Request $request): Response
    {
        try {
            $routeCollection = $this->container->make(RouteCollection::class);

            [$route, $params] = $this->dispatcher->dispatch(
                $request->method(),
                $request->uri(),
                $routeCollection
            );

            $middleware = $this->gatherMiddleware($route);

            // Use Flow's Pipeline
            return $this->pipeline
                ->process(
                    $request,
                    fn(Request $req) => $this->dispatchToRoute($route, $params, $req)
                );

        } catch (\Throwable $e) {
            return $this->exceptions->handle($request, $e);
        }
    }

    protected function dispatchToRoute(Route $route, array $params, Request $request): Response
    {
        return $this->container
            ->make(ControllerDispatcher::class)
            ->dispatch($route, $params, $request);
    }

    /**
     * Gather middleware for the current route
     */
    protected function gatherMiddleware(Route $route): array
    {
        $middleware = $this->middleware;

        foreach ((array) ($route->middleware ?? []) as $item) {
            $middleware = array_merge($middleware, $this->resolveMiddleware($item));
        }

        return array_unique($middleware);
    }

    /**
     * Resolve middleware name/alias/group into class names
     */
    protected function resolveMiddleware(string $name): array
    {
        $params = '';

        if (str_contains($name, ':')) {
            [$name, $params] = explode(':', $name, 2);
            $params = ':' . $params;
        }

        // Group (web, api, etc.)
        if (isset($this->middlewareGroups[$name])) {
            return array_map(
                fn(string $m) => $m . $params,
                $this->middlewareGroups[$name]
            );
        }

        // Alias
        if (isset($this->middlewareAliases[$name])) {
            return [(string) $this->middlewareAliases[$name] . $params];
        }

        // Direct class name
        return [$name . $params];
    }

    /**
     * Terminate the request (cleanup)
     */
    public function terminate(Request $request, Response $response): void
    {
        // Flow Pipeline already implements Terminable if needed
        // You can extend it later if you want custom termination logic
    }
}