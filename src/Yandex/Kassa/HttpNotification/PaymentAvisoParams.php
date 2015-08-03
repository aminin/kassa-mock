<?php

namespace Yandex\Kassa\HttpNotification;

class PaymentAvisoParams extends CheckOrderParams
{
    /**
     * xs:normalizedString, до 16 символов
     * Тип запроса, значение: paymentAviso.
     * При обмене данными в формате PKCS#7 передается в качестве открывающего тега XML-документа.
     */
    protected $action = 'paymentAviso';
    /**
     * xs:dateTime
     * Момент регистрации оплаты заказа в ИС Оператора.
     */
    protected $paymentDatetime;
    /**
     * xs:string, 2 символа
     * Двухбуквенный код страны плательщика в соответствии с ISO 3166-1 alpha-2.
     */
    protected $cps_user_country_code = 'RU';

    protected static $keysForArrayAccess = [
        'requestDatetime',
        'paymentDatetime',
        'cps_user_country_code',
        'action',
        'md5',
        'shopId',
        'shopArticleId',
        'invoiceId',
        'orderNumber',
        'customerNumber',
        'orderCreatedDatetime',
        'orderSumAmount',
        'orderSumCurrencyPaycash',
        'orderSumBankPaycash',
        'shopSumAmount',
        'shopSumCurrencyPaycash',
        'shopSumBankPaycash',
        'paymentPayerCode',
        'paymentType',
    ];
}
