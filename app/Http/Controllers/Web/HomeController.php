<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function Index()
    {
        return view("Home.index");
    }
}