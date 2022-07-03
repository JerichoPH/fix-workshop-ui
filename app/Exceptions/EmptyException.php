<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Str;
use Throwable;

class EmptyException extends Exception
{
    protected $message = '资源不存在';
    protected $code = 404;
}
