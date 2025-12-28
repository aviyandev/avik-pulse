<?php

declare(strict_types=1);

namespace Avik\Pulse\Exceptions;

use Avik\Flow\Http\Request;
use Avik\Flow\Http\Response;
use Avik\Crate\Container;

final class HttpExceptionHandler extends Handler
{
    public function __construct(
        private Container $container
    ) {}

    public function handle(Request $request, \Throwable $e): Response
    {
        if (method_exists($e, 'report')) {
            $e->report();
        }

        if (method_exists($e, 'render')) {
            $response = $e->render($request);
            if ($response instanceof Response) {
                return $response;
            }
        }

        $status = $this->getStatusCode($e);
        $debug = $this->container->has('config')
            ? ($this->container->make('config')['app']['debug'] ?? false)
            : false;

        if ($request->wantsJson()) {
            return new Response(
                json_encode($this->convertExceptionToArray($e, $debug)),
                $status,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response(
            $this->renderHtml($e, $debug),
            $status
        );
    }

    private function getStatusCode(\Throwable $e): int
    {
        $code = $e->getCode();
        return is_int($code) && $code >= 100 && $code < 600 ? $code : 500;
    }

    private function convertExceptionToArray(\Throwable $e, bool $debug): array
    {
        return $debug ? [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
        ] : [
            'message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error',
        ];
    }

    private function renderHtml(\Throwable $e, bool $debug): string
    {
        if (!$debug) {
            return "<h1>Server Error</h1><p>" . ($this->isHttpException($e) ? $e->getMessage() : 'Something went wrong.') . "</p>";
        }

        $message = htmlspecialchars($e->getMessage());
        $class = get_class($e);
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = nl2br(htmlspecialchars($e->getTraceAsString()));

        return <<<HTML
            <div style="font-family: sans-serif; padding: 20px;">
                <h1 style="color: #c00;">{$class}</h1>
                <p><strong>Message:</strong> {$message}</p>
                <p><strong>File:</strong> {$file}:{$line}</p>
                <h2>Stack Trace</h2>
                <pre style="background: #f4f4f4; padding: 10px; border-radius: 5px;">{$trace}</pre>
            </div>
        HTML;
    }

    private function isHttpException(\Throwable $e): bool
    {
        // Simple check for now, could be expanded
        return $e instanceof \RuntimeException && $e->getCode() >= 400 && $e->getCode() < 500;
    }
}
