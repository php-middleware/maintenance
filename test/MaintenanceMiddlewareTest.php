<?php

namespace PhpMiddlewareTest\Maintenance;

use DateTime;
use Exception;
use PhpMiddleware\Maintenance\MaintenanceMiddleware;
use PHPUnit_Framework_TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class MaintenanceMiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function testWithoutRetry()
    {
        $request = new ServerRequest();
        $response = new Response();

        $middleware = MaintenanceMiddleware::create();

        $next = function() {
            throw new Exception('Next should not be called');
        };

        $response = $middleware->__invoke($request, $response, $next);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('Retry-After'));
    }

    public function testWithRetryAsSeconds()
    {
        $request = new ServerRequest();
        $response = new Response();

        $middleware = MaintenanceMiddleware::createWithRetryAsSeconds(3600);

        $next = function() {
            throw new Exception('Next should not be called');
        };

        $response = $middleware->__invoke($request, $response, $next);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('3600', $response->getHeaderLine('Retry-After'));
        $this->assertSame('', $response->getHeaderLine('Refresh'));
    }

    public function testWithRetryAsSecondsWithRefresh()
    {
        $request = new ServerRequest();
        $response = new Response();

        $middleware = MaintenanceMiddleware::createWithRetryAsSecondsAndRefresh(3600);

        $next = function() {
            throw new Exception('Next should not be called');
        };

        $response = $middleware->__invoke($request, $response, $next);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('3600', $response->getHeaderLine('Retry-After'));
        $this->assertSame('3600', $response->getHeaderLine('Refresh'));
    }

    public function testWithRetryAsDatetime()
    {
        $request = new ServerRequest();
        $response = new Response();

        $date = DateTime::createFromFormat('Y-m-d H:i:s', '2015-11-30 11:12:13');

        $middleware = MaintenanceMiddleware::createWithRetryAsDateTime($date);

        $next = function() {
            throw new Exception('Next should not be called');
        };

        $response = $middleware->__invoke($request, $response, $next);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('Mon, 30 Nov 2015 11:12:13 +0000', $response->getHeaderLine('Retry-After'));
        $this->assertSame('', $response->getHeaderLine('Refresh'));
    }

    public function testWithRetryAsDatetimeWithRefresh()
    {
        $request = new ServerRequest();
        $response = new Response();

        $date = DateTime::createFromFormat('Y-m-d H:i:s', '2015-11-30 11:12:13');

        $middleware = MaintenanceMiddleware::createWithRetryAsDateTimeAndRefresh($date);

        $next = function() {
            throw new Exception('Next should not be called');
        };

        $response = $middleware->__invoke($request, $response, $next);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('Mon, 30 Nov 2015 11:12:13 +0000', $response->getHeaderLine('Retry-After'));
        $this->assertGreaterThan(1000, (int) $response->getHeaderLine('Refresh'));
    }
}
