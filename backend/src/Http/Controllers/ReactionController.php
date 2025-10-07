<?php

/**
 * Reactions controller (thin).
 * - Reads inputs from query/body.
 * - Delegates to ReactionService for business rules.
 * - Uses ResponseFactory helpers: ok(), created(), invalid().
 * - No DB or validation here that lives in service/repo/validator.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Service\ReactionService;
use App\Http\Response\ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

final class ReactionController
{
    private ReactionService $reactionService;
    private ResponseFactory $responseFactory;

    /**
     * @param ReactionService $reactionService
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        ReactionService $reactionService,
        ResponseFactory $responseFactory
    )
    {
        $this->responseFactory = $responseFactory;
        $this->reactionService = $reactionService;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function index(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams();
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = min(max(1, (int)($query['limit'] ?? 3)), 50);

        $result = $this->reactionService->list($page, $perPage);
        return $this->responseFactory->json($response, $result, 200);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function store(Request $request, Response $response): Response
    {
        $payload = $request->getParsedBody();
        if (!is_array($payload)) $payload = [];

        $result = $this->reactionService->create($payload);

        if (!$result->ok) {
            return $this->responseFactory->problem($response, 422, 'Validation Failed', [
                'errors' => $result->errors,
            ]);
        }

        return $this->responseFactory->json($response, ['id' => $result->id], 201);
    }
}