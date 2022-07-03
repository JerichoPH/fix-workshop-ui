<?php

namespace App\Exceptions;

use Exception;

class EntireInstanceNotFoundException extends Exception
{
    protected $message = '设备/器材没有找到';
    protected $code = 404;
}
