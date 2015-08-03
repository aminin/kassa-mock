<?php
/**
 * Created by PhpStorm.
 * User: anton
 * Date: 01.08.15
 * Time: 0:38
 */

namespace Yandex\Kassa\HttpNotification;

class PaymentMethod
{
    const DEFAULT_METHOD = 'PC';

    private static $paymentMethods = [
        'PC' => 'Оплата из кошелька в Яндекс.Деньгах.',
        'AC' => 'Оплата с произвольной банковской карты.',
        'MC' => 'Платеж со счета мобильного телефона.',
        'GP' => 'Оплата наличными через кассы и терминалы.',
        'WM' => 'Оплата из кошелька в системе WebMoney.',
        'SB' => 'Оплата через Сбербанк: оплата по SMS или Сбербанк Онлайн.',
        'MP' => 'Оплата через мобильный терминал (mPOS).',
        'AB' => 'Оплата через Альфа-Клик.',
        'МА' => 'Оплата через MasterPass.',
        'PB' => 'Оплата через Промсвязьбанк.',
        'QW' => 'Оплата через QIWI Wallet.',
        'KV' => 'Оплата через КупиВкредит (Тинькофф Банк).',
    ];

    public static function annotate($code)
    {
        return self::$paymentMethods[$code];
    }

    public static function normalize($code)
    {
        return isset(self::$paymentMethods[$code]) ? $code : self::DEFAULT_METHOD;
    }

    public static function paymentMethods()
    {
        return self::$paymentMethods;
    }
}
