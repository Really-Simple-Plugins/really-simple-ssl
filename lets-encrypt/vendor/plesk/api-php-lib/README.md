## PHP library for Plesk XML-RPC API

[![Build Status](https://travis-ci.com/plesk/api-php-lib.svg?branch=master)](https://travis-ci.com/plesk/api-php-lib) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/plesk/api-php-lib/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/plesk/api-php-lib/?branch=master)
[![StyleCI](https://styleci.io/repos/26514840/shield?branch=master)](https://styleci.io/repos/26514840)

PHP object-oriented library for Plesk XML-RPC API.

## Install Via Composer

[Composer](https://getcomposer.org/) is a preferable way to install the library:

`composer require plesk/api-php-lib`

## Usage Examples

Here is an example on how to use the library and create a customer with desired properties:
```php
$client = new \PleskX\Api\Client($host);
$client->setCredentials($login, $password);

$client->customer()->create([
    'cname' => 'Plesk',
    'pname' => 'John Smith',
    'login' => 'john',
    'passwd' => 'secret',
    'email' => 'john@smith.com',
]);
```

It is possible to use a secret key instead of password for authentication.

```php
$client = new \PleskX\Api\Client($host);
$client->setSecretKey($secretKey)
```

In case of Plesk extension creation one can use an internal mechanism to access XML-RPC API. It does not require to pass authentication because the extension works in the context of Plesk.

```php
$client = new \PleskX\Api\InternalClient();
$protocols = $client->server()->getProtos();
```

For additional examples see tests/ directory.

## How to Run Unit Tests

One the possible ways to become familiar with the library is to check the unit tests.

To run the unit tests use the following command:

`REMOTE_HOST=your-plesk-host.dom REMOTE_PASSWORD=password composer test`

To use custom port one can provide a URL (e.g. for Docker container):

`REMOTE_URL=https://your-plesk-host.dom:port REMOTE_PASSWORD=password composer test`

One more way to run tests is to use Docker:

`docker-compose run tests`

## Continuous Testing

During active development it could be more convenient to run tests in continuous manner. Here is the way how to achieve it:

`REMOTE_URL=https://your-plesk-host.dom:port REMOTE_PASSWORD=password composer test:watch`
