<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class AuthorizationController extends Controller
{
    final public function GetLogin()
    {
        return view("Authorization.login");
    }
}
