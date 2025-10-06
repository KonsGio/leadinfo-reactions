<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Handles CORS: adds cross-origin headers and short-circuits OPTIONS requests.
 */
final readonly class CorsMiddleware
{
    public function __construct(private string $allowedOrigin)
    {
    }


    /**
     * __invoke() is used by Slim automatically, no manual call needed.
     *
     * @param Request $request
     * @param Handler $handler
     * @return Response
     */
    public function __invoke(Request $request, Handler $handler): Response
    {
        // Small helper to attach headers consistently
        $applyCorsHeaders = function (Response $response): Response {
            $origin = $this->allowedOrigin ?: '*';
            return $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Vary', 'Origin')
                ->withHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With')
                ->withHeader('Access-Control-Expose-Headers', 'X-Request-Id')
                ->withHeader('Access-Control-Max-Age', '86400');
        };

        // Handle browser preflight requests quickly
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $applyCorsHeaders(new SlimResponse(204));
        }

        // For normal requests, continue through stack and then add headers
        $response = $handler->handle($request);
        return $applyCorsHeaders($response);
    }
}
