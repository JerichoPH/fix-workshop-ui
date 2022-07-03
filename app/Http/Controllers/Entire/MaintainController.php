<?php

namespace App\Http\Controllers\Entire;

use App\Facades\MaintainLocationFacade;
use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\Maintain;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\TextHelper;

class MaintainController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Factory|View
     */
    public function index()
    {
        # 现场车间列表
        $sceneWorkshops = json_decode(file_get_contents(storage_path('app/basicInfo/stations.json')), true);
        $sceneWorkshopsIsShow = DB::table('maintains as m')->where('type', 'SCENE_WORKSHOP')->where('is_show', true)->pluck('unique_code')->toArray();
        $sceneWorkshops2 = [];
        foreach ($sceneWorkshops as $su => $item) {
            if (in_array($su, $sceneWorkshopsIsShow)) {
                $sceneWorkshops2[$su] = $item['name'];
            }
        }

        return view('Entire.Maintain.index', [
            'sceneWorkshops' => $sceneWorkshops2,
            'sceneWorkshopsAsJson' => json_encode($sceneWorkshops2),
        ]);
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
