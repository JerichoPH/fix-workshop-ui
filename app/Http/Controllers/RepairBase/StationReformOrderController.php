<?php

namespace App\Http\Controllers\RepairBase;

use App\Facades\CodeFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\RepairBaseStationReformOrderRequest;
use App\Model\Account;
use App\Model\Maintain;
use App\Model\RepairBaseStationReformOrder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\HttpResponseHelper;
use Jericho\ValidateHelper;
use Illuminate\Support\Facades\Response;
use function Hprose\Future\all;

/**
 * 站改
 * Class StationReformOrderController
 * @package App\Http\Controllers
 */
class StationReformOrderController extends Controller
{
    private $_work_areas = [];

    public function __construct()
    {
        $this->_work_areas = array_flip(Account::$WORK_AREAS);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index(Request $request)
    {
        try {
            $direction = $request->get('direction', 'IN');
            $station_reforms = RepairBaseStationReformOrder::with(['WithAccount', 'WithStation'])
                ->where('direction', $direction)
                ->when(
                    session('account.read_scope') === 1,
                    function ($query) {
                        return $query->where('operator_id', session('account.id'));
                    }
                )
                ->orderByDesc('updated_at')->paginate();
            return view('RepairBase.StationReformOrder.index', [
                'station_reforms' => $station_reforms
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create()
    {
        try {
            $scene_workshops = DB::table('maintains')->where('type', 'SCENE_WORKSHOP')->pluck('name', 'unique_code')->toArray();
            return view('RepairBase.StationReformOrder.create', [
                'scene_workshops' => $scene_workshops,
                'first_scene_workshop_unique_code' => array_key_first($scene_workshops)
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return mixed
     * @throws \Throwable
     */
    final public function store(Request $request)
    {
        try {
            $work_area = session('account.work_area');
            if (array_flip(Account::$WORK_AREAS)[$work_area] == 0) return HttpResponseHelper::errorValidate('该用户没有所属工区');
            $v = ValidateHelper::firstErrorByRequest($request, new RepairBaseStationReformOrderRequest());
            if ($v !== null) return HttpResponseHelper::errorValidate($v);
            $stationReform = new RepairBaseStationReformOrder();
            $stationReform->fill(array_merge($request->all(), ['serial_number' => CodeFacade::makeSerialNumber('STATION_REFORM_IN')]));
            $stationReform->saveOrFail();

            $new = function () {

            };
            $old = function () {

            };

            return HttpResponseHelper::created('添加成功');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function edit($id)
    {
        try {

            return Response::view('RepairBase.StationReformOrder.edit');
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("异常错误", 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function update(Request $request, $id)
    {
        try {

            return Response::make("编辑成功");
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("异常错误", 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function destroy($id)
    {
        try {

            return Response::make();
        } catch (ModelNotFoundException $exception) {
            return Response::make("数据不存在", 404);
        } catch (\Exception $exception) {
            return Response::make("异常错误", 500);
        }
    }

}
