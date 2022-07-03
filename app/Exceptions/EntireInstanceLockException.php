<?php

namespace App\Exceptions;

use Exception;

class EntireInstanceLockException extends Exception
{
    protected $message = '设备/器材被其他任务锁定';
    protected $code = 403;
}
