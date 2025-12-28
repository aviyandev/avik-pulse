<?php

declare(strict_types=1);

namespace Avik\Pulse\Exceptions;

abstract class Handler
{
    abstract public function handle(\Throwable $e);
}
