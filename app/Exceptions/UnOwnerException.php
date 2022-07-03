<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class UnOwnerException extends Exception
{
    protected $message = '无权操作';
}
