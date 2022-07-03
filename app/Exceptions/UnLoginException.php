<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class UnLoginException extends Exception
{
    protected $message = '未登录';
}
