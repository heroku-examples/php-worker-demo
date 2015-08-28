<?php

use PhpAmqpLib\Message\AMQPMessage;

$app = require(__DIR__.'/../app.php');

$app->register(new Silex\Provider\TranslationServiceProvider(), [
    'translator.messages' => [],
]);
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), [
    'twig.path' => __DIR__.'/..',
    'twig.form.templates' => ['bootstrap_3_layout.html.twig'],
]);

$app->match('/', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {
    $opinions = $app['predis']->lrange('opinions', 0, 10);

    $form = $app['form.factory']->createBuilder('form')
        ->add('opinion', 'textarea', [
            'label' => 'Your opinion',
            'attr' => ['rows' => count($opinions)*2],
        ])
        ->getForm();
    $form->handleRequest($request);
    $submitted = false;
    if ($form->isValid()) {
        $data = $form->getData();

        $connection = $app['amqp']['default'];
        $channel = $connection->channel();
        $channel->queue_declare('task_queue', false, true, false, false);
        
        $msg = new AMQPMessage($data['opinion'], ['delivery_mode' => 2]);
        $channel->basic_publish($msg, '', 'task_queue');

        $channel->close();
        $connection->close();

        $submitted = true;
    }

    return $app['twig']->render('index.twig', [
        'form' => $form->createView(),
        'submitted' => $submitted,
        'opinions' => $opinions
    ]);
});

$app->run();
