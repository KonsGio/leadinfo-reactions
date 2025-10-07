<?php

/**
 * CORS guard.
 * - Adds Access-Control-* headers to all responses.
 * - Returns 204 immediately for OPTIONS preflight.
 * - Keeping this outermost so every response is CORS-friendly.
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

final class CorsMiddleware
{
    /** @var string[]|'*' */
    private array|string $allowList;

    /**
     * @param string $originsCsvOrStar
     */
    public function __construct(string $originsCsvOrStar)
    {
        $originsCsvOrStar = trim($originsCsvOrStar);
        $this->allowList = $originsCsvOrStar === '*' ? '*' : array_values(array_filter(
            array_map('trim', explode(',', $originsCsvOrStar))
        ));
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
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigin = $this->resolveAllowedOrigin($origin);

        $apply = static function (Response $res) use ($allowedOrigin): Response {
            if ($allowedOrigin !== null) {
                $res = $res->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
                    ->withHeader('Vary', 'Origin');
            }
            return $res
                ->withHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With')
                ->withHeader('Access-Control-Max-Age', '86400');
        };

        // preflight: answer immediately
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $apply(new SlimResponse(204));
        }

        $response = $handler->handle($request);
        return $apply($response);
    }

    /**
     * @param string|null $origin
     * @return string|null
     */
    private function resolveAllowedOrigin(?string $origin): ?string
    {
        if ($origin === null || $origin === '') return null;

        if ($this->allowList === '*') {
            return $origin;
        }

        // exact matches
        if (in_array($origin, $this->allowList, true)) return $origin;

        //allow any localhost port
        $bareLocalhostAllowed = in_array('http://localhost', $this->allowList, true);
        $bare127Allowed       = in_array('http://127.0.0.1', $this->allowList, true);

        if ($bareLocalhostAllowed && str_starts_with($origin, 'http://localhost:')) return $origin;
        if ($bare127Allowed       && str_starts_with($origin, 'http://127.0.0.1:')) return $origin;

        return null;
    }
}