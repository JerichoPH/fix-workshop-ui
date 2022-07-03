<?php

namespace App\Http\Controllers\Warehouse;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class GoingToDieController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $carbon = Carbon::now();
        $originAt = $carbon->firstOfMonth()->format('Y-m-d');
        $finishAt = $carbon->endOfMonth()->format('Y-m-d');

        $sceneWorkshops = DB::table('maintains')
            ->where('deleted_at', null)
            ->where('parent_unique_code', env('ORGANIZATION_CODE'))
            ->where('type', 'SCENE_WORKSHOP')
            ->pluck('name', 'unique_code');

        $scene_workshop_unique_code = $request->get('scene_workshop_unique_code', '');
        $station_name = $request->get('station_name', '');
        $category_unique_code = $request->get('category_unique_code', '');
        $sub_model_unique_code = $request->get('sub_model_unique_code', '');

        $entireInstances = DB::table('entire_instances as ei')
            ->select([
                'ei.next_fixing_day',
                'ei.category_name',
                'em.name as entire_model_name',
                'ei.entire_model_unique_code',
                'ei.identity_code',
                'ei.factory_device_code',
                'ei.serial_number',
                'ei.maintain_station_name'
            ])
            ->leftJoin(DB::raw('entire_models em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
            ->leftJoin(DB::raw('maintains s'), 'ei.maintain_station_name', '=', 's.name')
            ->leftJoin(DB::raw('maintains w'), 's.parent_unique_code', '=', 'w.unique_code')
            ->where('ei.deleted_at', null)
            ->where('em.deleted_at', null)
            ->whereIn('ei.status', ['INSTALLED', 'INSTALLING'])
            ->whereBetween('ei.next_fixing_day', [$originAt, $finishAt])
            ->when(
                !empty($scene_workshop_unique_code),
                function ($query) use ($scene_workshop_unique_code) {
                    return $query->where('w.unique_code', $scene_workshop_unique_code);
                }
            )
            ->when(
                !empty($station_name),
                function ($query) use ($station_name) {
                    return $query->where('ei.maintain_station_name', $station_name);
                }
            )
            ->when(
                !empty($category_unique_code),
                function ($query) use ($category_unique_code) {
                    return $query->where('ei.category_unique_code', $category_unique_code);
                }
            )
            ->when(
                !empty($sub_model_unique_code),
                function ($query) use ($sub_model_unique_code) {
                    return $query->where('ei.model_unique_code', $sub_model_unique_code);
                }
            )
            ->orderByDesc('ei.next_fixing_day')
            ->paginate(50);


        $categories = DB::table('categories')->where('deleted_at', null)->pluck('name', 'unique_code')->toArray();

        return view("Warehouse.GoingToDie.index")
            ->with('entireInstances', $entireInstances)
            ->with('sceneWorkshops', $sceneWorkshops)
            ->with('categories', $categories);
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
}
