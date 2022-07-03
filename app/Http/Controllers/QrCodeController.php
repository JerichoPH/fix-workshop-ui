<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Model\Area;
use App\Model\Install\InstallPosition;
use App\Model\Install\InstallTier;
use App\Model\Material;
use App\Model\Platoon;
use App\Model\Position;
use App\Model\PrintIdentityCode;
use App\Model\Shelf;
use App\Model\Storehouse;
use App\Model\Tier;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Jericho\HttpResponseHelper;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class QrCodeController extends Controller
{
    /**
     * 打印二维码
     * @param Request $request
     * @return Factory|View|string
     */
    final public function printQrCode(Request $request)
    {
        try {
            if (request('size_type') == 10) {
                $size_type = 100;
            } else {
                $size_type = 160;
            }

            $default = function () use ($size_type) {
                $contents = [];
                PrintIdentityCode::with([
                    'EntireInstance',
                    'EntireInstance.Category',
                    'EntireInstance.EntireModel',
                    'EntireInstance.EntireModel.Parent',
                ])
                    ->where('account_id', session('account.id'))
                    ->each(function ($print_identity_code) use (&$contents, $size_type) {
                        $contents[] = [
                            'serial_number' => @$print_identity_code->EntireInstance->serial_number ?: '',
                            'factory_device_code' => @$print_identity_code->EntireInstance->factory_device_code ?: '',
                            'identity_code' => @$print_identity_code->entire_instance_identity_code ?: '',
                            'category_name' => @$print_identity_code->EntireInstance->Category->name ?: '',
                            'category_nickname' => @$print_identity_code->EntireInstance->Category->nickname ?: '',
                            'entire_model_name' => @$print_identity_code->EntireInstance->EntireModel->Parent ? @$print_identity_code->EntireInstance->EntireModel->Parent->name : @$print_identity_code->EntireInstance->EntireModel->name,
                            'entire_model_nickname' => @$print_identity_code->EntireInstance->EntireModel->Parent ? @$print_identity_code->EntireInstance->EntireModel->Parent->nickname : @$print_identity_code->EntireInstance->EntireModel->nickname,
                            'sub_model_name' => @$print_identity_code->EntireInstance->EntireModel->Parent ? @$print_identity_code->EntireInstance->EntireModel->name : '',
                            'sub_model_nickname' => @$print_identity_code->EntireInstance->EntireModel->Parent ? @$print_identity_code->EntireInstance->EntireModel->nickname : '',
                            'img' => QrCode::format('png')->size($size_type)->margin(0)->generate($print_identity_code->entire_instance_identity_code),
                            'maintain_station_name' => @$print_identity_code->EntireInstance->maintain_station_name ?: '',
                            'maintain_location_code' => @$print_identity_code->EntireInstance->maintain_location_code ? (@$print_identity_code->EntireInstance->InstallPosition->real_name ?: @$print_identity_code->EntireInstance->maintain_location_code) : '',
                            'crossroad_number' => @$print_identity_code->EntireInstance->crossroad_number ?: '',
                            'open_direction' => @$print_identity_code->EntireInstance->open_direction ?: '',
                            'last_out_at' => @$print_identity_code->EntireInstance->last_out_at ?: '',
                            'made_at' => @$print_identity_code->EntireInstance->made_at ?: '',
                        ];
                    });
                return $contents;
            };
            $contents = $default();

            DB::table('print_identity_codes')->where('account_id', session('account.id'))->delete();

            if (!DB::table('print_identity_codes')->where('id', '>', 0)->exists()) {
                DB::table('print_identity_codes')->truncate();
            }

            switch (env('ORGANIZATION_CODE')) {
                case 'B048':
                    if (request("size_type") == 10) {
                        // 兄弟 12*10
                        return view('QrCode.printQrCode_12_10_nickname', ['contents' => $contents,]);
                    } else {
                        // 广州 36*30 兄弟
                        return view('QrCode.printQrCode_B048_nickname', ['contents' => $contents,]);
                    }
                case 'B050':
                    if (request('size_type') == 2) {
                        // 怀化 20*12
                        return view('QrCode.printQrCode_B050_12mm', ['contents' => $contents,]);
                    } else if (request("size_type") == 10) {
                        // 兄弟 12*10
                        return view('QrCode.printQrCode_12_10_nickname', ['contents' => $contents,]);
                    } else {
                        // 怀化 40*25
                        return view('QrCode.printQrCode_B050_nickname', ['contents' => $contents,]);
                    }
                case 'B049':
                case 'B051':
                case 'B052':
                case 'B053':
                case 'B074':
                    if (request("size_type") == 10) {
                        // 兄弟 12*10
                        return view('QrCode.printQrCode_12_10_nickname', ['contents' => $contents,]);
                    } else {
                        // 长沙、衡阳、惠州、肇庆、海口 30*25
                        return view('QrCode.printQrCode_B052_nickname', ['contents' => $contents,]);
                    }
            }
        } catch (Exception $e) {
            // return CommonFacade::ddExceptionWithAppDebug($e);
            return response()->make("<h1>错误：{$e->getMessage()}</h1>");
        }
    }

    /**
     * 打印二维码（带位置）
     * @param Request $request
     * @return Factory|Application|HttpResponse|View|mixed
     */
    final public function printQrCodeAndLocation(Request $request)
    {
        try {
            $size_type = 160;
            if (env("ORGANIZATION_CODE") == "B048") {
                $size_types = [
                    1 => 160,
                    2 => 85,
                    3 => 140,
                ];
                $size_type = $size_types[$request->get('size_type')] ?? 140;  // 二维码标签大小
            }

            $default = function () use ($size_type) {
                PrintIdentityCode::with([
                    'EntireInstance',
                    'EntireInstance.Category',
                    'EntireInstance.EntireModel',
                    'EntireInstance.EntireModel.Parent',
                    'EntireInstance.InstallPosition',
                ])
                    ->where('account_id', session('account.id'))
                    ->each(function ($print_identity_code) use (&$contents, $size_type) {
                        $contents[] = [
                            'serial_number' => @$print_identity_code->EntireInstance->serial_number ?: '',
                            'identity_code' => @$print_identity_code->entire_instance_identity_code ?: '',
                            'category_name' => @$print_identity_code->EntireInstance->Category->name ?: '',
                            'category_nickname' => @$print_identity_code->EntireInstance->Category->nickname ?: '',
                            'entire_model_name' => @$print_identity_code->EntireInstance->EntireModel->Parent ? @$print_identity_code->EntireInstance->EntireModel->Parent->name : @$print_identity_code->EntireInstance->EntireModel->name,
                            'entire_model_nickname' => @$print_identity_code->EntireInstance->EntireModel->Parent ? @$print_identity_code->EntireInstance->EntireModel->Parent->nickname : @$print_identity_code->EntireInstance->EntireModel->nickname,
                            'sub_model_name' => @$print_identity_code->EntireInstance->EntireModel->Parent ? @$print_identity_code->EntireInstance->EntireModel->name : '',
                            'sub_model_nickname' => @$print_identity_code->EntireInstance->EntireModel->Parent ? @$print_identity_code->EntireInstance->EntireModel->nickname : '',
                            'img' => QrCode::format('png')->size($size_type)->margin(0)->generate($print_identity_code->entire_instance_identity_code),
                            'maintain_station_name' => @$print_identity_code->EntireInstance->maintain_station_name ?: '',
                            'maintain_location_code' => @$print_identity_code->EntireInstance->maintain_location_code ? (@$print_identity_code->EntireInstance->InstallPosition->real_name ?: @$print_identity_code->EntireInstance->maintain_location_code) : '',
                            'crossroad_number' => @$print_identity_code->EntireInstance->crossroad_number ?: '',
                            'open_direction' => @$print_identity_code->EntireInstance->open_direction ?: '',
                            'last_out_at' => @$print_identity_code->EntireInstance->last_out_at ? Carbon::parse($print_identity_code->EntireInstance->last_out_at)->format("Y-m-d") : '',
                            'made_at' => @$print_identity_code->EntireInstance->made_at ?: '',
                            'l_name' => @$print_identity_code->EntireInstance->Line->name ?: '',  // 线别
                        ];
                    });

                return $contents;
            };
            $contents = $default();

            if (DB::table('print_identity_codes')->where('id', '>', 0)->doesntExist()) {
                DB::table('print_identity_codes')->truncate();
            }

            switch (env('ORGANIZATION_CODE')) {
                case 'B048':
                    if (request('size_type') == 2) {
                        return view('Qrcode.printQrCodeAndLocation_B048_nickname', ['contents' => $contents,]);
                    } else {
                        return view('QrCode.printQrCodeAndLocation_B048', ['contents' => $contents]);
                    }
                case 'B050':
                    if (request('size_type') == 2) {
                        return view('QrCode.printQrCodeAndLocation_B050_12mm', ['contents' => $contents,]);
                    } else {
                        return view('QrCode.printQrCodeAndLocation_B050_nickname', ['contents' => $contents,]);
                    }
                case 'B049':
                    return view("QrCode.printQrCodeAndLocation_B049_nickname", ["contents" => $contents,]);
                case 'B051':
                case 'B052':
                case 'B053':
                case 'B074':
                    return view('QrCode.printQrCodeAndLocation_B052_nickname', ['contents' => $contents,]);
                default:
                    return view('QrCode.printQrCodeAndLocation', ['contents' => $contents,]);
            }
        } catch (Exception $e) {
            // return CommonFacade::ddExceptionWithAppDebug($e);
            return response()->make("<h1>错误：{$e->getMessage()}</h1>");
        }
    }

    /**
     * 材料打印标签
     * @return Factory|Application|RedirectResponse|View
     */
    final public function getPrintQrCodeMaterial()
    {
        try {
            $identity_codes = collect(explode(',', request('identity_codes')));
            $materials = [];
            $identity_codes->each(function ($identity_code) use (&$materials) {
                $materials[] = Material::with([])->where('identity_code', $identity_code)->first();
            });

            // switch (request('type')) {
            //     case 1:
            //         // 35 x 20
            //         return view('QrCode.location_35x20', ['contents' => $contents, 'type' => $request->get('type'),]);
            //     case 2:
            //         // 20 x 12
            //         return view('QrCode.location_20x12', ['contents' => $contents, 'type' => $request->get('type'),]);
            //     case 3:
            //         // 40 x 25
            //         return view('QrCode.location_40x25', ['contents' => $contents, 'type' => $request->get('type'),]);
            //     case 4:
            //         // 36 x 20 brother
            //         return view('QrCode.location_36x20_brother', ['contents' => $contents, 'type' => $request->get('type'),]);
            // }

            dd($materials);
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::handleExceptionWithAppDebug($e);
        }
    }

    final public function show($entireInstanceIdentityCode)
    {
        $qrCodeContent = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(512)->encoding('UTF-8')->errorCorrection('H')->generate($entireInstanceIdentityCode);
        return view($this->view())->with("qrCodeContent", $qrCodeContent);
    }

    final private function view($viewName = null)
    {
        $viewName = $viewName ?: request()->route()->getActionMethod();
        return "QrCode.{$viewName}";
    }

    /**
     * 解析二维码扫码请求
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|HttpResponse
     */
    final public function parse(Request $request)
    {
        try {
            switch (request()->type) {
                case 'scan':
                    return response()->json([
                        'type' => 'redirect',
                        'url' => url('search', $request->params['identity_code'])
                    ]);
                case 'buy_in':
                case 'fixing':
                case 'return_factory':
                case 'factory_return':
                    break;
            }
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            return Response::make('意外错误', 500);
        }
    }

    /**
     * 生成二维码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function generateQrcode(Request $request)
    {
        try {
            $data = [];
            $size = $request->get('size', 200);
            $type = $request->get('type', 1); #1.生成base64,直接在页面使用不保存图片到文件服务器;2.保存图片到文件服务器,返回可访问的网络路径
            $url = request('url', url('form/apply'));
            $contents = $request->get('contents', '');
            if (empty($contents)) return HttpResponseHelper::errorEmpty('生成二维码数据为空');
            $logo = ''; #logo
            switch ($type) {
                case 1:
                    if (empty($logo)) {
                        foreach ($contents as $content) {
                            $gen = QrCode::format('png')->size($size)->generate($content);
                            $data[$content] = 'data:image/png;base64,' . base64_encode($gen);
                        }
                    } else {
                        foreach ($contents as $content) {
                            $gen = QrCode::format('png')->size($size)->merge($logo, .3, true)->generate($content);
                            $data[$content] = 'data:image/png;base64,' . base64_encode($gen);
                        }
                    }
                    break;
                case 2:
                    $qrcode_name = 'qrcodes/' . date('YmdHis_') . str_random(8) . '.png';
                    QrCode::format('png')->size($size)->merge($logo, .3, true)->generate($url, Storage::disk('public')->path($qrcode_name));
                    $data = Storage::disk('public')->url($qrcode_name);
                    break;

                default:
                    $data = '';
                    break;
            }

            return HttpResponseHelper::data($data);
        } catch (\Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage());
        }
    }

    /**
     * 打印标签
     * @param Request $request
     * @return Factory|Application|HttpResponse|View
     */
    final public function printLabel(Request $request)
    {
        try {
            $contents = [];  # 等待生成二维码的数组
            DB::table('print_identity_codes as pic')
                ->select([
                    'ei.identity_code',
                    'ei.category_name',
                    'ei.model_name',
                    'ei.serial_number',
                    'ei.made_at',
                    'pic.account_id',
                    'pic.id',
                    'ei.maintain_station_name',
                    'ei.model_name',
                    'ei.maintain_location_code',
                    'ei.crossroad_number',
                    'ei.last_out_at',
                ])
                ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'pic.entire_instance_identity_code')
                ->where('pic.account_id', session('account.id'))
                ->orderBy('pic.id')
                ->each(function ($entireInstance) use (&$contents) {
                    $contents[] = [
                        'out_time' => empty($entireInstance->last_out_at) ? date('Y-m-d') : date('Y-m-d', strtotime($entireInstance->last_out_at)),
                        'model_name' => $entireInstance->model_name ?? '',
                        'identity_code' => $entireInstance->identity_code ?? '',
                        'maintain_station_name' => $entireInstance->maintain_station_name ?? '',
                        'maintain_location_code' => $entireInstance->maintain_location_code ?? '',
                        'crossroad_number' => $entireInstance->crossroad_number ?? '',
                    ];
                });
            DB::table('print_identity_codes')->where('account_id', session('account.id'))->delete();

            return view('QrCode.printLabel', [
                'contents' => $contents
            ]);
        } catch (\Exception $e) {
            return response()->make("<h1>错误：{$e->getMessage()}</h1>");
        }
    }

    /**
     * 打印仓库二维码
     */
    final public function GetPrintStorehouseQrCode()
    {
        $unique_codes = request("locationUniqueCodes");
        if ($unique_codes) {
            $data = Storehouse::with([])
                ->whereIn("unique_code", $unique_codes)
                ->orderByRaw("field(unique_code, " . implode(",", array_map(function ($value) {
                        return "'$value'";
                    }, $unique_codes)) . ")")
                ->get()
                ->map(function ($datum) {
                    return [
                        "unique_code" => $datum->unique_code,
                        "storehouse_name" => strval($datum) ?? "",
                        "area_name" => "",
                        "platoon_name" => "",
                        "shelf_name" => "",
                        "tier_name" => "",
                        "position_name" => "",
                        "img" => QrCode::format("png")->size(140)->margin(0)->generate($datum->unique_code)
                    ];
                })
                ->all();

            return $this->printQrCodeWithLocationView($data);
        }
        return "<h1>打印数据丢失</h1>";
    }

    /**
     * @param array $contents
     * @return Factory|Application|View|string
     */
    final private function printQrCodeWithLocationView(array $contents)
    {
        switch (request("type")) {
            case 1:
                // 35 x 20
                return view("QrCode.location_35x20", ["contents" => $contents, "type" => request("type"),]);
            case 2:
                // 20 x 12
                return view("QrCode.location_20x12", ["contents" => $contents, "type" => request("type"),]);
            case 3:
                // 40 x 25
                return view("QrCode.location_40x25", ["contents" => $contents, "type" => request("type"),]);
            case 4:
                // 36 x 20 brother
                return view("QrCode.location_36x20_brother", ["contents" => $contents, "type" => request("type"),]);
        }
        return "<h1>标签尺寸错误</h1>";
    }

    /**
     * 打印区二维码
     */
    final public function GetPrintAreaQrCode()
    {
        $unique_codes = request("locationUniqueCodes");
        if ($unique_codes) {
            $data = Area::with(["Storehouse"])
                ->whereIn("unique_code", $unique_codes)
                ->orderByRaw("field(unique_code, " . implode(",", array_map(function ($value) {
                        return "'$value'";
                    }, $unique_codes)) . ")")
                ->get()
                ->map(function ($datum) {
                    return [
                        "unique_code" => $datum->unique_code,
                        "storehouse_name" => strval($datum->Storehouse) ?? "",
                        "area_name" => strval($datum),
                        "platoon_name" => "",
                        "shelf_name" => "",
                        "tier_name" => "",
                        "position_name" => "",
                        "img" => QrCode::format("png")->size(140)->margin(0)->generate($datum->unique_code)
                    ];
                })
                ->all();
            return $this->printQrCodeWithLocationView($data);
        }
        return "<h1>打印数据丢失</h1>";
    }

    /**
     * 打印排二维码
     * @return Factory|Application|View|string
     */
    final public function GetPrintPlatoonQrCode()
    {
        $unique_codes = request("locationUniqueCodes");
        if ($unique_codes) {
            $data = Platoon::with(["Area", "Area.Storehouse"])
                ->whereIn("unique_code", $unique_codes)
                ->orderByRaw("field(unique_code, " . implode(",", array_map(function ($value) {
                        return "'$value'";
                    }, $unique_codes)) . ")")
                ->get()
                ->map(function ($datum) {
                    return [
                        "unique_code" => $datum->unique_code,
                        "storehouse_name" => strval($datum->Area->Storehouse) ?? "",
                        "area_name" => strval($datum->Area),
                        "platoon_name" => strval($datum),
                        "shelf_name" => "",
                        "tier_name" => "",
                        "position_name" => "",
                        "img" => QrCode::format("png")->size(140)->margin(0)->generate($datum->unique_code)
                    ];
                })
                ->all();
            return $this->printQrCodeWithLocationView($data);
        }
        return "<h1>打印数据丢失</h1>";
    }

    /**
     * 打印架二维码
     * @return Factory|Application|View|string
     */
    final public function GetPrintShelfQrCode()
    {
        $unique_codes = request("locationUniqueCodes");
        if ($unique_codes) {
            $data = Shelf::with(["Platoon", "Platoon.Area", "Platoon.Area.Storehouse"])
                ->whereIn("unique_code", $unique_codes)
                ->orderByRaw("field(unique_code, " . implode(",", array_map(function ($value) {
                        return "'$value'";
                    }, $unique_codes)) . ")")
                ->get()
                ->map(function ($datum) {
                    return [
                        "unique_code" => $datum->unique_code,
                        "storehouse_name" => strval($datum->Platoon->Area->Storehouse) ?? "",
                        "area_name" => strval($datum->Platoon->Area),
                        "platoon_name" => strval($datum->Platoon),
                        "shelf_name" => strval($datum),
                        "tier_name" => "",
                        "position_name" => "",
                        "img" => QrCode::format("png")->size(140)->margin(0)->generate($datum->unique_code)
                    ];
                })
                ->all();
            return $this->printQrCodeWithLocationView($data);
        }
        return "<h1>打印数据丢失</h1>";
    }

    /**
     * 打印层二维码
     * @return Factory|Application|View|string
     */
    final public function GetPrintTierQrCode()
    {
        $unique_codes = request("locationUniqueCodes");
        if ($unique_codes) {
            $data = Tier::with(["Shelf", "Shelf.Platoon", "Shelf.Platoon.Area", "Shelf.Platoon.Area.Storehouse"])
                ->whereIn("unique_code", $unique_codes)
                ->orderByRaw("field(unique_code, " . implode(",", array_map(function ($value) {
                        return "'$value'";
                    }, $unique_codes)) . ")")
                ->get()
                ->map(function ($datum) {
                    return [
                        "unique_code" => $datum->unique_code,
                        "storehouse_name" => strval($datum->Shelf->Platoon->Area->Storehouse),
                        "area_name" => strval($datum->Shelf->Platoon->Area),
                        "platoon_name" => strval($datum->Shelf->Platoon),
                        "shelf_name" => strval($datum->Shelf),
                        "tier_name" => strval($datum),
                        "position_name" => "",
                        "img" => QrCode::format("png")->size(140)->margin(0)->generate($datum->unique_code)
                    ];
                })
                ->all();
            return $this->printQrCodeWithLocationView($data);
        }
        return "<h1>打印数据丢失</h1>";
    }

    /**
     * 打印标签-仓库位置
     * @param Request $request
     * @return Factory|Application|HttpResponse|View|mixed|string
     */
    final public function printQrCodeWithLocation(Request $request)
    {
        try {
            $locationUniqueCodes = $request->get('locationUniqueCodes', '');
            $type = $request->get('type', '');
            $size = 140;
            $locations = Position::with([
                'WithTier',
                'WithTier.WithShelf',
                'WithTier.WithShelf.WithPlatoon',
                'WithTier.WithShelf.WithPlatoon.WithArea',
                'WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse',
            ])
                ->whereIn('unique_code', $locationUniqueCodes)
                ->get();
            $contents = [];
            foreach ($locations as $location) {
                $contents[] = [
                    'unique_code' => $location->unique_code,
                    'storehouse_name' => strval($location->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse),
                    'area_name' => strval($location->WithTier->WithShelf->WithPlatoon->WithArea),
                    'platoon_name' => strval($location->WithTier->WithShelf->WithPlatoon),
                    'shelf_name' => strval($location->WithTier->WithShelf),
                    'tier_name' => strval($location->WithTier),
                    'position_name' => strval($location),
                    'img' => QrCode::format('png')->size($size)->margin(0)->generate($location->unique_code)
                ];
            }

            return $this->printQrCodeWithLocationView($contents);
        } catch (Exception $e) {
            return "<h1>错误：{$e->getMessage()}</h1><h2>{$e->getFile()}</h2><h3>{$e->getLine()}</h3>";
        }
    }

    /**
     * 上道位置打印标签
     * @param Request $request
     * @return Factory|RedirectResponse|View
     */
    final public function qrcodeWithInstallLocation(Request $request)
    {
        try {
            $locationUniqueCode = $request->get('locationUniqueCodes', '');
            $type = $request->get('type', '');
            $size = 140;
            $locations = InstallTier::with(['WithInstallShelf'])->whereIn('unique_code', explode(',', $locationUniqueCode))->get();
            $contents = [];
            foreach ($locations as $location) {
                $contents[] = [
                    'unique_code' => $location->unique_code,
                    'station_name' => $location->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->name ?? '',
                    'room_name' => $location->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->type->text ?? '',
                    'platoon_name' => $location->WithInstallShelf->WithInstallPlatoon->name ?? '',
                    'shelf_name' => $location->WithInstallShelf->name,
                    'tier_name' => $location->name,
                    'img' => QrCode::format('png')->size($size)->margin(0)->generate($location->unique_code),
                ];
            }

            switch ($type) {
                case 1:
                    // 35 x 20
                    return view('QrCode.installLocation_35x20', ['contents' => $contents, 'type' => $type,]);
                case 2:
                    // 20 x 12
                    return view('QrCode.installLocation_20x12', ['contents' => $contents, 'type' => $type,]);
                case 3:
                    // 40 x 25
                    return view('QrCode.installLocation_40x25', ['contents' => $contents, 'type' => $type,]);
                case 4:
                    // 36 x 20
                    return view('QrCode.installLocation_36x20_brother', ['contents' => $contents, 'type' => $type,]);
            }
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * 打印老位置和新编号
     * @return Factory|Application|RedirectResponse|View
     */
    final public function getPrintOldLocationAndNewEntireInstance()
    {
        try {
            $size_types = [
                1 => 160,
                2 => 85,
                3 => 140,
            ];
            $size_type = $size_types[request('size_type')] ?? 140;  // 二维码标签大小

            $do_b050 = function () use ($size_type) {
                $contents = [];  // 等待生成二维码的数组
                DB::table('print_new_location_and_old_entire_instances as pnlaoei')
                    ->select([
                        'ei.identity_code',
                        'ei.category_name',
                        'ei.model_name',
                        'ei.entire_model_unique_code',
                        'ei.serial_number',
                        'ei.made_at',
                        'pnlaoei.account_id',
                        'pnlaoei.id',
                        'em.fix_cycle_value',
                        'ei.life_year',
                        'ei.maintain_station_name',
                        'ei.maintain_location_code',
                        'ei.last_out_at',
                    ])
                    ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'pnlaoei.entire_instance_identity_code')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
                    ->where('pnlaoei.account_id', session('account.id'))
                    ->orderBy('pnlaoei.id')
                    ->each(function ($entireInstance) use (&$contents, $size_type) {
                        $first = substr($entireInstance->identity_code, 0, 1);
                        $entire_model_unique_code = $entireInstance->entire_model_unique_code;
                        if ($first == 'Q') $entire_model_unique_code = substr($entire_model_unique_code, 0, 5);
                        $entire_model_name = DB::table('entire_models')
                            ->select(['name'])
                            ->where('deleted_at', null)
                            ->where('unique_code', $entire_model_unique_code)
                            ->where('is_sub_model', false)
                            ->first()
                            ->name;

                        $contents[] = [
                            'maintain_station_name' => $entireInstance->old_maintain_workshop_name . $entireInstance->old_maintain_station_name,
                            'maintain_location_code' =>
                                (InstallPosition::getRealName(@$entireInstance->old_maintain_location_code ?? '') ?: (@$entireInstance->old_maintain_location_code ?? ''))
                                . ((@$entireInstance->old_crossroad_number ?? '') . (@$entireInstance->old_open_direction)),
                            'category_name' => $entireInstance->category_name,
                            'entire_model_name' => $entire_model_name,
                            'model_name' => $entireInstance->model_name,
                            'identity_code' => $entireInstance->identity_code,
                            'serial_number' => $entireInstance->serial_number,
                            'made_at' => $entireInstance->made_at ? date('Y-m-d', strtotime($entireInstance->made_at)) : '',
                            'life_year' => $entireInstance->life_year ?? 0,
                            'img' => QrCode::format('png')->size($size_type)->margin(0)->generate($entireInstance->identity_code),
                            'last_out_at' => $entireInstance->last_out_at,
                        ];
                    });
                // DB::table('print_new_location_and_old_entire_instances')->where('account_id', session('account.id'))->delete();
                return $contents;
            };

            $do_default = function () use ($size_type) {
                $contents = [];  // 等待生成二维码的数组
                DB::table('print_new_location_and_old_entire_instances as pnlaoei')
                    ->select([
                        'pnlaoei.old_maintain_workshop_name',
                        'pnlaoei.old_maintain_station_name',
                        'pnlaoei.old_maintain_location_code',
                        'pnlaoei.old_crossroad_number',
                        'pnlaoei.old_open_direction',
                        'ei.identity_code',
                        'ei.category_name',
                        'ei.model_name',
                        'ei.serial_number',
                        'ei.made_at',
                        'pnlaoei.account_id',
                        'pnlaoei.id',
                        'ei.last_out_at',
                    ])
                    ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'pnlaoei.new_entire_instance_identity_code')
                    ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
                    ->where('pnlaoei.account_id', session('account.id'))
                    ->orderBy('pnlaoei.id')
                    ->each(function ($entireInstance) use (&$contents, $size_type) {
                        $contents[] = [
                            'maintain_station_name' => $entireInstance->old_maintain_workshop_name . $entireInstance->old_maintain_station_name,
                            'maintain_location_code' =>
                                (InstallPosition::getRealName(@$entireInstance->old_maintain_location_code ?? '') ?: (@$entireInstance->old_maintain_location_code ?? ''))
                                . ((@$entireInstance->old_crossroad_number ?? '') . (@$entireInstance->old_open_direction)),
                            'category_name' => $entireInstance->category_name,
                            'model_name' => $entireInstance->model_name,
                            'identity_code' => $entireInstance->identity_code,
                            'serial_number' => $entireInstance->serial_number,
                            'made_at' => $entireInstance->made_at ? date('Y-m-d', strtotime($entireInstance->made_at)) : '',
                            'img' => QrCode::format('png')->size($size_type)->margin(0)->generate($entireInstance->identity_code),
                            'last_out_at' => $entireInstance->last_out_at,
                        ];
                    });
                // DB::table('print_new_location_and_old_entire_instances')->where('account_id', session('account.id'))->delete();
                return $contents;
            };

            switch (env('ORGANIZATION_CODE')) {
                case 'B050':
                    $contents = $do_b050();
                    break;
                default:
                    $contents = $do_default();
                    break;
            }

            if (DB::table('print_new_location_and_old_entire_instances')->where('id', '>', 0)->doesntExist()) {
                DB::table('print_new_location_and_old_entire_instances')->truncate();
            }

            return view('QrCode.printQrCodeAndLocation', [
                'contents' => $contents
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            // return '<h1>意外错误</h1>';
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }
}
