<?php

/**
 * Payload size guard.
 * - Rejects bodies larger than the configured limit.
 * - Fails fast with 413 (problem+json) before we parse anything.
 * - Good for DOS protection and noisy clients.
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Response\ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Rejects requests with bodies larger than a configured byte limit.
 */
final class LimitBodySizeMiddleware
{
    /**
     * @param int $maxBytes
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        private int             $maxBytes,
        private ResponseFactory $responseFactory
    )
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
        $length = (int)($request->getHeaderLine('Content-Length') ?: 0);
        if ($this->maxBytes > 0 && $length > $this->maxBytes) {
            return $this->responseFactory->problem(
                $this->responseFactory->empty(),
                413,
                'Payload Too Large',
                ['detail' => 'Request exceeds size limit']
            );
        }
        return $handler->handle($request);
    }
}