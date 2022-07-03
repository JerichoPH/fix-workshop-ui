<?php

namespace App\Exceptions;

use Exception;

class MaintainNotFoundException extends Exception
{
    protected $message = '资源不存在';
    protected $code = 404;
}
