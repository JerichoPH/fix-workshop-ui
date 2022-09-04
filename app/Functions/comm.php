<?php

use Illuminate\Foundation\Application;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;

/**
 * 从session中获取jwt
 * @return Application|SessionManager|Store|mixed
 */
function GetJWTFromSession()
{
    return session(__JWT__);
}
