<?php

namespace App\Http\Controllers\Storehouse;

use App\Exceptions\EmptyException;
use App\Exceptions\ValidateException;
use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\LocationFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\AreaStoreRequest;
use App\Http\Requests\AreaUpdateRequest;
use App\Http\Requests\PlatoonStoreRequest;
use App\Http\Requests\PlatoonUpdateRequest;
use App\Http\Requests\PositionUpdateRequest;
use App\Http\Requests\ShelfStoreRequest;
use App\Http\Requests\ShelfUpdateRequest;
use App\Http\Requests\StorehouseStoreRequest;
use App\Http\Requests\StorehouseUpdateRequest;
use App\Http\Requests\TierUpdateRequest;
use App\Model\Area;
use App\Model\EntireInstance;
use App\Model\Platoon;
use App\Model\Position;
use App\Model\Shelf;
use App\Model\Storehouse;
use App\Model\Tier;
use App\Validations\Web\PlatoonUpdateValidation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;
use Jericho\HttpResponseHelper;
use Jericho\TextHelper;
use Jericho\ValidateHelper;
use PHPMailer\PHPMailer\Exception;
use Throwable;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|View
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    final public function index(Request $request)
    {
        $code = env("ORGANIZATION_LOCATION_CODE");
        $lenCode = strlen($code);

        $storehouses = DB::table("storehouses")->select(["unique_code", "name"])->get();
        $areas = DB::table("areas")->select(["unique_code", "name", "storehouse_unique_code"])->get()->groupBy("storehouse_unique_code");
        $platoons = DB::table("platoons")->select(["unique_code", "name", "area_unique_code"])->get()->groupBy("area_unique_code");
        $shelves = DB::table("shelves")->select(["unique_code", "name", "platoon_unique_code"])->get()->groupBy("platoon_unique_code");
        $tiers = DB::table("tiers")->select(["unique_code", "name", "shelf_unique_code"])->get()->groupBy("shelf_unique_code");
        $positions = DB::table("positions")->select(["unique_code", "name", "tier_unique_code"])->get()->groupBy("tier_unique_code");

        switch (request("schema", "position") ?: "position") {
            case "storehouse":
                $locations = Storehouse::with([])
                    ->when(
                        request("storehouse_unique_code"),
                        function ($query, $unique_code) {
                            $query->where("unique_code", $unique_code);
                        }
                    )
                    ->paginate(100);
                break;
            case "area":
                $locations = Area::with([
                    "WithStorehouse",
                ])
                    ->when(
                        request("storehouse_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithStorehouse", function ($Storehouse) use ($unique_code) {
                                $Storehouse->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("area_unique_code"),
                        function ($query, $unique_code) {
                            $query->where("unique_code", $unique_code);
                        }
                    )
                    ->paginate(100);
                break;
            case "platoon":
                $locations = Platoon::with([
                    "WithArea",
                    "WithArea.WithStorehouse",
                ])
                    ->when(
                        request("storehouse_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithArea.WithStorehouse", function ($Storehouse) use ($unique_code) {
                                $Storehouse->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("area_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithArea", function ($Area) use ($unique_code) {
                                $Area->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("platoon_unique_code"),
                        function ($query, $unique_code) {
                            $query->where("unique_code", $unique_code);
                        }
                    )
                    ->paginate(100);
                break;
            case "shelf":
                $locations = Shelf::with([
                    "WithPlatoon",
                    "WithPlatoon.WithArea",
                    "WithPlatoon.WithArea.WithStorehouse",
                ])
                    ->when(
                        request("storehouse_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithPlatoon.WithArea.WithStorehouse", function ($Storehouse) use ($unique_code) {
                                $Storehouse->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("area_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithPlatoon.WithArea", function ($Area) use ($unique_code) {
                                $Area->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("platoon_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithPlatoon", function ($Platoon) use ($unique_code) {
                                $Platoon->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("shelf_unique_code"),
                        function ($query, $unique_code) {
                            $query->where("unique_code", $unique_code);
                        }
                    )
                    ->paginate(100);
                break;
            case "tier":
                $locations = Tier::with([
                    "WithShelf",
                    "WithShelf.WithPlatoon",
                    "WithShelf.WithPlatoon.WithArea",
                    "WithShelf.WithPlatoon.WithArea.WithStorehouse",
                ])
                    ->when(
                        request("storehouse_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithShelf.WithPlatoon.WithArea.WithStorehouse", function ($Storehouse) use ($unique_code) {
                                $Storehouse->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("area_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithShelf.WithPlatoon.WithArea", function ($Area) use ($unique_code) {
                                $Area->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("platoon_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithShelf.WithPlatoon", function ($Platoon) use ($unique_code) {
                                $Platoon->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("shelf_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithShelf", function ($Shelf) use ($unique_code) {
                                $Shelf->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("tier_unique_code"),
                        function ($query, $unique_code) {
                            $query->where("unique_code", $unique_code);
                        }
                    )
                    ->paginate(100);
                break;
            case "position":
            default:
                $locations = Position::with([
                    "WithTier",
                    "WithTier.WithShelf",
                    "WithTier.WithShelf.WithPlatoon",
                    "WithTier.WithShelf.WithPlatoon.WithArea",
                    "WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse",
                ])
                    ->when(
                        request("storehouse_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse", function ($Storehouse) use ($unique_code) {
                                $Storehouse->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("area_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithTier.WithShelf.WithPlatoon.WithArea", function ($Area) use ($unique_code) {
                                $Area->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("platoon_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithTier.WithShelf.WithPlatoon", function ($Platoon) use ($unique_code) {
                                $Platoon->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("shelf_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithTier.WithShelf", function ($Shelf) use ($unique_code) {
                                $Shelf->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("tier_unique_code"),
                        function ($query, $unique_code) {
                            $query->whereHas("WithTier", function ($Tier) use ($unique_code) {
                                $Tier->where("unique_code", $unique_code);
                            });
                        }
                    )
                    ->when(
                        request("position_unique_code"),
                        function ($query, $unique_code) {
                            $query->where("unique_code", $unique_code);
                        }
                    )
                    ->paginate(100);
                break;
        }

        if ($request->get("download") == 1) {
            ExcelWriteHelper::download2007(
                function ($excel) use ($locations) {
                    $excel->setActiveSheetIndex(0);
                    $current_sheet = $excel->getActiveSheet();
                    # 定义首行
                    $current_sheet->setCellValue("A1", "仓名称");
                    $current_sheet->setCellValue("B1", "区名称");
                    $current_sheet->setCellValue("C1", "排架层位名称");
                    $current_sheet->setCellValue("D1", "编码");
                    $current_sheet->setCellValue("E1", "名称");

                    $current_sheet->getColumnDimension("A")->setWidth(30);
                    $current_sheet->getColumnDimension("B")->setWidth(20);
                    $current_sheet->getColumnDimension("C")->setWidth(30);
                    $current_sheet->getColumnDimension("D")->setWidth(20);
                    $current_sheet->getColumnDimension("D")->setWidth(30);

                    # 放数据
                    $row = 2;
                    foreach ($locations as $location) {
                        $current_sheet->setCellValue("A{$row}", $location->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name);
                        $current_sheet->setCellValue("B{$row}", $location->WithTier->WithShelf->WithPlatoon->WithArea->name);
                        $current_sheet->setCellValue("C{$row}", $location->WithTier->WithShelf->WithPlatoon->name . $location->WithTier->WithShelf->name . $location->WithTier->name . $location->name);
                        $current_sheet->setCellValue("D{$row}", $location->unique_code);
                        $current_sheet->setCellValue("E{$row}", LocationFacade::makeLocationUniqueCode($location->unique_code, 1));

                        $row += 1;
                    }

                    return $excel;
                },
                "位置"
            );
        }
        if ($request->get("download") == 2) {
            ExcelWriteHelper::download2007(
                function ($excel) {
                    $excel->createSheet(1)->setTitle("仓");
                    $excel->setActiveSheetIndex(1);
                    $current_sheet = $excel->getActiveSheet();
                    $storehouses = Storehouse::get();
                    $current_sheet->setCellValue("A1", "仓名称");
                    $current_sheet->setCellValue("B1", "仓编码");
                    $row = 2;
                    foreach ($storehouses as $storehouse) {
                        $current_sheet->setCellValue("A{$row}", $storehouse->name);
                        $current_sheet->setCellValue("B{$row}", $storehouse->unique_code);
                        $row += 1;
                    }
                    $excel->createSheet(2)->setTitle("区");
                    $excel->setActiveSheetIndex(2);
                    $current_sheet = $excel->getActiveSheet();
                    $current_sheet->setCellValue("A1", "区全名称");
                    $current_sheet->setCellValue("B1", "区名称");
                    $current_sheet->setCellValue("C1", "区编码");
                    $areas = Area::with(["WithStorehouse"])->get();
                    $row = 2;
                    foreach ($areas as $area) {
                        $current_sheet->setCellValue("A{$row}", $area->WithStorehouse->name . $area->name);
                        $current_sheet->setCellValue("B{$row}", $area->name);
                        $current_sheet->setCellValue("C{$row}", $area->unique_code);
                        $row += 1;
                    }

                    $excel->createSheet(3)->setTitle("排");
                    $excel->setActiveSheetIndex(3);
                    $current_sheet = $excel->getActiveSheet();
                    $current_sheet->setCellValue("A1", "排全名称");
                    $current_sheet->setCellValue("B1", "排名称");
                    $current_sheet->setCellValue("C1", "排编码");
                    $platoons = Platoon::with(["WithArea"])->get();
                    $row = 2;
                    foreach ($platoons as $platoon) {
                        $current_sheet->setCellValue("A{$row}", $platoon->WithArea->WithStorehouse->name . $platoon->WithArea->name . $platoon->name);
                        $current_sheet->setCellValue("B{$row}", $platoon->name);
                        $current_sheet->setCellValue("C{$row}", $platoon->unique_code);
                        $row += 1;
                    }
                    $excel->createSheet(4)->setTitle("架");
                    $excel->setActiveSheetIndex(4);
                    $current_sheet = $excel->getActiveSheet();
                    $current_sheet->setCellValue("A1", "架全名称");
                    $current_sheet->setCellValue("B1", "架名称");
                    $current_sheet->setCellValue("C1", "架编码");
                    $shelfs = Shelf::with(["WithPlatoon"])->get();
                    $row = 2;
                    foreach ($shelfs as $shelf) {
                        $current_sheet->setCellValue("A{$row}", $shelf->WithPlatoon->WithArea->WithStorehouse->name . $shelf->WithPlatoon->WithArea->name . $shelf->WithPlatoon->name . $shelf->name);
                        $current_sheet->setCellValue("B{$row}", $shelf->name);
                        $current_sheet->setCellValue("C{$row}", $shelf->unique_code);
                        $row += 1;
                    }

                    $excel->createSheet(5)->setTitle("层");
                    $excel->setActiveSheetIndex(5);
                    $current_sheet = $excel->getActiveSheet();
                    $current_sheet->setCellValue("A1", "层全名称");
                    $current_sheet->setCellValue("B1", "层名称");
                    $current_sheet->setCellValue("C1", "层编码");
                    $tiers = Tier::with(["WithShelf"])->get();
                    $row = 2;
                    foreach ($tiers as $tier) {
                        $current_sheet->setCellValue("A{$row}", $tier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $tier->WithShelf->WithPlatoon->WithArea->name . $tier->WithShelf->WithPlatoon->name . $tier->WithShelf->name . $tier->name);
                        $current_sheet->setCellValue("B{$row}", $tier->name);
                        $current_sheet->setCellValue("C{$row}", $tier->unique_code);
                        $row += 1;
                    }

                    $excel->createSheet(6)->setTitle("位");
                    $excel->setActiveSheetIndex(6);
                    $current_sheet = $excel->getActiveSheet();
                    $current_sheet->setCellValue("A1", "位全名称");
                    $current_sheet->setCellValue("B1", "位名称");
                    $current_sheet->setCellValue("C1", "位编码");
                    $positions = Position::with(["WithTier"])->get();
                    $row = 2;
                    foreach ($positions as $position) {
                        $current_sheet->setCellValue("A{$row}", $position->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $position->WithTier->WithShelf->WithPlatoon->WithArea->name . $position->WithTier->WithShelf->WithPlatoon->name . $position->WithTier->WithShelf->name . $position->WithTier->name . $position->name);
                        $current_sheet->setCellValue("B{$row}", $position->name);
                        $current_sheet->setCellValue("C{$row}", $position->unique_code);
                        $row += 1;
                    }

                    $excel->removeSheetByIndex(0);
                    return $excel;
                },
                "位置全部"
            );
        }

        return view("Storehouse.Location.index", [
            "locations" => $locations,
            "code" => $code,
            "lenCode" => $lenCode,
            "storehouses_as_json" => $storehouses,
            "areas_as_json" => $areas,
            "platoons_as_json" => $platoons,
            "shelves_as_json" => $shelves,
            "tiers_as_json" => $tiers,
            "positions_as_json" => $positions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|View
     */
    final public function create()
    {
        $platoon_types = [];
        foreach (Platoon::$TYPES as $code => $type) $platoon_types[] = ['label' => $type, 'value' => $code];
        // $platoon_types = collect(Platoon::$TYPES);
        return view('Storehouse.Location.create', ['platoon_types' => json_encode($platoon_types, JSON_UNESCAPED_UNICODE),]);
    }

    /**
     * 获取仓json
     * @return false|string
     */
    final public function storehouseWithIndex()
    {
        try {
            $storehouses = DB::table('storehouses')->select('id', 'name', 'unique_code')->get();
            $json = [
                'code' => 0,
                'msg' => '',
                'count' => $storehouses->count(),
                'data' => $storehouses->toArray()
            ];
            return TextHelper::toJson($json);
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 仓添加
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    final public function storehouseWithStore(Request $request)
    {
        try {
            $v = ValidateHelper::firstErrorByRequest($request, new StorehouseStoreRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $storehouse = new Storehouse();
            $storehouseUniqueCode = $storehouse->getUniqueCode();
            $storehouse->fill([
                'name' => TextHelper::strip($request->get('name')),
                'unique_code' => $storehouseUniqueCode,
                "workshop_unique_code" => env("CURRENT_WORKSHOP_UNIQUE_CODE"),
            ]);
            $storehouse->saveOrFail();
            return JsonResponseFacade::created();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 仓修改
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws Throwable
     */
    final public function storehouseWithUpdate(Request $request, $id)
    {
        try {
            $storehouse = Storehouse::with([])->where('id', $id)->firstOrFail();
            $_POST['id'] = $id;
            $v = ValidateHelper::firstErrorByRequest($request, new StorehouseUpdateRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $req = $request->all();
            $req['name'] = TextHelper::strip($req['name']);
            $storehouse->fill($req)->saveOrFail();

            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 仓删除
     * @param $id
     * @return JsonResponse
     * @throws \Exception
     */
    final public function storehouseWithDestroy($id)
    {
        try {
            $storehouse = Storehouse::with(['WithAreas'])->where('id', $id)->firstOrFail();
            if ($storehouse->WithAreas->isNotEmpty()) return JsonResponseFacade::errorValidate('请先删除区');
            $storehouse->delete();
            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 获取区json
     * @param Request $request
     * @return false|string
     */
    final public function areaWithIndex(Request $request)
    {
        try {
            $storehouseUniqueCode = $request->get('storehouse_unique_code', '');
            $json = [
                'code' => 0,
                'msg' => '',
                'count' => 0,
                'data' => []
            ];
            $areas = DB::table('areas')->select('id', 'name', 'unique_code', 'storehouse_unique_code')->where('storehouse_unique_code', $storehouseUniqueCode)->get();
            if (!empty($areas)) {
                $json['count'] = $areas->count();
                $json['data'] = $areas->toArray();
            }

            return TextHelper::toJson($json);
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 区添加
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    final public function areaWithStore(Request $request)
    {
        try {
            $storehouseUniqueCode = $request->get('storehouse_unique_code');
            $_POST['storehouse_unique_code'] = $storehouseUniqueCode;
            $v = ValidateHelper::firstErrorByRequest($request, new AreaStoreRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $area = new Area();
            $areaUniqueCode = $area->getUniqueCode($storehouseUniqueCode);
            $area->fill([
                'name' => TextHelper::strip($request->get('name')),
                'storehouse_unique_code' => $storehouseUniqueCode,
                'unique_code' => $areaUniqueCode,
                'type' => $request->get('type') ?? 'FIXED',
            ]);
            $area->saveOrFail();
            return JsonResponseFacade::created();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 区修改
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws Throwable
     */
    final public function areaWithUpdate(Request $request, $id)
    {
        try {
            $area = Area::with([])->where('id', $id)->firstOrFail();
            $_POST['id'] = $id;
            $_POST['storehouse_unique_code'] = $area->storehouse_unique_code;
            $v = ValidateHelper::firstErrorByRequest($request, new AreaUpdateRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $req = $request->all();
            $req['name'] = TextHelper::strip($req['name']);
            $area->fill($req)->saveOrFail();

            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 区删除
     * @param $id
     * @return JsonResponse
     * @throws \Exception
     */
    final public function areaWithDestroy($id)
    {
        try {
            $area = Area::with(['WithPlatoons'])->where('id', $id)->firstOrFail();
            if ($area->WithPlatoons->isNotEmpty()) return JsonResponseFacade::errorValidate('请先删除排');
            $area->delete();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 获取排json
     * @param Request $request
     * @return false|string
     */
    final public function platoonWithIndex(Request $request)
    {
        try {
            $json = [
                'code' => 0,
                'msg' => '',
                'count' => 0,
                'data' => []
            ];
            $areaUniqueCode = $request->get('area_unique_code', '');
            $platoons = DB::table('platoons')->selectRaw("id,name,unique_code,area_unique_code,type,case when type = 'FIXED' then '成品' when type = 'FIXING' then '备品' when type = 'MATERIAL' then '材料' when type = 'EMERGENCY' then '应急备品' else '' end as type_name")->where('area_unique_code', $areaUniqueCode)->get();;
            if (!empty($platoons)) {
                $json['count'] = $platoons->count();
                $json['data'] = $platoons->toArray();
            }

            return TextHelper::toJson($json);
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 排添加
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    final public function platoonWithStore(Request $request)
    {
        try {
            $areaUniqueCode = $request->get('area_unique_code');
            $_POST['area_unique_code'] = $areaUniqueCode;
            $v = ValidateHelper::firstErrorByRequest($request, new PlatoonStoreRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $platoon = new Platoon();
            $platoonUniqueCode = $platoon->getUniqueCode($areaUniqueCode);
            $platoon->fill([
                'name' => TextHelper::strip($request->get('name')),
                'area_unique_code' => $areaUniqueCode,
                'unique_code' => $platoonUniqueCode,
                'type' => $request->get('type'),
            ]);
            $platoon->saveOrFail();
            return JsonResponseFacade::created();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 排修改
     * @param Request $request
     * @param $id
     * @throws EmptyException
     * @throws ValidateException
     */
    final public function platoonWithUpdate(Request $request, $id)
    {
        $platoon = Platoon::with([])->where('id', $id)->first();
        if (!$platoon) throw new EmptyException('排不存在');
        $_POST['id'] = $id;
        $_POST['area_unique_code'] = $platoon->area_unique_code;

        $v = ValidateHelper::firstErrorByRequest($request, new PlatoonUpdateRequest());
        if ($v !== true) throw new ValidateException($v);

        // return JsonResponseFacade::dump($request->all());
        $platoon->fill($request->all())->saveOrFail();

        return JsonResponseFacade::updated();
    }

    /**
     * 排删除
     * @param $id
     * @return JsonResponse
     * @throws \Exception
     */
    final public function platoonWithDestroy($id)
    {
        try {
            $platoon = Platoon::with(['WithShelfs'])->where('id', $id)->firstOrFail();
            if ($platoon->WithShelfs->isNotEmpty()) return JsonResponseFacade::errorValidate('请先删除架');
            $platoon->delete();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 获取架json
     * @param Request $request
     * @return false|string
     */
    final public function shelfWithIndex(Request $request)
    {
        try {
            $json = [
                'code' => 0,
                'msg' => '',
                'count' => 0,
                'data' => []
            ];
            $platoonUniqueCode = $request->get('platoon_unique_code', '');
            $shelfs = DB::table('shelves')->select('id', 'name', 'unique_code', 'platoon_unique_code', 'location_img')->where('platoon_unique_code', $platoonUniqueCode)->get();
            if (!empty($shelfs)) {
                $json['count'] = $shelfs->count();
                $json['data'] = $shelfs->toArray();
            }

            return TextHelper::toJson($json);
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 架添加
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    final public function shelfWithStore(Request $request)
    {
        try {
            $platoonUniqueCode = $request->get('platoon_unique_code');
            $_POST['platoon_unique_code'] = $platoonUniqueCode;
            $v = ValidateHelper::firstErrorByRequest($request, new ShelfStoreRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $shelf = new Shelf();
            $shelfUniqueCode = $shelf->getUniqueCode($platoonUniqueCode);
            $shelf->fill([
                'name' => TextHelper::strip($request->get('name')),
                'platoon_unique_code' => $platoonUniqueCode,
                'unique_code' => $shelfUniqueCode,
            ]);
            $shelf->saveOrFail();
            return JsonResponseFacade::created();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 架修改
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Throwable
     */
    final public function shelfWithUpdate(Request $request, $id)
    {
        try {
            $shelf = Shelf::with([])->where('id', $id)->firstOrFail();
            $_POST['id'] = $id;
            $_POST['platoon_unique_code'] = $shelf->platoon_unique_code;
            $v = ValidateHelper::firstErrorByRequest($request, new ShelfUpdateRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $req = $request->all();
            $req['name'] = TextHelper::strip($req['name']);
            $shelf->fill($req)->saveOrFail();

            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 架删除
     * @param $id
     * @return JsonResponse
     * @throws \Exception
     */
    final public function shelfWithDestroy($id)
    {
        try {
            $shelf = Shelf::with(['WithTiers'])->where('id', $id)->firstOrFail();
            if ($shelf->WithTiers->isNotEmpty()) return JsonResponseFacade::errorValidate('请先删除层');
            $shelf->delete();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 架-上传图片
     * @param Request $request
     * @return mixed
     * @throws Throwable
     */
    final public function uploadImageWithShelf(Request $request)
    {
        try {
            $shelfId = $request->get('shelf_id');
            $shelf = Shelf::with([])->where('id', $shelfId)->firstOrFail();
            $file = $request->file('file', null);
            if (empty($file) && is_null($file)) return JsonResponseFacade::errorForbidden('上传文件失败');
            $path = public_path('images/location');
            $fileSystem = FileSystem::init(__DIR__);
            if (!is_dir($path)) $fileSystem->makeDir($path);
            $imageName = date('YmdHis') . '-' . $file->getClientOriginalName();
            $file->move($path, $imageName);
            $oldImage = public_path($shelf->location_img);
            $shelf->fill([
                'location_img' => "/images/location/{$imageName}",
            ])->saveOrFail();
            if (is_file($oldImage)) $fileSystem->setPath($oldImage)->deleteFile();

            return JsonResponseFacade::created('上传成功');
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 获取层json
     * @param Request $request
     * @return false|string
     */
    final public function tierWithIndex(Request $request)
    {
        try {
            $json = [
                'code' => 0,
                'msg' => '',
                'count' => 0,
                'data' => []
            ];
            $shelfUniqueCode = $request->get('shelf_unique_code', '');
            $tiers = DB::table('tiers')->select('id', 'name', 'unique_code', 'shelf_unique_code')->where('shelf_unique_code', $shelfUniqueCode)->get();
            if (!empty($tiers)) {
                $json['count'] = $tiers->count();
                $json['data'] = $tiers->toArray();
            }

            return TextHelper::toJson($json);
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 层添加
     * @param Request $request
     * @return mixed
     */
    final public function tierWithStore(Request $request)
    {
        try {
            $shelfUniqueCode = $request->get('shelf_unique_code');
            if (empty($shelfUniqueCode)) return JsonResponseFacade::errorValidate('请选择架');
            $tier_count = $request->get('tier_count', 1);
            $uniqueCodes = Tier::generateUniqueCodes($shelfUniqueCode, $tier_count);
            $insert = array_map(function ($value) use ($shelfUniqueCode) {
                return [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'unique_code' => $value['unique_code'],
                    'shelf_unique_code' => $shelfUniqueCode,
                    'name' => $value['name'],
                ];
            }, $uniqueCodes);

            DB::table('tiers')->insert($insert);
            return JsonResponseFacade::created();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 层修改
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws Throwable
     */
    final public function tierWithUpdate(Request $request, $id)
    {
        try {
            $tier = Tier::with([])->where('id', $id)->firstOrFail();
            $_POST['id'] = $id;
            $_POST['shelf_unique_code'] = $tier->shelf_unique_code;
            $v = ValidateHelper::firstErrorByRequest($request, new TierUpdateRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $req = $request->all();
            $req['name'] = TextHelper::strip($req['name']);
            $tier->fill($req)->saveOrFail();

            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 层删除
     * @param $id
     * @return JsonResponse
     * @throws \Exception
     */
    final public function tierWithDestroy($id)
    {
        try {
            $tier = Tier::with(['WithPositions'])->where('id', $id)->firstOrFail();
            if ($tier->WithPositions->isNotEmpty()) return JsonResponseFacade::errorValidate('请先删除位');
            $tier->delete();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }


    /**
     * 获取位json
     * @param string $tierUniqueCode
     * @return false|string
     */
    final public function positionWithIndex(Request $request)
    {
        try {
            $json = [
                'code' => 0,
                'msg' => '',
                'count' => 0,
                'data' => []
            ];
            $tierUniqueCode = $request->get('tier_unique_code', '');
            $positions = DB::table('positions')->select('id', 'name', 'unique_code', 'tier_unique_code')->where('tier_unique_code', $tierUniqueCode)->get();
            if (!$positions->isEmpty()) {
                $json['count'] = $positions->count();
                $json['data'] = $positions->toArray();
            }

            return TextHelper::toJson($json);
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 位添加
     * @param Request $request
     * @return mixed
     */
    final public function positionWithStore(Request $request)
    {
        try {
            $tier_unique_code = $request->get('tier_unique_code');
            if (empty($tier_unique_code)) return JsonResponseFacade::errorEmpty('请选择层');
            $positionCount = $request->get('position_count', 1);
            $uniqueCodes = Position::generateUniqueCodes($tier_unique_code, $positionCount);
            $insert = array_map(function ($value) use ($tier_unique_code) {
                return [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'unique_code' => $value['unique_code'],
                    'tier_unique_code' => $tier_unique_code,
                    'name' => $value['name'],
                ];
            }, $uniqueCodes);

            DB::table('positions')->insert($insert);
            return JsonResponseFacade::created();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 位修改
     * @param Request $request
     * @param $id
     * @return JsonResponse
     * @throws Throwable
     */
    final public function positionWithUpdate(Request $request, $id)
    {
        try {
            $position = Position::with([])->where('id', $id)->firstOrFail();
            $_POST['id'] = $id;
            $_POST['tier_unique_code'] = $position->tier_unique_code;
            $v = ValidateHelper::firstErrorByRequest($request, new PositionUpdateRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $req = $request->all();
            $req['name'] = TextHelper::strip($req['name']);
            $position->fill($req)->saveOrFail();

            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    public function getUpload()
    {
        try {
            ExcelWriteHelper::download(
                function ($excel) {
                    $excel->setActiveSheetIndex(0);
                    $current_sheet = $excel->getActiveSheet();

                    // 首行数据
                    $first_row_data = [
                        ['context' => '仓（必须提前手动创建仓库）', 'color' => 'red', 'width' => 30],  // A
                        ['context' => '区', 'color' => 'black', 'width' => 20],  // B
                        ['context' => '排', 'color' => 'black', 'width' => 20],  // C
                        ['context' => '架', 'color' => 'black', 'width' => 20],  // D
                        ['context' => '层', 'color' => 'black', 'width' => 20],  // E
                        ['context' => '位（总数）', 'color' => 'black', 'width' => 25],  // F
                    ];
                    // 填充首行数据
                    foreach ($first_row_data as $col => $first_row_datum) {
                        $col_for_excel = ExcelWriteHelper::int2Excel($col);
                        ['context' => $context, 'color' => $color, 'width' => $width] = $first_row_datum;
                        $current_sheet->setCellValueExplicit("{$col_for_excel}1", $context);
                        $current_sheet->getStyle("{$col_for_excel}1")->getFont()->setColor(ExcelWriteHelper::getFontColor($color));
                        $current_sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($col))->setWidth($width);
                    }

                    return $excel;
                },
                "批量创建仓库位置",
                ExcelWriteHelper::$VERSION_5
            );
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 批量上传
     */
    final public function postUpload(Request $request)
    {
        try {
            $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
                ->originRow(2)
                ->withSheetIndex(0);

            if (empty(@$excel['success'])) return back()->with('danger', 'excel 内容为空');

            $total = 0;

            DB::beginTransaction();
            foreach ($excel['success'] as $row_data) {
                if (empty(array_filter($row_data, function ($val) {
                    return !empty($val);
                }))) continue;

                list($warehouse_name, $area_name, $platoon_name, $shelf_name, $tier_name, $position_number) = $row_data;

                $storehouse = Storehouse::with([])->where('name', $warehouse_name)->first();
                if (!$storehouse) return back()->with('danger', "仓库：{$warehouse_name} 不存在，必须先手动创建仓库");

                $area = Area::with([])->where('storehouse_unique_code', $storehouse->unique_code)->where('name', $area_name)->first();
                if (!$area) {
                    $area = Area::with([])
                        ->create([
                            'name' => $area_name,
                            'storehouse_unique_code' => $storehouse->unique_code,
                            'unique_code' => Area::generateUniqueCode($storehouse->unique_code),
                        ]);
                }

                $platoon = Platoon::with([])->where('area_unique_code', $area->unique_code)->where('name', $platoon_name)->first();
                if (!$platoon) {
                    $platoon = Platoon::with([])
                        ->create([
                            'name' => $platoon_name,
                            'unique_code' => Platoon::generateUniqueCode($area->unique_code),
                            'area_unique_code' => $area->unique_code,
                        ]);
                }

                $shelf = Shelf::with([])->where('platoon_unique_code', $platoon->unique_code)->where('name', $shelf_name)->first();
                if (!$shelf) {
                    $shelf = Shelf::with([])
                        ->create([
                            'name' => $shelf_name,
                            'unique_code' => Shelf::generateUniqueCode($platoon->unique_code),
                            'platoon_unique_code' => $platoon->unique_code,
                        ]);
                }

                $tier = Tier::with([])->where('shelf_unique_code', $shelf->unique_code)->where('name', $tier_name)->first();
                if (!$tier) {
                    $tier = Tier::with([])
                        ->create([
                            'name' => $tier_name,
                            'unique_code' => array_first(Tier::generateUniqueCodes($shelf->unique_code, 1))['unique_code'],
                            'shelf_unique_code' => $shelf->unique_code,
                        ]);
                }

                for ($i = 0; $i < $position_number; $i++) {
                    $position = Position::with([])
                        ->create([
                            'name' => $i + 1,
                            'unique_code' => array_first(Position::generateUniqueCodes($tier->unique_code, 1))['unique_code'],
                            'tier_unique_code' => $tier->unique_code,
                        ]);
                    $total++;
                }
            }
            DB::commit();

            return back()->with('success', "成功上传：{$total}个位置");
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 层删除
     * @param $id
     * @return JsonResponse
     * @throws \Exception
     */
    final public function positionWithDestroy($id)
    {
        try {
            $position = Position::with(['WithEntireInstances', 'WithPartInstances'])->where('id', $id)->firstOrFail();
            if ($position->WithEntireInstances->isNotEmpty()) return HttpResponseHelper::errorValidate('该位置上存在整件设备');
            if ($position->WithPartInstances->isNotEmpty()) return HttpResponseHelper::errorValidate('该位置上存在部件设备');
            $position->delete();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 获取位置图片
     * @param $identityCode
     * @return JsonResponse
     * @throws \Exception
     */
    public function getImg($identityCode)
    {
        try {
            $entireInstance = EntireInstance::with(['WithPosition'])->where('identity_code', $identityCode)->firstOrFail();
            $data = [
                'location_full_name' => $entireInstance->WithPosition ? $entireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $entireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $entireInstance->WithPosition->WithTier->WithShelf->name . $entireInstance->WithPosition->WithTier->name . $entireInstance->WithPosition->name : '',
                'location_img' => $entireInstance->WithPosition ? $entireInstance->WithPosition->WithTier->WithShelf->location_img : '',
            ];
            return HttpResponseHelper::data($data);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return HttpResponseHelper::error($e->getMessage());
        }
    }

    /**
     * 获取仓库定位图
     * @param string $location_unique_code
     * @return mixed
     */
    final public function getImg2(string $location_unique_code)
    {
        $img_url = DB::table('positions')->where('unique_code', $location_unique_code)->value('img_url');
        if ($img_url) return JsonResponseFacade::errorEmpty('没有找到仓库定位图');
        return JsonResponseFacade::data([
            'location_ful_name' => request('locationFullName'),
            'location_img' => $img_url ? "/images/location/{$img_url}" : ''
        ]);
    }

    /**
     * drawing warehouse location platform and front view
     * usage
     * GET params：
     *     1. bg_class background class name by adminlte
     *     2. position_unique_code current highlight position unique code
     * @param string $shelf_unique_code shelf unique code
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|View|string
     */
    final public function getCanvas(string $shelf_unique_code)
    {
        $shelf = Shelf::with([
            'WithPlatoon',
            'WithPlatoon.WithArea',
            'WithPlatoon.WithArea.WithStorehouse',
            'WithTiers',
            'WithTiers.WithPositions',
        ])
            ->where('unique_code', $shelf_unique_code)
            ->first();

        $area = Area::with([
            'WithStorehouse',
            'WithPlatoons',
            'WithPlatoons.WithShelves',
        ])
            ->where('unique_code', @$shelf->WithPlatoon->WithArea->unique_code)
            ->first();
        if (!$area) return '<h1 class="text-danger">错误：当前位置没有找到所属的上级</h1>';

        return view('Storehouse.Location.canvas', [
            'shelf_as_json' => $shelf->toJson(),
            'area_as_json' => $area->toJson(),
        ]);
    }
}
