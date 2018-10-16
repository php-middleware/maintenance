<?php

namespace PhpMiddlewareTest\Maintenance;

use DateTime;
use Exception;
use PhpMiddleware\Maintenance\MaintenanceMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;

class MaintenanceMiddlewareTest extends TestCase
{
    private $request;
    private $responseFactory;

    protected function setUp()
    {
        $this->request = new ServerRequest();
        $this->responseFactory = new ResponseFactory();
    }

    public function testWithoutRetry()
    {
        $middleware = MaintenanceMiddleware::create($this->responseFactory);

        $response = $middleware->handle($this->request);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('Retry-After'));
    }

    public function testWithoutRetryAsMiddleware()
    {
        $middleware = MaintenanceMiddleware::create($this->responseFactory);

        $requestHandler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

        $response = $middleware->process($this->request, $requestHandler);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('Retry-After'));
    }

    public function testWithRetryAsSeconds()
    {
        $middleware = MaintenanceMiddleware::createWithRetryAsSeconds(3600, $this->responseFactory);

        $response = $middleware->handle($this->request);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('3600', $response->getHeaderLine('Retry-After'));
        $this->assertSame('', $response->getHeaderLine('Refresh'));
    }

    public function testWithRetryAsSecondsWithRefresh()
    {
        $middleware = MaintenanceMiddleware::createWithRetryAsSecondsAndRefresh(3600, $this->responseFactory);

        $response = $middleware->handle($this->request);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('3600', $response->getHeaderLine('Retry-After'));
        $this->assertSame('3600', $response->getHeaderLine('Refresh'));
    }

    public function testWithRetryAsDatetime()
    {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', '2015-11-30 11:12:13');

        $middleware = MaintenanceMiddleware::createWithRetryAsDateTime($date, $this->responseFactory);

        $response = $middleware->handle($this->request);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('Mon, 30 Nov 2015 11:12:13 +0000', $response->getHeaderLine('Retry-After'));
        $this->assertSame('', $response->getHeaderLine('Refresh'));
    }

    public function testWithRetryAsDatetimeWithRefresh()
    {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', '2015-11-30 11:12:13');

        $middleware = MaintenanceMiddleware::createWithRetryAsDateTimeAndRefresh($date, $this->responseFactory);

        $response = $middleware->handle($this->request);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('Mon, 30 Nov 2015 11:12:13 +0000', $response->getHeaderLine('Retry-After'));
        $this->assertGreaterThan(1000, (int) $response->getHeaderLine('Refresh'));
    }
}
