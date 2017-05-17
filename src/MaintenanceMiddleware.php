<?php

namespace PhpMiddleware\Maintenance;

use DateTime;
use DateTimeInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use InvalidArgumentException;
use PhpMiddleware\DoublePassCompatibilityTrait;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

final class MaintenanceMiddleware implements MiddlewareInterface
{
    use DoublePassCompatibilityTrait;

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
     * @return self
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
            throw new InvalidArgumentException('Seconds must be integer');
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
     * @param DateTimeInterface $datetime
     *
     * @return self
     */
    public static function createWithRetryAsDateTime(DateTimeInterface $datetime)
    {
        $instance = new self();

        $instance->retryAfter = $datetime->format(DateTime::RFC2822);

        return $instance;
    }

    /**
     * @param DateTimeInterface $datetime
     *
     * @return self
     */
    public static function createWithRetryAsDateTimeAndRefresh(DateTimeInterface $datetime)
    {
        $instance = self::createWithRetryAsDateTime($datetime);
        $diff = time() - $datetime->getTimestamp();
        $instance->refresh = $diff;

        return $instance;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $headers = [];

        if ($this->retryAfter !== '') {
            $headers['Retry-After'] = $this->retryAfter;

            if ($this->refresh > 0) {
                $headers['Refresh'] = (string) $this->refresh;
            }
        }

        return new Response('php://memory', 503, $headers);
    }

}
