<?php

namespace App\Http\Controllers\Storehouse;

use App\Facades\JsonResponseFacade;
use App\Model\Storehouse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PostController extends Controller
{
    final public function index()
    {
        $storehouses = Storehouse::with([])->get();
        if (request()->ajax()) {
            return JsonResponseFacade::dict(["storehouses" => $storehouses,]);
        } else {
            return view("Storehouse.Post.index", ["storehouses" => $storehouses,]);
        }
    }
}
