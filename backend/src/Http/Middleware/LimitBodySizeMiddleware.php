<?php
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
     * @param ResponseFactory $json
     */
    public function __construct(
        private readonly int             $maxBytes,
        private readonly ResponseFactory $json
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
            return $this->json->problem(
                $this->json->empty(),
                413,
                'Payload Too Large',
                ['detail' => 'Request exceeds size limit']
            );
        }
        return $handler->handle($request);
    }
}