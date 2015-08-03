<?php

$autoLoader = require_once __DIR__ . '/../vendor/autoload.php';
$autoLoader->add('Yandex\\Kassa\\HttpNotification\\', __DIR__ . '/../src/');
$autoLoader->register();

use Symfony\Component\HttpFoundation\Request;
use Yandex\Kassa\HttpNotification\PaymentMethod;

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

$app = new Silex\Application();
$app['debug'] = true;

$app['kassa.config'] = include __DIR__ . '/../config.php';

$makeCheckData = function ($requestData) use ($app) {
    $params = [
        'requestDatetime'         => date('Y-m-d\\TH:i:sP'),
        'action'                  => 'checkOrder',
        'shopId'                  => $requestData['shopId'],
        'invoiceId'               => '1234567',
        'customerNumber'          => $requestData['customerNumber'],
        'orderCreatedDatetime'    => date('Y-m-d\\TH:i:sP'),
        'orderSumAmount'          => round($requestData['sum'], 2),
        'orderSumCurrencyPaycash' => \Yandex\Kassa\HttpNotification\CurrencyCode::TEST,
        'orderSumBankPaycash'     => \Yandex\Kassa\HttpNotification\CurrencyBank::TEST,
        'shopSumAmount'           => round($requestData['sum'] * 0.98, 2),
        'shopSumCurrencyPaycash'  => \Yandex\Kassa\HttpNotification\CurrencyCode::TEST,
        'shopSumBankPaycash'      => \Yandex\Kassa\HttpNotification\CurrencyBank::TEST,
        'paymentPayerCode'        => '42007148320',
        'paymentType'             => PaymentMethod::normalize(isset($requestData['paymentType']) ? $requestData['paymentType'] : ''),
        //'MyField'                 => 'Добавленное Контрагентом поле',
    ];
    isset($requestData['shopArticleId']) && $params['shopArticleId'] = $requestData['shopArticleId'];
    $checkData = \Yandex\Kassa\HttpNotification\CheckOrderParams::createWithArray($params);
    $params['md5'] = $checkData->signWithPassword($app['kassa.config']['shopPassword']);
    return $params;
};

$makeAvisoData = function ($requestData) use ($app, $makeCheckData) {
    $params = $makeCheckData($requestData);
    $params['action'] = 'paymentAviso';
    $params['paymentDatetime'] = date('Y-m-d\\TH:i:sP');
    $checkData = \Yandex\Kassa\HttpNotification\CheckOrderParams::createWithArray($params);
    $params['md5'] = $checkData->signWithPassword($app['kassa.config']['shopPassword']);
    return $params;
};

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app->match('/embed/shop.xml', function (Request $request) use ($app, $makeCheckData, $makeAvisoData) {
    $requestData = $request->request->getIterator()->getArrayCopy();
    $checkData = $makeCheckData($requestData);
    $avisoData = $makeAvisoData($requestData);
    return $app['twig']->render('shop.html.twig', [
        'request' => $requestData,
        'checkData' => $checkData,
        'avisoData' => $avisoData,
    ]);
});

$app->match('/notify/{what}', function ($what, Request $request) use ($app) {
    $data = $request->get('data');
    if (!in_array($what, ['paymentAvisoUrl', 'checkOrderUrl'])) {
        return sprintf('Неверный URL %s. Верные урлы: %s', $what, 'paymentAvisoUrl, checkOrderUrl');
    }

    $url = $app['kassa.config'][$what];

    $data = json_decode($data, true);
    $context  = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded; charset=utf-8',
        'timeout' => 5,
        'content' => http_build_query($data),
    ]]);
    $result = @file_get_contents($url, false, $context);

    return $result;
});

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig');
});

$app->get('/config', function () use ($app) {
    return $app['twig']->render('config.html.twig', ['settings' => $app['kassa.config']]);
});

$app->run();
