<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Response\ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * For write methods, require Content-Type: application/json.
 * Skips GET/HEAD/OPTIONS.
 */
final readonly class RequireJsonMiddleware
{
    /**
     * @param ResponseFactory $responseFactory
     */
    public function __construct(private ResponseFactory $responseFactory)
    {
    }

    /**
     *  __invoke() is used by Slim automatically, no manual call needed.
     *
     * @param Request $request
     * @param Handler $handler
     * @return Response
     */
    public function __invoke(Request $request, Handler $handler): Response
    {
        $method = strtoupper($request->getMethod());
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $handler->handle($request);
        }

        $content = $request->getHeaderLine('Content-Type');
        if (stripos($content, 'application/json') !== 0) {
            return $this->responseFactory->problem(
                $this->responseFactory->empty(),
                415,
                'Unsupported Media Type',
                ['detail' => 'Use Content-Type: application/json']
            );
        }

        return $handler->handle($request);
    }
}