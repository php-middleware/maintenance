<?php

namespace PhpMiddleware\Maintenance;

use DateTimeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MaintenanceMiddleware implements RequestHandlerInterface, MiddlewareInterface
{
    /**
     * @var string
     */
    private $retryAfter = '';

    /**
     * @var int
     */
    private $refresh = 0;
    private $responseFactory;

    private function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public static function create(ResponseFactoryInterface $responseFactory): self
    {
        return new self($responseFactory);
    }

    public static function createWithRetryAsSeconds(int $seconds, ResponseFactoryInterface $responseFactory): self
    {
        $instance = new self($responseFactory);
        $instance->retryAfter = (string) $seconds;

        return $instance;
    }

    public static function createWithRetryAsSecondsAndRefresh(int $seconds, ResponseFactoryInterface $responseFactory): self
    {
        $instance = self::createWithRetryAsSeconds($seconds, $responseFactory);
        $instance->refresh = $seconds;

        return $instance;
    }

    public static function createWithRetryAsDateTime(DateTimeInterface $datetime, ResponseFactoryInterface $responseFactory): self
    {
        $instance = new self($responseFactory);
        $instance->retryAfter = $datetime->format(DateTimeInterface::RFC2822);

        return $instance;
    }

    public static function createWithRetryAsDateTimeAndRefresh(DateTimeInterface $datetime, ResponseFactoryInterface $responseFactory)
    {
        $instance = self::createWithRetryAsDateTime($datetime, $responseFactory);
        $diff = time() - $datetime->getTimestamp();
        $instance->refresh = $diff;

        return $instance;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(503);

        if ($this->retryAfter !== '') {
            $response = $response->withHeader('Retry-After', $this->retryAfter);

            if ($this->refresh > 0) {
                $response = $response->withHeader('Refresh', (string) $this->refresh);
            }
        }

        return $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->handle($request);
    }
}
