<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Response\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\CallableResolverInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Handlers\ErrorHandler;

/**
 * Custom error handler that emits RFC7807 "problem+json" responses.
 *
 */
final class ProblemDetailsHandler extends ErrorHandler
{
    /**
     * @param CallableResolverInterface $callableResolver
     * @param ResponseFactoryInterface $responseFactory
     * @param LoggerInterface $logger
     * @param ResponseFactory $responseHelper
     */
    public function __construct(
        CallableResolverInterface        $callableResolver,
        ResponseFactoryInterface         $responseFactory,
        protected LoggerInterface        $logger,
        private readonly ResponseFactory $responseHelper
    )
    {
        parent::__construct($callableResolver, $responseFactory);
    }

    /**
     * Build a standardized RFC7807 problem+json response.
     *
     * @return ResponseInterface
     */
    protected function respond(): ResponseInterface
    {
        $status    = $this->statusCode ?: 500;
        $exception = $this->exception;
        $req       = $this->request;
        $rid       = $req?->getAttribute('request_id');

        $this->logger->error('unhandled_exception', [
            'request_id' => $rid,
            'status'     => $status,
            'message'    => $exception?->getMessage(),
            'method'     => $req?->getMethod(),
            'path'       => $req?->getUri()->getPath(),
        ]);

        return $this->responseHelper->problem(
            $this->responseHelper->empty(),
            $status,
            $status >= 500 ? 'Server Error' : 'Request Error',
            [
                'detail'   => $exception?->getMessage(),
                'instance' => $rid ? ('urn:req:' . $rid) : null,
            ]
        );
    }
}
