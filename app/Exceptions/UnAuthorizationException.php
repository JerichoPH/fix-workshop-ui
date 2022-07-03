<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class UnAuthorizationException extends Exception
{
    protected $message = '权限不足';
}
