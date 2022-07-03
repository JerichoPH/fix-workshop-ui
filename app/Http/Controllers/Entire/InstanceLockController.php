<?php

namespace App\Http\Controllers\Entire;

use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Model\EntireInstanceLock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InstanceLockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function index()
    {
        try {
            $entire_instance_lock = ModelBuilderFacade::init(request(), EntireInstanceLock::with([]))->first();

            if ($entire_instance_lock) {
                return JsonResponseFacade::data(['entire_instance_lock' => $entire_instance_lock]);
            } else {
                return JsonResponseFacade::errorEmpty('设备没有被占用');
            }
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    final public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $identity_code
     * @return \Illuminate\Http\Response
     */
    final public function show($identity_code)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function destroy($id)
    {
        //
    }
}
