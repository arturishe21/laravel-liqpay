<?php

namespace Arturishe21\LiqPay\Facades;

use Illuminate\Support\Facades\Facade;

class LiqPay extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'liqpay';
    }
}
