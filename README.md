# maintenance [![Build Status](https://travis-ci.org/php-middleware/maintenance.svg?branch=master)](https://travis-ci.org/php-middleware/maintenance)
Site maintenance middleware SEO friendly

## How to usage

Create instance of middleware as you want (we use [named constructors](http://verraes.net/2014/06/named-constructors-in-php/)) and add it to middleware runner.

```php
$date = DateTime::createFromFormat('Y-m-d H:i:s', '2025-11-30 11:12:13');

$middleware = MaintenanceMiddleware::createWithRetryAsDateTime($date, $psr17ResponseFactory);

$middlewareRunner->add(middleware);
$middlewareRunner->run();
```

## Features

* Setup 503 status code,
* Supports `Retry-After` header (as seconds or HTTP-date),
* Supports `Redirect` header (redirect page after `Retry-After` time).

More about this SEO practice: [How to deal with planned site downtime](http://googlewebmastercentral.blogspot.com/2011/01/how-to-deal-with-planned-site-downtime.html) in Google Webmaster Central Blog.

## How to install

Use composer!

```bash
composer require php-middleware/maintenance
```

This package require [PSR-17 message factory](https://packagist.org/providers/psr/http-factory-implementation) implementation to return SEO friendly response.