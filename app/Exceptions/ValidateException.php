<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ValidateException extends Exception
{
    protected $message = '表单验证错误';
}
