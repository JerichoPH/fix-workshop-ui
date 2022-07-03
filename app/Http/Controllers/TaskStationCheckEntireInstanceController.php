<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Model\TaskStationCheckEntireInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class TaskStationCheckEntireInstanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function index()
    {
        try {
            $task_station_check_entire_instances = ModelBuilderFacade::init(request(), TaskStationCheckEntireInstance::with([]))->all();

            if (request()->ajax())
                return JsonResponseFacade::data(['task_station_check_entire_instances' => $task_station_check_entire_instances]);
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
            $task_station_check_entire_instance = TaskStationCheckEntireInstance::with([])->where('id', $id)->firstOrFail();

            if (request()->ajax()) return JsonResponseFacade::data(['task_station_check_entire_instance' => $task_station_check_entire_instance]);
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

    /**
     * 获取图片
     * @param $id
     * @return mixed
     */
    final public function getImages($id)
    {
        try {
            $task_station_check_entire_instance = TaskStationCheckEntireInstance::with([])->where('id', $id)->firstOrFail();

            $images = [];
            if ($task_station_check_entire_instance->images)
                $images = json_decode($task_station_check_entire_instance->images, true);

            if (request()->ajax()) return JsonResponseFacade::data(['images' => $images]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
