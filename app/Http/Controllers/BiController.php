<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BiController extends Controller
{
    /**
     * 申请种类型页面
     */
    final public function getModelCat()
    {
        return view("Bi.modelCat");
    }

    /**
     * 申请种类型列表页
     * @return Factory|Application|View
     */
    final public function getModelAly()
    {
        return view("Bi.modelAly");
    }
}