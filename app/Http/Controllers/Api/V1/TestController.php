<?php

namespace App\Http\Controllers\Api\V1;

use App\Facades\JsonResponseFacade;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public function store(Request $request)
    {
        Log::channel('curl-test')->info('测试', [$request->fullUrl(),$request->query(),$request->all()]);
        return JsonResponseFacade::ok();
    }
}
