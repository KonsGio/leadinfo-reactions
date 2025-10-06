<?php
declare(strict_types=1);

namespace App\Http\Response;

use Psr\Log\LoggerInterface;
use Slim\Psr7\Response;

final class ResponseFactory
{
    private LoggerInterface $logger;

    //private ?string $logDir;

    /**
     * @param LoggerInterface $logger
     * @param string|null $logDir
     */
    public function __construct(LoggerInterface $logger, ?string $logDir = null)
    {
        $this->logger = $logger;
        //$this->logDir = $logDir; // reserved for future file-based logging if needed
    }

    /**
     * @param int $status
     * @return Response
     */
    public function empty(int $status = 200): Response
    {
        return new Response($status);
    }

    /**
     * @param Response $res
     * @param array $data
     * @param int $status
     * @return Response
     */
    public function json(Response $res, array $data, int $status = 200): Response
    {
        $res->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES));
        $res = $res->withHeader('Content-Type', 'application/json')->withStatus($status);

        $this->logForStatus($status, 'json', $data);
        return $res;
    }

    /**
     * @param Response $res
     * @param int $status
     * @param string $title
     * @param array $extra
     * @return Response
     */
    public function problem(Response $res, int $status, string $title, array $extra = []): Response
    {
        $payload = array_merge([
            'type' => 'about:blank',
            'title' => $title,
            'status' => $status,
        ], $extra);

        $res->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $res = $res->withHeader('Content-Type', 'application/problem+json')->withStatus($status);

        $this->logForStatus($status, 'problem', $payload);
        return $res;
    }

    /**
     * Compact structured logging without dumping full bodies
     *
     * @param int $status
     * @param string $kind
     * @param array $payload
     * @return void
     */
    private function logForStatus(int $status, string $kind, array $payload): void
    {
        $summary = ['kind' => $kind, 'status' => $status];
        if (isset($payload['title'])) {
            $summary['title'] = $payload['title'];
        }
        if (isset($payload['errors'])) {
            $summary['errors'] = array_keys($payload['errors']);
        }
        if (isset($payload['type'])) {
            $summary['type'] = $payload['type'];
        }

        if ($status >= 500) {
            $this->logger->error('response', $summary);
        } elseif ($status >= 400) {
            $this->logger->warning('response', $summary);
        } else {
            $this->logger->info('response', $summary);
        }
    }
}