<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\JsonBodyMiddleware;
use App\Http\Middleware\LimitBodySizeMiddleware;
use App\Http\Middleware\ProblemDetailsHandler;
use App\Http\Middleware\RequireJsonMiddleware;
use App\Http\Response\ResponseFactory;

/**
 * Build a fresh Slim app for every test.
 * Uses in-memory SQLite so tests are isolated and fast.
 *
 * @return App
 * @throws DependencyException
 * @throws NotFoundException
 */
function make_test_app(): App
{
    $container = new Container();

    // Minimal config for tests
    $container->set('config', [
        'app' => [
            'debug' => true,
            'origin' => 'http://localhost:5173',
        ],
    ]);

    // In-memory SQLite schema
    $container->set(PDO::class, function () {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(<<<SQL
            CREATE TABLE reactions (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              name TEXT NOT NULL,
              email TEXT NOT NULL,
              title TEXT NOT NULL,
              message TEXT NOT NULL,
              rating INTEGER NOT NULL,
              created_at TEXT NOT NULL
            );
        SQL
        );
        return $pdo;
    });

    $container->set(LoggerInterface::class, function (): LoggerInterface {
        $logDir = __DIR__ . '/../storage/test-logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $file = $logDir . '/test-' . date('Y-m-d') . '.log';

        $logger = new Logger('test');
        // Log everything for tests, including INFO (successful messages)
        $logger->pushHandler(new StreamHandler($file, Logger::INFO));

        // (optional) also mirror to stderr, handy in CI
        $logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));

        return $logger;
    });

    $container->set('access_logger', function () {
        // write test access logs to /tmp to avoid polluting repo logs
        $file = sys_get_temp_dir() . '/leadinfo-access-' . date('Y-m-d') . '.log';
        $log = new \Monolog\Logger('access');
        $log->pushHandler(new \Monolog\Handler\StreamHandler($file, \Monolog\Level::Info));
        return $log;
    });

    // Slim app
    AppFactory::setContainer($container);
    $app = AppFactory::create();

    // Important for route matching
    $app->addRoutingMiddleware();

    // Error handling -> RFC7807 via our ProblemDetailsHandler
    $logger = $container->get(LoggerInterface::class);
    $errors = $app->addErrorMiddleware(true, true, true, $logger);
    $errors->setDefaultErrorHandler(new ProblemDetailsHandler(
        $app->getCallableResolver(),
        $app->getResponseFactory(),
        $logger,
        $container->get(ResponseFactory::class)
    ));

    // Guards (same order as runtime)
    $jsonResponder = $container->get(ResponseFactory::class);
    $allowedOrigin = $container->get('config')['app']['origin'];

    $app->add(new CorsMiddleware($allowedOrigin));                    // outer
    $app->add(new LimitBodySizeMiddleware(1_000_000, $jsonResponder));
    $app->add(new RequireJsonMiddleware($jsonResponder));             // 415 for wrong Content-Type on mutating methods
    $app->add(new JsonBodyMiddleware($jsonResponder));                // 400 for malformed JSON

    // Routes (shared with real app)
    (require __DIR__ . '/../routes/web.php')($app);

    return $app;
}