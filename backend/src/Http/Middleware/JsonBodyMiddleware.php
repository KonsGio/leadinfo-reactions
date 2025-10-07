<?php

/**
 * JSON parser + 400 handler.
 * - Parses JSON into `$request->getParsedBody()`.
 * - If JSON is invalid → 400 with problem+json (clean message).
 * - This runs after RequireJsonMiddleware so we only parse when type is right.
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Response\ResponseFactory;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Parses JSON request bodies. On invalid JSON → 400 problem+json.
 */
final class JsonBodyMiddleware
{
    /**
     * @param ResponseFactory $json
     */
    public function __construct(private ResponseFactory $json)
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
        $content = $request->getHeaderLine('Content-Type');
        if ($content && str_starts_with(strtolower($content), 'application/json')) {
            $raw = (string)$request->getBody();
            if ($raw !== '') {
                try {
                    /** @var mixed $decoded */
                    $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
                } catch (Exception) {
                    return $this->json->problem(
                        $this->json->empty(),
                        400,
                        'Invalid JSON',
                        ['detail' => 'Request body is not valid JSON']
                    );
                }
                if (!is_array($decoded)) {
                    return $this->json->problem(
                        $this->json->empty(),
                        400,
                        'Invalid JSON',
                        ['detail' => 'JSON payload must be an object']
                    );
                }
                $request = $request->withParsedBody($decoded);
            }
        }
        return $handler->handle($request);
    }
}