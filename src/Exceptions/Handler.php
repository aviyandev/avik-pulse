<?php

declare(strict_types=1);

namespace Avik\Pulse\Exceptions;

use Avik\Flow\Http\Request;
use Avik\Flow\Http\Response;

abstract class Handler
{
    /**
     * Render an exception into an HTTP response.
     */
    abstract public function handle(Request $request, \Throwable $e): Response;
}