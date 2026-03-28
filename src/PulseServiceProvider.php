<?php

declare(strict_types=1);

namespace Avik\Pulse;

use Avik\Ignite\Application;
use Avik\Seed\Contracts\ServiceProvider;
use Avik\Pulse\Http\Kernel;
use Avik\Pulse\Http\ControllerDispatcher;
use Avik\Pulse\Exceptions\HttpExceptionHandler;

final class PulseServiceProvider implements ServiceProvider
{
    public function __construct(private Application $app) {}

    public function register(): void
    {
        $container = $this->app->container();

        // Core HTTP Kernel
        $container->singleton(Kernel::class);

        // Controller Dispatcher
        $container->singleton(ControllerDispatcher::class);

        // Exception Handler
        $container->singleton(HttpExceptionHandler::class);
        $container->instance('exceptions', $container->make(HttpExceptionHandler::class));
    }

    public function boot(): void
    {
        // You can register default middleware groups here in the future
    }
}