<?php

namespace PhpMiddleware\Maintenance;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class MaintenanceMiddleware
{
    /**
     * @var string
     */
    private $retryAfter = '';

    /**
     * @var int
     */
    private $refresh = 0;

    private function __construct()
    {
    }

    /**
     * @return \self
     */
    public static function create()
    {
        return new self();
    }

    /**
     * @param int $seconds
     *
     * @return self
     */
    public static function createWithRetryAsSeconds($seconds)
    {
        if (!is_int($seconds)) {
            throw new \InvalidArgumentException('Seconds must be integer');
        }
        $instance = new self();
        $instance->retryAfter = (string) $seconds;

        return $instance;
    }

    /**
     * @param int $seconds
     *
     * @return self
     */
    public static function createWithRetryAsSecondsAndRefresh($seconds)
    {
        $instance = self::createWithRetryAsSeconds($seconds);
        $instance->refresh = $seconds;

        return $instance;
    }

    /**
     * @param \DateTimeInterface $datetime
     *
     * @return self
     */
    public static function createWithRetryAsDateTime(\DateTimeInterface $datetime)
    {
        $instance = new self();

        $instance->retryAfter = $datetime->format(\DateTime::RFC2822);

        return $instance;
    }

    /**
     * @param \DateTimeInterface $datetime
     *
     * @return self
     */
    public static function createWithRetryAsDateTimeAndRefresh(\DateTimeInterface $datetime)
    {
        $instance = self::createWithRetryAsDateTime($datetime);
        $diff = time() - $datetime->getTimestamp();
        $instance->refresh = $diff;

        return $instance;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if ($this->retryAfter !== '') {
            $response = $response->withHeader('Retry-After', $this->retryAfter);

            if ($this->refresh > 0) {
                $response = $response->withHeader('Refresh', (string) $this->refresh);
            }
        }

        return $response->withStatus(503);
    }
}
