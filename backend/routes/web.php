<?php
declare(strict_types=1);

use Slim\App;
use App\Http\Controllers\ReactionController;
use App\Http\Response\ResponseFactory as JsonResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app): void {
    $container = $app->getContainer();
    $json = $container->get(JsonResponseFactory::class);

    // Health
    $app->get('/api/health', function (Request $request, Response $result) use ($json) {
        return $json->json($result, ['status' => 'ok'], 200);
    });

    // CORS preflight catch-all (defensive; CORS middleware already short-circuits)
    $app->options('/{routes:.+}', fn(Request $request, Response $result) => $result->withStatus(204));

    // Reactions
    $app->get('/api/reactions', [ReactionController::class, 'index']);
    $app->post('/api/reactions', [ReactionController::class, 'store']);
};