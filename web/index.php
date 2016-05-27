<?php

require('../vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use IndicoIo\IndicoIo as IndicoIo;

IndicoIo::$config['api_key']  = '22e41cde696fcf15bc67f06319f6ffe6';
IndicoIo::$config['language'] = 'japanese';

$app = new Silex\Application();
$bot = new CU\LineBot();

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));

$app->before(function (Request $request) use($bot) {
    // Signature validation
    $request_body = $request->getContent();
    $signature = $request->headers->get('X-LINE-CHANNELSIGNATURE');
    if (!$bot->isValid($signature, $request_body)) {
        return new Response('Signature validation failed.', 400);
    }
});

$app->post('/callback', function (Request $request) use ($app, $bot) {
    // Let's hack from here!
    $body = json_decode($request->getContent(), true);

    foreach ($body['result'] as $obj) {
        $app['monolog']->addInfo(sprintf('obj: %s', json_encode($obj)));
        $from = $obj['content']['from'];
        $content = $obj['content'];

        if ($content['text']) {
            //$bot->sendText($from, sprintf('%sじゃないよ、もう', $content['text']));
            $res_ana = IndicoIo::sentiment($content['text']);
            $bot->sendText($from, round($res_ana*100)."％のポジ発言");
        }
    }

    return 0;
});

$app->run();
