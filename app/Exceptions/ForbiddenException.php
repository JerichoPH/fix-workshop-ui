<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ForbiddenException extends Exception
{
    protected $message = '禁止操作';
}
