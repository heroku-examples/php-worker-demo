<?php

$app = require(__DIR__.'/../app.php');

$app->register(new SilexGuzzle\GuzzleServiceProvider(), [
   'guzzle.base_uri' => 'https://bomberman-prod.herokuapp.com/api/v1/profanity/',
   'guzzle.timeout' => 5,
   'guzzle.request_options' => [
       'headers' => [
           'Authorization' => 'Token token='.getenv('BOMBERMAN_API_KEY'),
           'Accept' => 'application/json',
        ],
    ],
]);

$connection = $app['amqp']['default'];
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);

$app['monolog']->info('Worker ready for messages.');

$callback = function($msg) use($app) {
    $app['monolog']->debug('New task received for censoring message: ' . $msg->body);
    
    try {
        // call the "censor" API and pass it the text to clean up
        $result = $app['guzzle']->get('censor', ['query' => ['corpus' => $msg->body]]);
        $result = json_decode($result->getBody());
        if($result) {
            $app['monolog']->debug('Censored message result is: ' . $result->censored_text);
            // store in Redis
            $app['predis']->lpush('opinions', $result->censored_text);
            // mark as delivered in RabbitMQ
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        } else {
            $app['monolog']->warning('Failed to decode JSON, will retry later');
        }
    } catch(Exception $e) {
        $app['monolog']->warning('Failed to call API, will retry later');
    }
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('task_queue', '', false, false, false, false, $callback);

// loop over incoming messages
while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
