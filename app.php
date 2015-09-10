<?php

require_once __DIR__.'/vendor/autoload.php';

$app = new Silex\Application();

// set up Monolog to log to stderr
$app->register(new Silex\Provider\MonologServiceProvider(), [
    'monolog.logfile' => 'php://stderr',
    'monolog.level' => constant('Monolog\\Logger::'.strtoupper(getenv('LOG_LEVEL')?:'NOTICE')),
]);

// RabbitMQ connection
$rabbitmq = parse_url(getenv('CLOUDAMQP_URL'));
$app->register(new Amqp\Silex\Provider\AmqpServiceProvider, [
    'amqp.connections' => [
        'default' => [
            'host'     => $rabbitmq['host'],
            'port'     => isset($rabbitmq['port']) ? $rabbitmq['port'] : 5672,
            'username' => $rabbitmq['user'],
            'password' => $rabbitmq['pass'],
            'vhost'    => substr($rabbitmq['path'], 1) ?: '/',
        ],
    ],
]);

// Redis database
$app->register(new Predis\Silex\ClientServiceProvider(), [
    'predis.parameters' => getenv('REDIS_URL'),
]);

// return app object for use by other files
return $app;
