<?php

namespace App\Exceptions;

use Exception;

class WechatException extends Exception
{
    protected $message = '微信小程序意外错误';
    protected $code = 403;
}
