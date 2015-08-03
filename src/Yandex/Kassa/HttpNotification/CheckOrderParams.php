<?php

namespace Yandex\Kassa\HttpNotification;

class CheckOrderParams implements \ArrayAccess
{
    /**
     * xs:dateTime
     * Момент формирования запроса в ИС Оператора.
     */
    protected $requestDatetime;
    /**
     * xs:normalizedString, до 16 символов
     * Тип запроса. Значение: «checkOrder» (без кавычек).
     */
    protected $action = 'checkOrder';
    /**
     * xs:normalizedString, ровно 32 шестнадцатеричных символа, в верхнем регистре
     * MD5-хэш параметров платежной формы, правила формирования описаны
     * в разделе 4.4 «Правила обработки HTTP-уведомлений Контрагентом».
     */
    protected $md5;
    /**
     * xs:long
     * Идентификатор Контрагента, присваиваемый Оператором.
     */
    protected $shopId;
    /**
     * xs:long
     * Идентификатор товара, присваиваемый Оператором.
     */
    protected $shopArticleId;
    /**
     * xs:long
     * Уникальный номер транзакции в ИС Оператора.
     */
    protected $invoiceId;
    /**
     * xs:normalizedString, до 64 символов
     * Номер заказа в ИС Контрагента. Передается, только если был указан в платежной форме.
     */
    protected $orderNumber;
    /**
     * xs:normalizedString, до 64 символов
     * Идентификатор плательщика (присланный в платежной форме)
     * на стороне Контрагента: номер договора, мобильного телефона и т.п.
     */
    protected $customerNumber;
    /**
     * xs:dateTime
     * Момент регистрации заказа в ИС Оператора.
     */
    protected $orderCreatedDatetime;
    /**
     * CurrencyAmount
     * Стоимость заказа. Может отличаться от суммы платежа, если пользователь платил в валюте,
     * которая отличается от указанной в платежной форме. В этом случае Оператор берет на себя все конвертации.
     */
    protected $orderSumAmount;
    /**
     * CurrencyCode
     * Код валюты для суммы заказа.
     */
    protected $orderSumCurrencyPaycash;
    /**
     * CurrencyBank
     * Код процессингового центра Оператора для суммы заказа.
     */
    protected $orderSumBankPaycash;
    /**
     * CurrencyAmount
     * Сумма к выплате Контрагенту на р/с (стоимость заказа минус комиссия Оператора).
     */
    protected $shopSumAmount;
    /**
     * CurrencyCode
     * Код валюты для shopSumAmount.
     */
    protected $shopSumCurrencyPaycash;
    /**
     * CurrencyBank
     * Код процессингового центра Оператора для shopSumAmount.
     */
    protected $shopSumBankPaycash;
    /**
     * YMAccount
     * Номер счета в ИС Оператора, с которого производится оплата.
     */
    protected $paymentPayerCode;
    /**
     * xs:normalizedString
     * Способ оплаты заказа. Список значений приведен в таблице 6.6.1.
     */
    protected $paymentType;

    protected static $keysForArrayAccess = [
        'requestDatetime',
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

    protected function __construct($params)
    {
        foreach (static::$keysForArrayAccess as $key) {
            if ($key != 'action' && isset($params[$key])) {
                $this->$key = $params[$key];
            }
        }
    }

    /**
     * @param array $params
     * @return CheckOrderParams
     */
    public static function createWithArray($params)
    {
        return new self($params);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return CheckOrderParams
     */
    public static function createWithRequest($request)
    {
        $params = [];
        foreach (static::$keysForArrayAccess as $key) {
            if (isset($params[$key])) {
                $params[$key] = $request->get($key, null);
            }
        }
        return new self($params);
    }

    public function signWithPassword($password)
    {
        $paramKeys = [
            'action',
            'orderSumAmount',
            'orderSumCurrencyPaycash',
            'orderSumBankPaycash',
            'shopId',
            'invoiceId',
            'customerNumber',
        ];
        $dataToHash = [];
        foreach ($paramKeys as $k) {
            $dataToHash[] = isset($data[$k]) ? $data[$k] : '';
        }
        $dataToHash[] = $password;
        $stringToHash = implode(';', $dataToHash);
        $this['md5'] = md5($stringToHash);
        return $this['md5'];
    }

    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    public function offsetGet($offset)
    {
        return property_exists($this, $offset) ? $this->$offset : null;
    }

    public function offsetSet($offset, $value)
    {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        }
    }

    public function offsetUnset($offset)
    {
        if (property_exists($this, $offset)) {
            $this->$offset = null;
        }
    }
}
