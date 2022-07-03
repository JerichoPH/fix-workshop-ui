<?php

namespace App\Exceptions;

use Exception;

class FuncNotFoundException extends Exception
{
    protected $message = '方法不存在';
    protected $code = 403;
}
