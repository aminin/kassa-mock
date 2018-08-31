<?php

namespace Yandex\Kassa\HttpNotification;

class NotificationParams implements \ArrayAccess
{
    /**
     * Для переводов из кошелька — p2p-incoming. Для переводов с произвольной карты — card-incoming.
     * @var string
     */
    protected $notification_type;
    /**
     * Идентификатор операции в истории счета получателя.
     * @var string
     */
    protected $operation_id;
    /**
     * Сумма, которая зачислена на счет получателя.
     * @var amount
     */
    protected $amount;
    /**
     * Сумма, которая списана со счета отправителя.
     * @var amount
     */
    protected $withdraw_amount;
    /**
     * Код валюты — всегда 643 (рубль РФ согласно ISO 4217).
     * @var string
     */
    protected $currency;
    /**
     * Дата и время совершения перевода.
     * @var datetime
     */
    protected $datetime;
    /**
     * Для переводов из кошелька — номер счета отправителя. Для переводов с произвольной карты — параметр содержит пустую строку.
     * @var string
     */
    protected $sender;
    /**
     * Для переводов из кошелька — перевод защищен кодом протекции. Для переводов с произвольной карты — всегда false.
     * @var boolean
     */
    protected $codepro;
    /**
     * Метка платежа. Если ее нет, параметр содержит пустую строку.
     * @var string
     */
    protected $label;
    /**
     * SHA-1 hash параметров уведомления.
     * @var string
     */
    protected $sha1_hash;
    protected $sha1_data;
    /**
     * Перевод еще не зачислен. Получателю нужно освободить место в кошельке или использовать код протекции (если codepro=true).
     * @var boolean
     */
    protected $unaccepted;

    /**#@+
     * ФИО и контакты отправителя перевода (указывает отправитель, если не запрашивались, параметры содержат пустую строку)
     */
    /**
     * Имя.
     *
     * @var string
     */
    protected $firstname;
    /**
     * Фамилия.
     *
     * @var string
     */
    protected $lastname;
    /**
     * Отчество.
     *
     * @var string
     */
    protected $fathersname;
    /**
     * Адрес электронной почты отправителя перевода. Если email не запрашивался, параметр содержит пустую строку.
     *
     * @var string
     */
    protected $email;
    /**
     * Телефон отправителя перевода. Если телефон не запрашивался, параметр содержит пустую строку.
     *
     * @var string
     */
    protected $phone;
    /**#@-*/
    /**#@+
     * Адрес доставки (указывает отправитель, если адрес не запрашивался, параметры содержат пустую строку)
     */
    /**
     * Город.
     *
     * @var string
     */
    protected $city;
    /**
     * Улица.
     *
     * @var string
     */
    protected $street;
    /**
     * Дом.
     *
     * @var string
     */
    protected $building;
    /**
     * Корпус.
     *
     * @var string
     */
    protected $suite;
    /**
     * Квартира.
     *
     * @var string
     */
    protected $flat;
    /**
     * Индекс.
     *
     * @var string
     */
    protected $zip;
    /**#@-*/

    protected static $keysForArrayAccess = [
        'notification_type',
        'operation_id',
        'amount',
        'withdraw_amount',
        'currency',
        'datetime',
        'sender',
        'codepro',
        'label',
        'sha1_hash',
        'sha1_data',
        'unaccepted',
        'firstname',
        'lastname',
        'fathersname',
        'email',
        'phone',
        'city',
        'street',
        'building',
        'suite',
        'flat',
        'zip',
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
     * @return self
     */
    public static function createWithArray($params)
    {
        return new static($params);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return self
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
            'notification_type',
            'operation_id',
            'amount',
            'currency',
            'datetime',
            'sender',
            'codepro',
            'notification_secret',
            'label',
        ];
        $dataToHash = [];
        foreach ($paramKeys as $k) {
            if ($k == 'notification_secret') {
                $dataToHash[] = $password;
            } else {
                $dataToHash[] = isset($this->$k) ? $this->$k : '';
            }
        }
        $stringToHash = implode('&', $dataToHash);
        $this['sha1_hash'] = sha1($stringToHash);
        $this['sha1_data'] = $stringToHash;
        return $this['sha1_hash'];
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
