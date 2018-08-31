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
    isset($requestData['orderNumber']) && $params['orderNumber'] = $requestData['orderNumber'];
    $checkData = \Yandex\Kassa\HttpNotification\CheckOrderParams::createWithArray($params);
    $params['md5'] = $checkData->signWithPassword($app['kassa.config']['shopPassword']);
    return $params;
};

$makeAvisoData = function ($requestData) use ($app, $makeCheckData) {
    $params = $makeCheckData($requestData);
    $params['action'] = 'paymentAviso';
    $params['paymentDatetime'] = date('Y-m-d\\TH:i:sP');
    $checkData = \Yandex\Kassa\HttpNotification\PaymentAvisoParams::createWithArray($params);
    $params['md5'] = $checkData->signWithPassword($app['kassa.config']['shopPassword']);
    return $params;
};

$makeSuccessData = function ($requestData) use ($app, $makeCheckData) {
    $params = $makeCheckData($requestData);
    $params['action'] = 'PaymentSuccess';
    $params['paymentDatetime'] = date('Y-m-d\\TH:i:sP');
    return $params;
};

$makeNotificationData = function ($requestData) use ($app, $makeCheckData) {
    $params = [
        'operation_id'      => '441361714955017004',
        'notification_type' => 'card-incoming', // Для кошельков php-incoming
        'datetime'          => date('Y-m-d\\TH:i:sP'),
        'sha1_hash'         => 'ac13833bd6ba9eff1fa9e4bed76f3d6ebb57f6c0',
        'sender'            => '', // Для карт пустая строка, для Яндекс.Денег — номер счёта Яндекс.Денег
        'codepro'           => false,
        'currency'          => 643,
        'amount'            => round($requestData['sum'] * 0.98, 2),
        'withdraw_amount'   => round($requestData['sum'], 2),
        'label'             => $requestData['label'],
        // ФИО и контакты отправителя перевода (указывает отправитель, если не запрашивались, параметры содержат пустую строку)
        'lastname'          => $requestData['lastname'],
        'firstname'         => $requestData['firstname'],
        'fathersname'       => $requestData['fathersname'],
        'phone'             => $requestData['phone'],
        'email'             => $requestData['email'],
        // Адрес доставки (указывает отправитель, если адрес не запрашивался, параметры содержат пустую строку)
        'zip'               => $requestData['zip'],
        'city'              => $requestData['city'],
        'street'            => $requestData['street'],
        'building'          => $requestData['building'],
        'suite'             => $requestData['suite'],
        'flat'              => $requestData['flat'],
        //'MyField'                 => 'Добавленное Контрагентом поле',
    ];

    $checkData = \Yandex\Kassa\HttpNotification\CheckOrderParams::createWithArray($params);
    $params['sha1_hash'] = $checkData->signWithPassword($app['kassa.config']['secret']);

    return $params;
};

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app->match('/eshop.xml', function (Request $request) use ($app, $makeCheckData, $makeAvisoData, $makeSuccessData) {
    $requestData = $request->request->getIterator()->getArrayCopy();
    $checkData = $makeCheckData($requestData);
    $avisoData = $makeAvisoData($requestData);
    $successData = $makeSuccessData($requestData);
    return $app['twig']->render('eshop.html.twig', [
        'request' => $requestData,
        'checkData' => $checkData,
        'avisoData' => $avisoData,
        'successData' => $successData,
    ]);
});

$app->match('/quickpay/confirm.xml', function (Request $request) use ($app, $makeNotificationData) {
    $requestData = $request->request->getIterator()->getArrayCopy();
    $notificationData = $makeNotificationData($requestData);
    return $app['twig']->render('quickpayConfirm.html.twig', [
        'request' => $requestData,
        'notificationData' => $notificationData,
    ]);
});

$app->match('/notify/{what}', function ($what, Request $request) use ($app) {
    $data = $request->get('data');
    if (!in_array($what, ['paymentAvisoUrl', 'checkOrderUrl', 'notificationUrl'])) {
        return sprintf('Неверный URL %s. Верные урлы: %s', $what, 'paymentAvisoUrl, checkOrderUrl, notificationUrl');
    }

    $url = $app['kassa.config'][$what];
    $data = json_decode($data, true);

    if ($request->get('update_checksum')) {
        $checkClass = [
            'paymentAvisoUrl' => 'PaymentAvisoParams',
            'checkOrderUrl' => 'CheckOrderParams',
            'notificationUrl' => 'NotificationParams',
        ][$what];
        $checksumKey = [
            'paymentAvisoUrl' => 'md5',
            'checkOrderUrl' => 'md5',
            'notificationUrl' => 'sha1_hash',
        ][$what];
        $passwordKey = [
            'paymentAvisoUrl' => 'shopPassword',
            'checkOrderUrl' => 'shopPassword',
            'notificationUrl' => 'secret',
        ][$what];

        $checkClass = sprintf('\Yandex\Kassa\HttpNotification\%s', $checkClass);
        $checkData = $checkClass::createWithArray($data);
        $data[$checksumKey] = $checkData->signWithPassword($app['kassa.config'][$passwordKey]);
    }

    $context  = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded; charset=utf-8',
        'timeout' => 5,
        'content' => http_build_query($data),
        'ignore_errors' => true,
    ]]);
    $result = @file_get_contents($url, false, $context);

    return $result;
    //return $result . print_r($http_response_header, true) . print_r($data, true) . $url;
});

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig');
});

$app->get('/config', function () use ($app) {
    return $app['twig']->render('config.html.twig', ['settings' => $app['kassa.config']]);
});

$app->run();
