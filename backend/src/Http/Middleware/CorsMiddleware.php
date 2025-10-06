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
final class CorsMiddleware {

    /**
     * @param array|string $allowed
     */
    public function __construct(private array|string $allowed) {
        $this->allowed = is_array($allowed) ? $allowed : [$allowed];
    }

    /**
     * @param string|null $reqOrigin
     * @return string|null
     */
    private function pickOrigin(?string $reqOrigin): ?string {
        if (!$reqOrigin) return null;
        foreach ($this->allowed as $o) {
            if (strcasecmp($o, $reqOrigin) === 0 || $o === '*') return $reqOrigin;
        }
        // if '*' present, reflect reqOrigin (no credentials)
        return in_array('*', $this->allowed, true) ? '*' : null;
    }

    /**
     * @param $request
     * @param $handler
     * @return mixed
     */
    public function __invoke($request, $handler) {
        $reqOrigin = $request->getHeaderLine('Origin') ?: null;
        $allow = $this->pickOrigin($reqOrigin);

        $add = function ($res) use ($allow) {
            if ($allow) {
                $res = $res->withHeader('Access-Control-Allow-Origin', $allow)
                    ->withHeader('Vary', 'Origin');
            }
            return $res->withHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With')
                ->withHeader('Access-Control-Max-Age', '86400');
        };

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $add(new SlimResponse(204));
        }

        return $add($handler->handle($request));
    }
}
