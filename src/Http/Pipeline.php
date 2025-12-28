<?php

declare(strict_types=1);

namespace Avik\Pulse\Http;

use Avik\Flow\Http\Request;

final class Pipeline
{
    private array $middleware = [];

    public function send(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function process(Request $request, callable $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn($next, $middleware) =>
            fn($req) => (new $middleware)->handle($req, $next),
            $destination
        );

        return $pipeline($request);
    }
}
