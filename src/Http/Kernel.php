<?php

declare(strict_types=1);

namespace Avik\Pulse\Http;

use Avik\Flow\Http\Request;
use Avik\Flow\Http\Response;
use Avik\Path\Dispatcher;
use Avik\Crate\Container;
use Avik\Pulse\Exceptions\HttpExceptionHandler;

final class Kernel
{
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

            return $this->pipeline->process(
                $request,
                fn(Request $req) =>
                $this->container
                    ->make(ControllerDispatcher::class)
                    ->dispatch($route, $params, $req)
            );
        } catch (\Throwable $e) {
            return $this->exceptions->handle($e);
        }
    }
}
