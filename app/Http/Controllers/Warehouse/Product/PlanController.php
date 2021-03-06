<?php

namespace App\Http\Controllers\Warehouse\Product;

use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\WarehouseProductInstance;
use App\Model\WarehouseProductPart;
use App\Model\WarehouseProductPlan;
use App\Model\WarehouseProductPlanProcess;
use App\Model\WarehouseReportProductPart;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $warehouseProductInstances = WarehouseProductInstance::with([
            'warehouseProduct',
            'warehouseProduct.category',
        ])->where('status', 'INSTALLED')->paginate();
        return view($this->view('index'), ['warehouseProductInstances' => $warehouseProductInstances]);
    }

    private function view(string $viewName)
    {
        return "Warehouse.Product.Plan.{$viewName}";
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * ??????????????????
     * @param int $warehouseProductPlanId ??????????????????
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    public function getProcessWarehouseProductPlan($warehouseProductPlanId)
    {
        try {
            DB::transaction(function () use ($warehouseProductPlanId) {
                # ??????????????????
                $warehouseProductPlan = WarehouseProductPlan::with(['warehouseProductPart'])->findOrFail($warehouseProductPlanId);
                $warehouseProductPlan->fill([
                    'started_at' => time(),
                    'explain' => date('Y-m-d') . '???' . Account::find(session('account.id'))->nickname . '????????????',
                    'last_processor_id' => session('account.id'),
                    'last_processed_at' => date('Y-m-d H:i:s')
                ])
                    ->saveOrFail();

                # ?????????
                $warehouseProductPart = WarehouseProductPart::findOrFail($warehouseProductPlan->warehouseProductPart->id);
                $warehouseProductPart->fill(['inventory' => $warehouseProductPart->inventory - 1])->saveOrFail();

                # ????????????????????????
                $warehouseProductPlanProcess = new WarehouseProductPlanProcess();
                $warehouseProductPlanProcess->fill([
                    'warehouse_product_plan_id' => $warehouseProductPlanId,
                    'processor_id' => session('account.id'),
                    'processed_at' => date('Y-m-d H:i:s')
                ])->saveOrFail();

                # ????????????????????????
                $warehouseReportProductPart = new WarehouseReportProductPart;
                $warehouseReportProductPart->fill([
                    'warehouse_product_part_id' => $warehouseProductPlan->warehouseProductPart->id,
                    'number' => 1,
                    'operation_direction' => 'OUT',
                ])->saveOrFail();
            });


            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????', 500);
        }
    }
}
