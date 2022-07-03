<?php

namespace App\Http\Controllers\Entire;

use App\Facades\CodeFacade;
use App\Facades\CommonFacade;
use App\Facades\EntireInstanceFacade;
use App\Facades\ExcelTaggingFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\KindsFacade;
use App\Facades\ModelBuilderFacade;
use App\Facades\OrganizationFacade;
use App\Facades\TextFacade;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireInstanceExcelTaggingIdentityCode;
use App\Model\EntireInstanceExcelTaggingReport;
use App\Model\EntireInstanceLog;
use App\Model\EntireModel;
use App\Model\Factory;
use App\Model\Line;
use App\Model\Maintain;
use App\Model\PartModel;
use App\Model\WorkArea;
use App\Services\ExcelCellService;
use App\Services\ExcelRowService;
use App\Services\ExcelWriterService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PHPExcel_Exception;
use PHPExcel_Reader_Exception;
use PHPExcel_Writer_Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class TaggingController extends Controller
{

    /**
     * 整件赋码页面
     * @return \Illuminate\Contracts\View\Factory|Application|RedirectResponse|Redirector|View
     */
    final public function create()
    {
        if (!session('account.work_area_unique_code')) return redirect('/')->with('danger', '当前用户没有工区');

        // 根据工区获取种类
        $categories = KindsFacade::getCategories([], function ($db) {
            return $db->where("is_show", true);
        });
        $entire_models = KindsFacade::getEntireModelsByCategory();
        $models = KindsFacade::getModelsByEntireModel();

        $factories = DB::table('factories')->select(['unique_code', 'name',])->whereNull('deleted_at')->get();
        $statuses = collect(EntireInstance::$STATUSES);
        $scene_workshops = OrganizationFacade::getSceneWorkshops([], function ($db) {
            return $db->where("sc.is_show", true);
        });
        $lines = OrganizationFacade::getLines([], function ($db) {
            return $db->where("is_show", true);
        });
        $stations = OrganizationFacade::getStationsBySceneWorkshop([], function ($db) {
            return $db->where("s.is_show", true);
        });
        $work_areas = WorkArea::with([])->get();
        $part_categories = DB::table('part_categories as pc')->whereNull('pc.deleted_at')->get()->groupBy(['category_unique_code']);

        return view("Entire.Tagging.create", [
            "categories_as_json" => $categories,
            "entire_models_as_json" => $entire_models,
            "models_as_json" => $models,
            "factories_as_json" => $factories,
            "statuses_as_json" => $statuses,
            "scene_workshops_as_json" => $scene_workshops,
            "lines_as_json" => $lines,
            "stations_as_json" => $stations,
            "work_areas" => $work_areas,
            "part_categories_as_json" => $part_categories,
        ]);
    }

    /**
     * 批量赋码（页面）
     * @param Request $request
     * @return mixed
     */
    final public function store(Request $request)
    {
        try {
            if (!session('account.work_area_unique_code')) return back()->with('danger', '当前用户没有所属工区');
            $GorS = substr($request->get('category_unique_code'), 0, 1);
            $work_area_type = intval(substr(session('account.work_area_unique_code'), 5));
            $statuses = collect(EntireInstance::$STATUSES);

            // 表单验证
            if (!$request->get('category_unique_code')) return back()->with('danger', '种类不能为空');
            $category = Category::with([])->where('unique_code', $request->get('category_unique_code'))->first();
            if (!$category) return back()->with('danger', '种类参数错误');
            if (!$request->get('entire_model_unique_code')) return back()->with('danger', '类型不能为空');
            $entire_model = EntireModel::with([])->where('unique_code', $request->get('entire_model_unique_code'))->first();
            if (!$entire_model) return back()->with('danger', '类型参数错误');
            if ($GorS === 'Q') {
                if (!$request->get('model_unique_code')) return back()->with('danger', '型号不能为空');
                $model = EntireModel::with([])->where('unique_code', $request->get('model_unique_code'))->first();
                if (!$model) return back()->with('danger', '型号参数错误');
            } else {
                $model = null;
            }
            if ($request->get('number') <= 0) return back()->with('danger', '数量必须是大于0的正整数');
            $made_at = null;
            if ($request->get('made_at')) {
                try {
                    $made_at = Carbon::parse($request->get('made_at'));
                } catch (Exception $e) {
                    return back()->with('danger', '生产日期格式不正确');
                }
            }
            $last_out_at = null;
            if ($request->get('last_out_at')) {
                try {
                    $last_out_at = Carbon::parse($request->get('last_out_at'));
                } catch (Exception $e) {
                    return back()->with('danger', '出所日期格式不正确');
                }
            }
            $installed_at = null;
            if ($request->get('last_installed_at')) {
                try {
                    $installed_at = Carbon::parse($request->get('last_installed_at'));
                } catch (Exception $e) {
                    return back()->with('danger', '上道日期格式不正确');
                }
            }
            $factory_name = '';
            if ($request->get('factory_unique_code')) {
                $factory = Factory::with([])->where('unique_code', $request->get('factory_unique_code'))->first();
                if (!$factory) return back()->with('danger', '厂家参数错误');
                $factory_name = $factory->name;
            }
            $scene_workshop_name = '';
            $station_name = '';
            if ($request->get('station_unique_code')) {
                $station = Maintain::with(['Parent'])->where('unique_code', $request->get('station_unique_code'))->first();
                if (!$station) return back()->with('danger', '车站参数错误');
                if (!$station->Parent) return back()->with('danger', "车站数据有误，没有找到：{$station->name}的所属车间");
                $station_name = $station->name;
                $scene_workshop_name = $station->Parent->name;
            }
            $line_unique_code = '';
            if ($request->get('line_unique_code')) {
                $line = Line::with([])->where('unique_code', $request->get('line_unique_code'))->first();
                if (!$line) return back()->with('danger', '线别不存在');
                $line_unique_code = $line->unique_code;
            }
            if (!$request->get('status')) {
                return back()->with('danger', '状态不能为空');
            }
            if (!($statuses->get($request->get('status', '') ?? '') ?? '')) {
                return back()->with('danger', '状态参数错误');
            }
            $is_part = $request->get('is_part', false) ?? false;
            $part_model = null;
            if ($is_part) {
                $part_model = PartModel::with([])->where('name', $model->name ?? '')->first();
                if (!$part_model) return back()->with('danger', '没有找到对应的部件型号');
                if (!$part_model->part_category_id) return back()->with('danger', '部件型号数据错误，没有找到对应的部件种类');
            }

            // 批量赋码
            $new_identity_codes = [];
            $current_model_unique_code = '';
            switch ($GorS) {
                case 'S':
                    $new_identity_codes = CodeFacade::makeEntireInstanceIdentityCodes($request->get('entire_model_unique_code'), intval($request->get('number')));
                    $current_model_unique_code = $request->get('entire_model_unique_code');
                    break;
                case 'Q':
                    $new_identity_codes = CodeFacade::makeEntireInstanceIdentityCodes($request->get('model_unique_code'), intval($request->get('number')));
                    $current_model_unique_code = $request->get('model_unique_code');
                    break;
                default:
                    return back()->with('danger', '编码参数错误');
            }

            DB::beginTransaction();
            // 生成赋码单
            $entire_instance_excel_tagging_report = EntireInstanceExcelTaggingReport::with([])
                ->create([
                    'serial_number' => EntireInstanceExcelTaggingReport::generateSerialNumber(),
                    'is_upload_create_device_excel_error' => false,
                    'work_area_type' => session('account.work_area_by_unique_code.type'),
                    'processor_id' => session('account.id'),
                    'work_area_unique_code' => session('account.work_area_by_unique_code.unique_code'),
                    'correct_count' => $request->get('number'),
                    'fail_count' => 0,
                ]);

            // 设置参数
            $new_entire_instances = collect([]);
            $new_entire_instance_logs = collect([]);
            $entire_instance_excel_tagging_identity_codes = collect([]);
            if (!empty($new_identity_codes)) {
                foreach ($new_identity_codes as $new_identity_code) {
                    $next = null;
                    if ($last_out_at) {
                        switch ($GorS) {
                            case 'Q':
                                if ($model) {
                                    if ($model->fix_cycle_value > 0) {
                                        $next = Carbon::parse($last_out_at)->addYears($model->fix_cycle_value);
                                    }
                                } else {
                                    if ($entire_model->fix_cycle_value > 0) {
                                        $next = Carbon::parse($last_out_at)->addYears($entire_model->fix_cycle_value);
                                    }
                                }
                                break;
                            case 'S':
                                if ($entire_model->fix_cycle_value > 0) {
                                    $next = Carbon::parse($last_out_at)->addYears($entire_model->fix_cycle_value);
                                }
                                break;
                        }
                    }

                    $scraping_at = null;
                    if ($made_at) {
                        switch ($GorS) {
                            case 'Q':
                                if ($model) {
                                    if ($model->life_year) {
                                        $scraping_at = Carbon::parse($made_at)->addYears($model->life_year);
                                    }
                                } else {
                                    if ($entire_model->life_year) {
                                        $scraping_at = Carbon::parse($made_at)->addYears($entire_model->life_year);
                                    }
                                }
                                break;
                            case 'S':
                                if ($entire_model->life_year) {
                                    $scraping_at = Carbon::parse($made_at)->addYears($entire_model->life_year);
                                }
                                break;
                        }
                    }

                    $new_entire_instance = [
                        'created_at' => now(),
                        'updated_at' => now(),
                        'identity_code' => $new_identity_code,
                        'category_unique_code' => $request->get('category_unique_code'),
                        'category_name' => $category->name,
                        'entire_model_unique_code' => $GorS == 'S' ? $request->get('entire_model_unique_code') : $request->get('model_unique_code'),
                        'model_unique_code' => $GorS == 'S' ? $request->get('entire_model_unique_code') : $request->get('model_unique_code'),
                        'model_name' => $GorS == 'S' ? $entire_model->name : $model->name,
                        'made_at' => $made_at,
                        'scarping_at' => $scraping_at ? $scraping_at->format('Y-m-d') : null,
                        'last_out_at' => $last_out_at,
                        'installed_at' => $installed_at,
                        'factory_device_code' => $request->get("factory_device_code") ?: "",
                        'factory_name' => $factory_name,
                        'maintain_workshop_name' => $scene_workshop_name,
                        'maintain_station_name' => $station_name,
                        'line_unique_code' => $line_unique_code ?? '',
                        'next_fixing_time' => @$next ? $next->timestamp : 0,
                        'next_fixing_month' => @$next ? $next->startOfMonth()->toDateString() : null,
                        'next_fixing_day' => @$next ? $next->toDateTimeString() : null,
                        'status' => $request->get('status'),
                        'note' => $request->get('note', '') ?? '',
                        'work_area_unique_code' => session('account.work_area_unique_code'),
                        'source_type' => $request->get('source_type', '') ?? '',
                        'source_name' => $request->get('source_name', '') ?? '',
                        'is_part' => $request->get('is_part', false) ?? false,
                        'part_model_unique_code' => $part_model->unique_code ?? '',
                        'part_category_id' => $part_model->part_category_id ?? 0,
                    ];

                    $new_entire_instances->push($new_entire_instance);

                    $new_entire_instance_logs->push([
                        'created_at' => now(),
                        'updated_at' => now(),
                        'name' => '赋码',
                        'description' => implode('；', array_filter([
                            $new_entire_instance['made_at'] ? "出厂日期：" . Carbon::parse($new_entire_instance['made_at'])->toDateString() : "",
                            ($new_entire_instance["made_at"] && $new_entire_instance['scarping_at']) ? "到期日期：" . Carbon::parse($new_entire_instance['made_at'])->toDateString() : "",
                            "操作人：" . session("account.nickname"),
                        ], function ($value) {
                            return !empty($value);
                        })),
                        'entire_instance_identity_code' => $new_entire_instance['identity_code'],
                        'type' => 0,
                        'url' => '',
                        'material_type' => 'ENTIRE',
                        'operator_id' => session('account.id'),
                        'station_unique_code' => '',
                    ]);

                    $entire_instance_excel_tagging_identity_codes->push([
                        'created_at' => now(),
                        'updated_at' => now(),
                        'entire_instance_excel_tagging_report_sn' => $entire_instance_excel_tagging_report->serial_number,
                        'entire_instance_identity_code' => $new_identity_code,
                    ]);
                }

                // 保存到数据库
                DB::table('entire_instances')->insert($new_entire_instances->toArray());  // 保存设备器材
                DB::table('entire_instance_logs')->insert($new_entire_instance_logs->toArray());  // 保存设备器材赋码日志
                $current_entire_instance_count = DB::table('entire_instance_counts as cic')->where('entire_model_unique_code', $current_model_unique_code)->first();
                if ($current_entire_instance_count) {
                    $new_count = $current_entire_instance_count->count + intval($request->get('number'));
                    DB::table('entire_instance_counts')->where('entire_model_unique_code', $current_model_unique_code)->update(['updated_at' => now(), 'count' => $new_count]);
                } else {
                    $new_count = intval($request->get('number'));
                    DB::table('entire_instance_counts')->insert([
                        'created_at' => now(),
                        'updated_at' => now(),
                        'entire_model_unique_code' => $current_model_unique_code,
                        'count' => $new_count,
                    ]);
                }
                DB::table('entire_instance_excel_tagging_identity_codes')->insert($entire_instance_excel_tagging_identity_codes->toArray());
                DB::commit();

                return redirect("/entire/tagging/{$entire_instance_excel_tagging_report->serial_number}/uploadCreateDeviceReport?" . http_build_query([
                        'page' => $request->get('page'),
                        'type' => $request->get('type'),
                    ]));
                // ->with('success', '设备赋码：' . $request->get('number') . '条。');
            } else {
                return back()->with('danger', '没有找赋码的设备器材');
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 下载批量赋码模板Excel
     * @return RedirectResponse
     */
    final public function getDownloadUploadCreateDeviceExcelTemplate(): RedirectResponse
    {
        $work_area = WorkArea::with([])->where('unique_code', request('work_area_unique_code'))->first();
        if (!$work_area) return back()->with('danger', '工区参数错误');

        ExcelTaggingFacade::DownloadTemplate($work_area);
        // return EntireInstanceFacade::downloadUploadCreateDeviceExcelTemplate($work_area);
    }

    /**
     * excel 批量赋码页面
     * @return \Illuminate\Contracts\View\Factory|RedirectResponse|View
     */
    final public function getUploadCreateDevice()
    {
        try {
            $work_areas = WorkArea::with([])->get();

            return view('Entire.Tagging.uploadCreateDevice', [
                'work_areas' => $work_areas,
            ]);
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 上传设备赋码Excel
     * @param Request $request
     * @return RedirectResponse
     * @throws Throwable
     */
    final public function postUploadCreateDevice(Request $request): RedirectResponse
    {
        $workshop = Maintain::with([])->where("unique_code", env("CURRENT_WORKSHOP_UNIQUE_CODE"))->first();
        if (!$workshop) return back()->with("danger", "配置错误：没有找到专业车间");

        if (!$request->hasFile('file')) return back()->with('danger', '上传文件失败');
        if (!in_array($request->file('file')->getClientMimeType(), [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream'
        ])) return back()->withInput()->with('danger', '只能上传excel，当前格式：' . $request->file('file')->getClientMimeType());

        $work_area = WorkArea::with([])->where('unique_code', $request->get('work_area_unique_code'))->first();
        if (!$work_area) return JsonResponseFacade::errorValidate('工区参数错误');

        // 保存原文件
        $original_filename = $request->file('file')->getClientOriginalName();
        $original_extension = $request->file('file')->getClientOriginalExtension();
        $filename = $request->file('file')->storeAs("/public/uploadCreateExcel", "【赋码原始表】" . now()->format("Y年m月d日 H时i分s秒") . "-" . session("account.nickname") . ".$original_extension");

        // 新赋码
        if (env("USE_NEW_EXCEL_TAGGING")) {
            // 创建赋码记录
            $entire_instance_excel_tagging_report = EntireInstanceExcelTaggingReport::with([])
                ->create([
                    "serial_number" => $entire_instance_excel_tagging_report_sn = EntireInstanceExcelTaggingReport::generateSerialNumber(),
                    "work_area_type" => $work_area->type,
                    "processor_id" => session("account.id"),
                    "work_area_unique_code" => $work_area->unique_code,
                    "filename" => $filename,
                    "original_filename" => $original_filename,
                    "is_upload_create_device_excel_error" => false,
                ]);

            // 读取excel
            $excel_collection = ExcelTaggingFacade::ReadExcel($request, "file", $work_area);
            if ($excel_collection->isEmpty()) return JsonResponseFacade::errorForbidden("Excel中没有数据");
            // 检查错误
            $excel_check = ExcelTaggingFacade::CheckData($excel_collection, $work_area);
            // 开始赋码
            if ($excel_check->HasError()) {
                // 保存错误Excel
                $this->handleTaggingError(
                    $work_area,
                    $entire_instance_excel_tagging_report
                );
            }

            if ($excel_check->GetCorrectData()->isNotEmpty()) {
                ["new_entire_instances" => $new_entire_instances, "new_entire_instance_logs" => $new_entire_instance_logs] = $excel_check->Tagging($work_area, $workshop);
                $entire_instance_excel_tagging_identity_codes = [];  // 赋码器材记录
                collect($new_entire_instances)
                    ->each(function ($new_entire_instance) use ($entire_instance_excel_tagging_report, &$entire_instance_excel_tagging_identity_codes) {
                        $entire_instance_excel_tagging_identity_codes[] = [
                            "created_at" => $entire_instance_excel_tagging_report->created_at,
                            "updated_at" => $entire_instance_excel_tagging_report->updated_at,
                            "entire_instance_excel_tagging_report_sn" => $entire_instance_excel_tagging_report->serial_number,
                            "entire_instance_identity_code" => $new_entire_instance["identity_code"],
                        ];
                    });
                DB::table("entire_instance_excel_tagging_identity_codes")->insert($entire_instance_excel_tagging_identity_codes);
            }

            $entire_instance_excel_tagging_report->correct_count = $excel_check->GetCorrectCount() ?: 0;
            $entire_instance_excel_tagging_report->fail_count = $excel_check->GetErrorCount() ?: 0;
            $entire_instance_excel_tagging_report->saveOrFail();

            return redirect("/entire/tagging/{$entire_instance_excel_tagging_report_sn}/uploadCreateDeviceReport?" . http_build_query([
                    'page' => $request->get('page'),
                    'type' => $request->get('type'),
                ]));
        } else {
            // 旧赋码
            return EntireInstanceFacade::uploadCreateDevice(
                $request,
                $request->get('is_part') == 1 ? 'is_part' : $work_area->type,
                $work_area->unique_code,
                $filename,
                $original_filename
            );
        }
    }

    /**
     * 处理Excel赋码错误数据 生成错误Excel
     * @param WorkArea $work_area
     * @param EntireInstanceExcelTaggingReport $entire_instance_excel_tagging_report
     * @param int $error_count
     * @throws Throwable
     */
    private function handleTaggingError(WorkArea $work_area, EntireInstanceExcelTaggingReport $entire_instance_excel_tagging_report): void
    {
        $error_excel = ExcelTaggingFacade::GenerateErrorExcel($work_area);
        $error_excel_filename = "【赋码错误表】" . now()->format("Y年m月d日 H时i分s秒") . "-" . session("account.nickname");
        $error_excel->save(storage_path("app/public/uploadCreateExcel/$error_excel_filename"));
        $entire_instance_excel_tagging_report->upload_create_device_excel_error_filename = "uploadCreateExcel/$error_excel_filename.xls";
        $entire_instance_excel_tagging_report->saveOrFail();
    }

    /**
     * 上传设备赋码Excel报告
     * @param string $serial_number
     * @return \Illuminate\Contracts\View\Factory|RedirectResponse|View
     */
    final public function getUploadCreateDeviceReport(string $serial_number)
    {
        try {
            // 获取基础数据
            $factories = Factory::with([])->get();
            $scene_workshops = DB::table('maintains as sc')->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))->where('sc.type', 'SCENE_WORKSHOP')->get();
            $stations = DB::table('maintains as s')->where('s.type', 'STATION')->get()->groupBy('parent_unique_code');

            // 获取赋码上传记录
            $entire_instance_excel_tagging_report = EntireInstanceExcelTaggingReport::with([])->where('serial_number', $serial_number)->first();

            // 获取本次入所单的设备
            $entire_instances = EntireInstanceExcelTaggingIdentityCode::with([
                'EntireInstance' => function ($EntireInstance) {
                    $EntireInstance->withoutGlobalScopes();
                },
                'EntireInstance.SubModel',
                'EntireInstance.PartModel',
                'EntireInstance.Factory',
                'EntireInstance.InstallPosition',
            ])
                ->where('entire_instance_excel_tagging_report_sn', $serial_number)
                ->paginate(200);

            // 计算当前页容量
            $total = $entire_instances->total();
            $current_page = $entire_instances->currentPage();
            $pre_page = $entire_instances->perPage();
            $last_page = $entire_instances->lastPage();
            if ($last_page === 1) {
                $current_total = $total;
            } else {
                if ($last_page === $current_page) {
                    $page = $last_page - 1;
                    $current_total = $total - $page * $pre_page;
                } else {
                    $current_total = $pre_page;
                }
            }

            return view('Entire.Tagging.uploadCreateDeviceReport', [
                'entireInstanceExcelTaggingReport' => $entire_instance_excel_tagging_report,
                'entireInstances' => $entire_instances,
                'factories_as_json' => $factories->toJson(),
                'scene_workshops_as_json' => $scene_workshops->toJson(),
                'stations_as_json' => $stations->toJson(),
                'current_total' => $current_total,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 下载设备赋码Excel错误报告
     * @param string $serial_number
     * @return RedirectResponse|BinaryFileResponse
     */
    final public function getDownloadCreateDeviceErrorExcel(string $serial_number)
    {
        try {
            EntireInstanceExcelTaggingReport::with([])->where('serial_number', $serial_number)->firstOrFail();

            $filename = storage_path(request('path'));
            if (!file_exists($filename)) return back()->with('danger', '文件不存在');

            return response()->download($filename, '上传设备赋码错误报告.xls');
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 设备器材赋码 记录列表
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|Application|RedirectResponse|View
     */
    final public function getReport(int $id = 0)
    {
        try {
            // 当前时间
            list($origin_at, $finish_at) = explode('~', request('created_at') ?? join('~', [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')]));

            // 操作人列表
            $accounts = Account::with([])->where('work_area_unique_code', session('account.work_area_unique_code'))->get();

            // 赋码记录表
            $entire_instance_excel_tagging_reports = ModelBuilderFacade::init(
                request(),
                EntireInstanceExcelTaggingReport::with([]),
                ['created_at']
            )
                ->extension(
                    function ($entire_instance_excel_tagging_report) use ($origin_at, $finish_at) {
                        return $entire_instance_excel_tagging_report
                            ->where('work_area_unique_code', session('account.work_area_unique_code'))
                            ->whereBetween('created_at', [Carbon::parse($origin_at)->startOfDay()->format('Y-m-d H:i:s'), Carbon::parse($finish_at)->endOfDay()->format('Y-m-d H:i:s')])
                            ->orderByDesc('created_at');
                    }
                )
                ->all();

            $create_device_error_dir = 'entireInstanceTagging/upload/errorExcels/createDevice';

            return view('Entire.Tagging.report', [
                'entire_instance_excel_tagging_reports' => $entire_instance_excel_tagging_reports,
                'create_device_error_dir' => $create_device_error_dir,
                'processors' => $accounts,
                'origin_at' => $origin_at,
                'finish_at' => $finish_at,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 设备器材赋码 详情
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|Application|RedirectResponse|View
     */
    final public function getReportShow(int $id)
    {
        try {
            $entire_instance_excel_tagging_report = EntireInstanceExcelTaggingReport::with([
                'EntireInstanceIdentityCodes',
                'EntireInstanceIdentityCodes.EntireInstance',
                'EntireInstanceIdentityCodes.EntireInstance.SubModel',
                'EntireInstanceIdentityCodes.EntireInstance.PartModel',
            ])
                ->where('id', $id)
                ->firstOrFail();

            return view('Entire.Tagging.reportShow', [
                'entire_instance_excel_tagging_report' => $entire_instance_excel_tagging_report,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 设备器材赋码 回退
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    final public function postRollback(Request $request, int $id): JsonResponse
    {
        $entire_instance_excel_tagging_report = EntireInstanceExcelTaggingReport::with(["EntireInstanceIdentityCodes"])->where('id', $id)->firstOrFail();
        EntireInstance::with([])->whereIn("identity_code", $entire_instance_excel_tagging_report->EntireInstanceIdentityCodes->pluck("entire_instance_identity_code")->toArray())->update(["deleted_at" => now(), "deleter_id" => session("account.id"),]);
        $entire_instance_excel_tagging_report->update(["is_rollback" => true, "rollback_processed_at" => now(), "rollback_processor_id" => session("account.id"),]);

        $entire_instance_logs = [];
        $entire_instance_excel_tagging_report
            ->EntireInstanceIdentityCodes
            ->each(function ($datum) use (&$entire_instance_logs) {
                $entire_instance_logs[] = [
                    "created_at" => now(),
                    "updated_at" => now(),
                    "name" => "赋码回退",
                    "description" => TextFacade::joinWithNotEmpty(" ", [
                        "由",
                        session("account.nickname"),
                        "于",
                        now()->format("Y-m-d H:i:s"),
                        "执行赋码回退",
                    ]),
                    "entire_instance_identity_code" => $datum->entire_instance_identity_code,
                    "type" => "fa-envelope-o",
                    "url" => "/entire/tagging/$datum->entire_instance_excel_tagging_report_sn",
                    "operator_id" => session("account.id"),
                ];
            });
        if (!empty($entire_instance_logs)) {
            EntireInstanceLog::with([])->insert($entire_instance_logs);
        }

        // $identity_codes = EntireInstanceExcelTaggingIdentityCode::with([])->where('entire_instance_excel_tagging_report_sn', $entire_instance_excel_tagging_report->serial_number)->pluck('entire_instance_identity_code')->toArray();  // 获取赋码设备器材唯一编号组
        // $part_identity_codes = EntireInstance::with([])->select(['identity_code'])->whereIn('entire_instance_identity_code', $identity_codes)->pluck('identity_code')->toArray();
        //
        // $entire_instance_excel_tagging_report->forceDelete();  // 删除设备器材赋码记录单
        // EntireInstanceExcelTaggingIdentityCode::with([])->where('entire_instance_excel_tagging_report_sn', $entire_instance_excel_tagging_report->serial_number)->forceDelete();  // 删除设备器材赋码唯一编号记录
        // EntireInstance::with([])->whereIn('identity_code', $identity_codes)->forceDelete();  // 删除设备器材
        // EntireInstance::with([])->whereIn('identity_code', $part_identity_codes)->forceDelete(); // 删除设备器材（部件）
        // EntireInstanceLog::with([])->whereIn('entire_instance_identity_code', $identity_codes)->forceDelete();  // 删除设备器材日志
        // EntireInstanceLog::with([])->whereIn('entire_instance_identity_code', $part_identity_codes)->forceDelete();  // 删除设备器材日志（部件）
        //
        // $fix_workflow_serial_numbers = FixWorkflow::with([])->whereIn('entire_instance_identity_code', $identity_codes)->pluck('serial_number')->toArray();  // 获取检修单编号组
        // $fix_workflow_process_serial_numbers = FixWorkflowProcess::with([])->whereIn('fix_workflow_serial_number', $fix_workflow_serial_numbers)->pluck('serial_number')->toArray();  // 获取检修过程单编号组
        // FixWorkflowRecord::with([])->whereIn('fix_workflow_process_serial_number', $fix_workflow_process_serial_numbers)->forceDelete();  // 删除检修记录值
        // FixWorkflowProcess::with([])->whereIn('fix_workflow_serial_number', $fix_workflow_serial_numbers)->forceDelete();  // 删除检测过程单
        // FixWorkflow::with([])->whereIn('entire_instance_identity_code', $identity_codes)->forceDelete();  // 删除检修单
        //
        // WarehouseReportEntireInstance::with([])->whereIn('entire_instance_identity_code', $identity_codes)->forceDelete(); // 删除出入所设备
        //
        // EntireInstanceLock::with([])->whereIn('entire_instance_identity_code', $identity_codes)->forceDelete();  // 删除设备锁
        // EntireInstanceLock::with([])->whereIn('entire_instance_identity_code', $part_identity_codes)->forceDelete();  // 删除设备锁（部件）
        //
        // $entire_instance_excel_tagging_report->forceDelete();  // 删除赋码批次单
        //
        // $warehouse_entire_instances = DB::table('warehouse_materials')->whereIn('material_unique_code', $identity_codes)->get();
        // DB::table('warehouses')->whereIn('unique_code', $warehouse_entire_instances->pluck('warehouse_unique_code')->toArray())->delete();  // 报废单
        // DB::table('warehouse_materials')->whereIn('material_unique_code', $identity_codes)->delete();  // 报废单详情

        return JsonResponseFacade::deleted([], '回退成功');
    }

    /**
     * 下载室外位置采集单
     * @param string $serial_number
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     * @throws PHPExcel_Exception
     */
    final public function getDownloadOutDeviceCollateExcel(string $serial_number): void
    {
        $excel = ExcelWriterService::Init();
        $sheet = $excel->GetSheet();

        ExcelRowService::Init()
            ->SetRow(1)
            ->SetExcelCells([
                ExcelCellService::Init("序号"),
                ExcelCellService::Init("唯一编号"),
                ExcelCellService::Init("种类"),
                ExcelCellService::Init("类型"),
                ExcelCellService::Init("型号"),
                ExcelCellService::Init("所编号"),
                ExcelCellService::Init("车站/区间"),
                ExcelCellService::Init("安装位置"),
            ])
            ->Write($sheet);

        $current_row = 2;
        EntireInstanceExcelTaggingIdentityCode::with(['EntireInstance'])
            ->where('entire_instance_excel_tagging_report_sn', $serial_number)
            ->each(function ($entire_instance_identity_code) use (&$sheet, &$current_row) {
                ExcelRowService::Init()
                    ->SetRow($current_row)
                    ->SetExcelCells([
                        ExcelCellService::Init($current_row - 1),  // A 序号
                        ExcelCellService::Init($entire_instance_identity_code->EntireInstance->identity_code),  // B 唯一编号
                        ExcelCellService::Init(@$entire_instance_identity_code->EntireInstance->Category->name),  // C 种类
                        ExcelCellService::Init(@$entire_instance_identity_code->EntireInstance->EntireModel->Parent->name ?: @$entire_instance_identity_code->EntireInstance->EntireModel->name),  // D 类型
                        ExcelCellService::Init(@$entire_instance_identity_code->EntireInstance->EntireModel->Parent->name ? @$entire_instance_identity_code->EntireInstance->EntireModel->name : ''),  // E 型号
                        ExcelCellService::Init(),  // F 所编号
                        ExcelCellService::Init(),  // G 车站/区间
                        ExcelCellService::Init(),  // H 安装位置
                        ExcelCellService::Init(),  // I 出厂时间
                    ])
                    ->Write($sheet);
                $current_row++;
            });

        $excel
            ->SetWidthByColText("A", 10)
            ->SetWidthByColText("B", 25)
            ->SetWidthByColText("C", 20)
            ->SetWidthByColText("D", 20)
            ->SetWidthByColText("E", 20)
            ->SetWidthByColText("F", 15)
            ->SetWidthByColText("G", 15)
            ->SetWidthByColText("H", 15)
            ->SetWidthByColText("I", 15)
            ->Download("室外上道位置采集单");
    }

    /**
     * 选择赋码方式页面
     * @return \Illuminate\Contracts\View\Factory|Application|View
     */
    final public function getStart()
    {
        return view("Entire.Tagging.start");
    }
}
