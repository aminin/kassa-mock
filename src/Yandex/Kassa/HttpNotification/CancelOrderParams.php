<?php

namespace Yandex\Kassa\HttpNotification;

class CancelOrderParams extends CheckOrderParams
{
    /**
     * xs:normalizedString, до 16 символов
     * Тип запроса, значение: cancelOrder.
     */
    protected $action = 'cancelOrder';
}
