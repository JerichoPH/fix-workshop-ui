<?php

namespace App\Http\Controllers\RepairBase;

use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Model\PivotBreakdownLogAndBreakdownType;
use App\Model\RepairBaseBreakdownOrderEntireInstance;
use App\Model\RepairBaseBreakdownOrderTempEntireInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BreakdownOrderEntireInstanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function index()
    {
        try {
            $breakdown_order_entire_instances = ModelBuilderFacade::init(request(), RepairBaseBreakdownOrderEntireInstance::with([]))->all();

            return JsonResponseFacade::dump(['breakdown_order_entire_instance' => $breakdown_order_entire_instances]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function show($id)
    {
        try {
            $breakdown_order_entire_instance = ModelBuilderFacade::init(
                request(),
                RepairBaseBreakdownOrderEntireInstance::with(['BreakdownLog'])
            )
                ->extension(function ($builder) use ($id) {
                    return $builder->where('id', $id);
                })
                ->first();
            $breakdown_type_ids = PivotBreakdownLogAndBreakdownType::with([])->where('breakdown_log_id', $breakdown_order_entire_instance->breakdown_log_id)->pluck('breakdown_type_id')->toArray();

            return JsonResponseFacade::data([
                'breakdown_order_entire_instance' => $breakdown_order_entire_instance,
                'breakdown_type_ids' => $breakdown_type_ids,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
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
