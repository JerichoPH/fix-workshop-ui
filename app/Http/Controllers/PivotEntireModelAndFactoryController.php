<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class PivotEntireModelAndFactoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Support\Collection
     */
    public function index()
    {
        if (request()->ajax()) return DB::table('pivot_entire_model_and_factories')->where('entire_model_unique_code', request('entire_model_unique_code'))->get(['factory_name as name']);
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
        try {
            DB::table('pivot_entire_model_and_factories')->insert($request->all());

            return Response::make('绑定成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('重复绑定', 500);
        }
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
     * @param int $entireModelUniqueCode
     * @return \Illuminate\Http\Response
     */
    public function destroy($entireModelUniqueCode)
    {
        try {
            DB::table('pivot_entire_model_and_factories')
                ->where('entire_model_unique_code', $entireModelUniqueCode)
                ->where('factory_name', request('factory_name'))
                ->delete();
            return Response::make('解绑成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('意外错误', 500);
        }
    }
}
