<?php
declare(strict_types=1);

use App\Domain\Repository\ReactionRepository;
use App\Domain\Service\ReactionService;
use App\Domain\Validation\ReactionValidator;
use App\Http\Controllers\ReactionController;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\JsonBodyMiddleware;
use App\Http\Middleware\LimitBodySizeMiddleware;
use App\Http\Middleware\ProblemDetailsHandler;
use App\Http\Middleware\RequireJsonMiddleware;
use App\Http\Response\ResponseFactory;
use DI\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;

return (function () {
    $container = new Container();

    // config (env-backed)
    $config = [
        'db' => [
            'driver' => getenv('DB_DRIVER') ?: 'mysql',
            'host' => getenv('DB_HOST') ?: 'db',
            'port' => (int)(getenv('DB_PORT') ?: 3306),
            'name' => getenv('DB_DATABASE') ?: 'leadinfo',
            'user' => getenv('DB_USERNAME') ?: 'root',
            'pass' => getenv('DB_PASSWORD') ?: 'secret',
            'charset' => 'utf8mb4',
        ],
        'app' => [
            'origin' => getenv('APP_ORIGIN') ?: 'http://localhost:5173',
            'debug' => filter_var(getenv('APP_DEBUG') ?: '1', FILTER_VALIDATE_BOOLEAN),
            'max_body_bytes' => (int)(getenv('APP_MAX_BODY') ?: 1_000_000),
            'log_dir' => __DIR__ . '/../storage/logs',
        ],
    ];
    $container->set('config', $config);

    // app logger (warnings+ to app-YYYY-MM-DD.log)
    $container->set(LoggerInterface::class, static function () use ($config): LoggerInterface {
        $dir = $config['app']['log_dir'];
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $file = $dir . '/app-' . date('Y-m-d') . '.log';
        $log = new Logger('api');
        $log->pushHandler(new StreamHandler($file, Level::Warning));
        return $log;
    });

    // access logger (info+ to access-YYYY-MM-DD.log)
    $container->set('access_logger', static function () use ($config): LoggerInterface {
        $dir = $config['app']['log_dir'];
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $file = $dir . '/access-' . date('Y-m-d') . '.log';
        $log = new Logger('access');
        $log->pushHandler(new StreamHandler($file, Level::Info));
        return $log;
    });


    // PDO
    $container->set(PDO::class, static function (Container $c): PDO {
        $db = $c->get('config')['db'];
        $dsn = sprintf('%s:host=%s;port=%d;dbname=%s;charset=%s',
            $db['driver'], $db['host'], $db['port'], $db['name'], $db['charset']);
        return new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    });

    // response helper
    $container->set(ResponseFactory::class, static function (Container $c): ResponseFactory {
        $logger = $c->get(LoggerInterface::class);
        $dir = $c->get('config')['app']['log_dir'];
        return new ResponseFactory($logger, $dir);
    });

    // domain wiring
    $container->set(ReactionRepository::class, fn(Container $c) => new ReactionRepository($c->get(PDO::class))
    );
    $container->set(ReactionService::class, fn(Container $c) => new ReactionService($c->get(ReactionRepository::class), new ReactionValidator())
    );
    $container->set(ReactionController::class, fn(Container $c) => new ReactionController($c->get(ReactionService::class), $c->get(ResponseFactory::class))
    );

    // app
    AppFactory::setContainer($container);
    $app = AppFactory::create();
    $app->addRoutingMiddleware();

    // errors -> problem+json
    $config = $config['app'];
    $logger = $container->get(LoggerInterface::class);
    $errors = $app->addErrorMiddleware($config['debug'], true, true, $logger);
    $errors->setDefaultErrorHandler(new ProblemDetailsHandler(
        $app->getCallableResolver(),
        $app->getResponseFactory(),
        $logger,
        $container->get(ResponseFactory::class)
    ));

    // request/response pipeline (outer → inner)
    $json = $container->get(ResponseFactory::class);

    $app->add(new CorsMiddleware($config['origin']));
    $app->add(new LimitBodySizeMiddleware($config['max_body_bytes'], $json));
    $app->add(new RequireJsonMiddleware($json));
    $app->add(new JsonBodyMiddleware($json));

    // routes
    (require __DIR__ . '/../routes/web.php')($app);

    return $app;
})();