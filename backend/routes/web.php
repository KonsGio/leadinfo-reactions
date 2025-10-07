<?php

/**
 * Routes: only request/response wiring here.
 * - No business logic in routes, controllers/services handle that.
 * - Keep paths and HTTP verbs obvious for the frontend team.
 * - Return JSON or problem+json only; no HTML here.
 */

declare(strict_types=1);

use Slim\App;
use App\Http\Controllers\ReactionController;
use App\Http\Response\ResponseFactory as JsonResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app): void {
    $container = $app->getContainer();
    $json = $container->get(JsonResponseFactory::class);

    // health
    $app->get('/api/health', function (Request $request, Response $result) use ($json) {
        return $json->json($result, ['status' => 'ok'], 200);
    });

    // CORS preflight catch-all (CORS middleware already short-circuits)
    $app->options('/{routes:.+}', fn(Request $request, Response $result) => $result->withStatus(204));

    // reactions
    $app->get('/api/reactions', [ReactionController::class, 'index']);
    $app->post('/api/reactions', [ReactionController::class, 'store']);
};