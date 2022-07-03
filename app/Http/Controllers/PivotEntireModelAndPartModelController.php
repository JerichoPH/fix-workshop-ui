<?php

namespace App\Http\Controllers;

use App\Model\PartModel;
use App\Model\PivotEntireModelAndPartModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class PivotEntireModelAndPartModelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            $part_models = PartModel::with(['PartCategory'])->where('entire_model_unique_code', request('entire_model_unique_code'))->get();
            return response()->json($part_models);
        }
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
     * @param int $entireModelUniqueCode
     * @return \Illuminate\Http\Response
     */
    public function edit($entireModelUniqueCode)
    {
        $pivotEntireModelAndPartModels = PivotEntireModelAndPartModel::with(['EntireModel', 'PartModel'])->where('entire_model_unique_code', $entireModelUniqueCode)->get();
        return view('Entire.Model.bindingNumber_ajax')
            ->with('pivotEntireModelAndPartModels', $pivotEntireModelAndPartModels)
            ->with('entireModelUniqueCode', $entireModelUniqueCode);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $entireModelUniqueCode
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $entireModelUniqueCode)
    {
        try {
            DB::table("pivot_entire_model_and_part_models")->where("entire_model_unique_code", $entireModelUniqueCode)->delete();
            $insert = [];
            foreach ($request->all() as $partModelUniqueCode => $number) {
                $insert[] = [
                    "entire_model_unique_code" => $entireModelUniqueCode,
                    "part_model_unique_code" => $partModelUniqueCode,
                    "number" => $number,
                ];
            }
            DB::table("pivot_entire_model_and_part_models")->insert($insert);
            return Response::make("修改成功");
        } catch (\Exception $exception) {
            return Response::make($exception->getMessage(), 500);
        }
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
}
