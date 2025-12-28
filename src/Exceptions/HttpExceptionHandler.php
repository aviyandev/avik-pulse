<?php

declare(strict_types=1);

namespace Avik\Pulse\Exceptions;

use Avik\Flow\Http\Response;

final class HttpExceptionHandler
{
    public function handle(\Throwable $e): Response
    {
        $code = $e->getCode();
        $status = is_int($code) && $code >= 100 ? $code : 500;

        return new Response(
            $e->getMessage(),
            $status
        );
    }
}
