<?php

declare(strict_types=1);

namespace Avik\Pulse;

use Avik\Seed\Contracts\ServiceProvider;
use Avik\Crate\Container;
use Avik\Pulse\Http\{
    Kernel,
    Pipeline,
    ControllerDispatcher
};
use Avik\Pulse\Exceptions\HttpExceptionHandler;

use Avik\Ignite\Application;

final class PulseServiceProvider implements ServiceProvider
{
    private Container $container;

    public function __construct(Application $app)
    {
        $this->container = $app->container();
    }

    public function register(): void
    {
        $this->container->singleton(Kernel::class, Kernel::class);
        $this->container->singleton(Pipeline::class, Pipeline::class);
        $this->container->singleton(ControllerDispatcher::class, ControllerDispatcher::class);
        $this->container->singleton(HttpExceptionHandler::class, HttpExceptionHandler::class);
    }

    public function boot(): void {}
}
