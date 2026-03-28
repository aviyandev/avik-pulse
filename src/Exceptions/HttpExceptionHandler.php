<?php

declare(strict_types=1);

namespace Avik\Pulse\Exceptions;

use Avik\Flow\Http\Request;
use Avik\Flow\Http\Response;
use Avik\Crate\Container;
use Avik\Essence\Config\Config;

final class HttpExceptionHandler extends Handler
{
    public function __construct(private Container $container) {}

    public function handle(Request $request, \Throwable $e): Response
    {
        // Report exception if the exception has a report method
        if (method_exists($e, 'report') && is_callable([$e, 'report'])) {
            $e->report();
        }

        // Let exception render itself if possible
        if (method_exists($e, 'render') && is_callable([$e, 'render'])) {
            $response = $e->render($request);
            if ($response instanceof Response) {
                return $response;
            }
        }

        $status = $this->getStatusCode($e);
        $debug = $this->isDebugMode();

        if ($request->wantsJson() || $request->isJson()) {
            return Response::json(
                $this->convertExceptionToArray($e, $debug),
                $status
            );
        }

        return new Response(
            $this->renderHtml($e, $debug),
            $status,
            ['Content-Type' => 'text/html']
        );
    }

    private function getStatusCode(\Throwable $e): int
    {
        $code = $e->getCode();
        return is_int($code) && $code >= 100 && $code < 600 ? $code : 500;
    }

    private function isDebugMode(): bool
    {
        return Config::get('app.debug', false) === true;
    }

    private function convertExceptionToArray(\Throwable $e, bool $debug): array
    {
        if (!$debug) {
            return [
                'message' => $this->isClientError($e) ? $e->getMessage() : 'Server Error',
            ];
        }

        return [
            'message'   => $e->getMessage(),
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTrace(),
        ];
    }

    private function renderHtml(\Throwable $e, bool $debug): string
    {
        if (!$debug) {
            return '<h1>Server Error</h1><p>' .
                ($this->isClientError($e) ? htmlspecialchars($e->getMessage()) : 'Something went wrong.') .
                '</p>';
        }

        $message = htmlspecialchars($e->getMessage());
        $class = htmlspecialchars(get_class($e));
        $file = htmlspecialchars($e->getFile());
        $line = $e->getLine();
        $trace = nl2br(htmlspecialchars($e->getTraceAsString()));

        return <<<HTML
        <div style="font-family: system-ui, sans-serif; padding: 30px; background: #f8f9fa;">
            <h1 style="color: #dc3545;">{$class}</h1>
            <p><strong>Message:</strong> {$message}</p>
            <p><strong>File:</strong> {$file}:{$line}</p>
            <h2>Stack Trace</h2>
            <pre style="background:#f1f1f1; padding:15px; border-radius:6px; overflow:auto;">{$trace}</pre>
        </div>
HTML;
    }

    private function isClientError(\Throwable $e): bool
    {
        $code = $e->getCode();
        return is_int($code) && $code >= 400 && $code < 500;
    }
}