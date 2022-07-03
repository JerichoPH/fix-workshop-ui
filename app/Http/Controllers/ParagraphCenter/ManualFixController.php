<?php

namespace App\Http\Controllers\ParagraphCenter;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\View\View;

class ManualFixController extends Controller
{
    /**
     * 列表页
     * @return Factory|Application|View
     */
    final public function index()
    {
        return view("ParagraphCenter.ManualFix.index");
    }

    /**
     * 新建检修页
     * @return Factory|Application|View
     */
    final public function create()
    {
        return view("ParagraphCenter.ManualFix.create");
    }
}
