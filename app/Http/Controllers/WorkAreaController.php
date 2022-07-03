<?php

namespace App\Http\Controllers;

use App\Exceptions\EmptyException;
use App\Exceptions\ValidateException;
use App\Facades\JsonResponseFacade;
use App\Model\Maintain;
use App\Model\WorkArea;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class WorkAreaController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Factory|Application|View
     */
    final public function index()
    {
        if (request()->ajax()) {
            $work_areas = (new WorkArea)
                ->ReadMany(["name",])
                ->with(["Workshop"])
                ->when(
                    request("name"),
                    function ($query, $name) {
                        $query->where("name", "like", "%{$name}%");
                    }
                );
            return JsonResponseFacade::dict([
                "work_areas" => $work_areas->get()
                ,]);
        } else {
            return view("WorkArea.index", ["work_area_types" => collect(WorkArea::$WORK_AREA_TYPES)->flip(),]);
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return mixed
     * @throws EmptyException
     * @throws ValidateException
     */
    final public function store(Request $request)
    {
        $workshop_unique_code = $request->get("workshop_unique_code", "") ?? "";
        if (!$workshop_unique_code) throw new ValidateException("请选择所属车间");
        if (!Maintain::with([])->whereIn("type", ["SCENE_WORKSHOP", "WORKSHOP",])->exists()) {
            throw new EmptyException("所属工区不存在");
        }
        $name = $request->get("name", "") ?? "";
        if (!$name) throw new ValidateException("工区名称不能为空");
        if (WorkArea::with([])->where("name", $name)->exists()) {
            throw new ValidateException("工区名称重复");
        }
        $type = $request->get("type", "") ?? "";
        if(!array_key_exists($type,array_flip(WorkARea::$WORK_AREA_TYPES))){
            throw new ValidateException("工区类型错误");
        }

        WorkArea::with([])->create([
            "workshop_unique_code" => $workshop_unique_code,
            "name" => $name,
            "unique_code" => WorkArea::generateUniqueCode(),
            "type" => $type,
            "paragraph_unique_code" => env("ORGANIZATION_CODE"),
            "is_show" => true,
        ]);

        return JsonResponseFacade::created([], "新建成功");
    }

    /**
     * @param string $unique_code
     * @return JsonResponse
     */
    final public function show(string $unique_code): JsonResponse
    {
        $work_area = (new WorkArea)->ReadOneByUniqueCode($unique_code)->firstOrFail();
        return JsonResponseFacade::dict(["work_area" => $work_area,]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param string $unique_code
     * @return JsonResponse
     * @throws ValidateException
     * @throws EmptyException
     * @throws Throwable
     */
    final public function update(Request $request, string $unique_code): JsonResponse
    {
        $workshop_unique_code = $request->get("workshop_unique_code", "") ?? "";
        if (!$workshop_unique_code) throw new ValidateException("请选择所属车间");
        if (!Maintain::with([])->whereIn("type", ["SCENE_WORKSHOP", "WORKSHOP",])->exists()) {
            throw new EmptyException("所属工区不存在");
        }
        $name = $request->get("name", "") ?? "";
        if (!$name) throw new ValidateException("工区名称不能为空");
        if (WorkArea::with([])->where("unique_code", "<>", $unique_code)->where("name", $name)->exists()) {
            throw new ValidateException("工区名称重复");
        }
        $type = $request->get("type", "") ?? "";
        if(!array_key_exists($type,array_flip(WorkARea::$WORK_AREA_TYPES))){
            throw new ValidateException("工区类型错误");
        }

        $work_area = WorkArea::with([])->where("unique_code", $unique_code)->firstOrFail();
        $work_area
            ->fill([
                "workshop_unique_code" => $workshop_unique_code,
                "name" => $name,
                "type" => $type,
            ])
            ->saveOrFail();

        return JsonResponseFacade::updated([], "编辑成功");
    }
}
