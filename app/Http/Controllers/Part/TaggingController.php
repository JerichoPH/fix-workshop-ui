<?php

namespace App\Http\Controllers\Part;

use App\Facades\CodeFacade;
use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\KindsFacade;
use App\Facades\ModelBuilderFacade;
use App\Facades\OrganizationFacade;
use App\Model\Account;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireInstanceExcelTaggingIdentityCode;
use App\Model\EntireInstanceExcelTaggingReport;
use App\Model\EntireInstanceLock;
use App\Model\EntireInstanceLog;
use App\Model\EntireModel;
use App\Model\Factory;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use App\Model\FixWorkflowRecord;
use App\Model\Maintain;
use App\Model\PartInstance;
use App\Model\PartInstanceExcelTaggingIdentityCode;
use App\Model\PartInstanceExcelTaggingReport;
use App\Model\PartInstanceLog;
use App\Model\PartModel;
use App\Model\WarehouseReportEntireInstance;
use App\Model\WorkArea;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

class TaggingController extends Controller
{
    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    final public function create()
    {
        if (!session('account.work_area_unique_code')) return redirect('/')->with('danger', '当前用户没有工区');

        // 根据工区获取种类
        $categories = KindsFacade::getCategories([],function($db){
            return $db->where("is_show",true);
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

        return view('Part.Tagging.create', [
            'categories_as_json' => $categories,
            'entire_models_as_json' => $entire_models,
            'models_as_json' => $models,
            'factories_as_json' => $factories,
            'statuses_as_json' => $statuses,
            'scene_workshops_as_json' => $scene_workshops,
            'lines_as_json' => $lines,
            'stations_as_json' => $stations,
            'work_areas' => $work_areas,
            'part_categories_as_json' => $part_categories,
        ]);
    }

    /**
     * 批量赋码
     * @param Request $request
     * @return mixed
     */
    final public function store(Request $request)
    {
        try {
            if (!session('account.work_area_unique_code')) return back()->with('danger', '当前用户没有所属工区');
            $work_area_type = intval(substr(session('account.work_area_unique_code'), 5));
            $statuses = collect(PartInstance::$STATUSES);
            // 表单验证
            if (!$request->get('category_unique_code')) return back()->with('danger', '种类不能为空');
            $category = Category::with([])->where('unique_code', $request->get('category_unique_code'))->first();
            if (!$category) return back()->with('danger', '种类参数错误');
            if (!$request->get('entire_model_unique_code')) return back()->with('danger', '类型不能为空');
            $entire_model = EntireModel::with([])->where('unique_code', $request->get('entire_model_unique_code'))->first();
            if (!$entire_model) return back()->with('danger', '类型参数错误');
            if (!$request->get('model_unique_code')) return back()->with('danger', '型号不能为空');
            $part_model = EntireModel::with([])->where('unique_code',$request->get('model_unique_code'))->first();
            if (!$part_model) return back()->with('danger', '型号参数错误');

            if ($request->get('number') <= 0) return back()->with('danger', '数量必须是大于0的正整数');
            $made_at = null;
            if ($request->get('made_at')) {
                try {
                    $made_at = Carbon::parse($request->get('made_at'));
                } catch (Exception $e) {
                    return back()->with('danger', '生产日期格式不正确');
                }
            }
            $last_installed_time = 0;
            if ($request->get('last_installed_at')) {
                try {
                    $last_installed_time = Carbon::parse($request->get('last_installed_at'));
                } catch (Exception $e) {
                    return back()->with('danger', '上道日期格式不正确');
                }
            }
            $factory_name = '';
            if (!$request->get('status')) {
                return back()->with('danger', '状态不能为空');
            }
            if (!($statuses->get($request->get('status', '') ?? '') ?? '')) {
                return back()->with('danger', '状态参数错误');
            }

            // 批量赋码
            $new_identity_codes = [];
            $current_model_unique_code = '';
            $new_identity_codes = CodeFacade::makeEntireInstanceIdentityCodes($request->get('model_unique_code'), intval($request->get('number')));
            $current_model_unique_code = $request->get('model_unique_code');

            DB::beginTransaction();
            // 生成赋码单
            $entire_instance_excel_tagging_report = PartInstanceExcelTaggingReport::with([])->create([
                'serial_number' => $part_instance_excel_tagging_report_sn = PartInstanceExcelTaggingReport::generateSerialNumber(),
                'is_upload_create_device_excel_error' => false,
                'work_area_type' => $work_area_type,
                'processor_id' => session('account.id'),
                'work_area_unique_code' => session('account.work_area_unique_code'),
            ]);

            // 设置参数
            $new_part_instances = collect([]);
            $new_part_instance_logs = collect([]);
            $part_instance_excel_tagging_identity_codes = collect([]);
            if (!empty($new_identity_codes)) {
                foreach ($new_identity_codes as $new_identity_code) {
                    $scraping_at = null;
                    if ($made_at) {
                        if ($part_model->life_year) {
                            $scraping_at = Carbon::parse($made_at)->addYears($part_model->life_year);
                        }
                    }

                    $new_part_instance = [
                        'created_at' => now(),
                        'updated_at' => now(),
                        'identity_code' => $new_identity_code,
                        'category_unique_code' => $request->get('category_unique_code'),
                        'entire_model_unique_code' => $request->get('entire_model_unique_code'),
                        'part_model_unique_code' => $request->get('model_unique_code'),
                        'made_at' => $made_at,
                        'scraping_at' => $scraping_at ? $scraping_at->toDateString() : null,
                        'installed_at' => $last_installed_time->format("Y-m-d H:i:s"),
                        'factory_name' => $factory_name,
                        'status' => $request->get('status'),
                        'note' => $request->get('note', '') ?? '',
                        'work_area_unique_code' => session('account.work_area_unique_code'),
                    ];

                    $new_part_instances->push($new_part_instance);

                    $new_part_instance_logs->push([
                        'created_at' => now(),
                        'updated_at' => now(),
                        'name' => '赋码',
                        'description' => $new_part_instance['made_at'] ? ($new_part_instance['scarping_at'] ? "出厂日期：{$new_part_instance['made_at']}；报废日期：{$new_part_instance['scarping_at']}；" : "出厂日期：{$new_part_instance['made_at']}；") : '',
                        'part_instance_identity_code' => $new_part_instance['identity_code'],
                        'type' => 0,
                        'url' => '',
                        'material_type' => 'PART',
                        'operator_id' => session('account.id'),
                        'station_unique_code' => '',
                    ]);

                    $part_instance_excel_tagging_identity_codes->push([
                        'created_at' => now(),
                        'updated_at' => now(),
                        'part_instance_excel_tagging_report_sn' => $part_instance_excel_tagging_report_sn,
                        'part_instance_identity_code' => $new_identity_code,
                    ]);
                }

                // 保存的到数据库
                DB::table('part_instances')->insert($new_part_instances->toArray());  // 保存设备器材
                DB::table('part_instance_logs')->insert($new_part_instance_logs->toArray());  // 保存设备器材赋码日志
                $current_part_instance_count = DB::table('entire_instance_counts as cic')->where('entire_model_unique_code', $current_model_unique_code)->first();
                if ($current_part_instance_count) {
                    $new_count = $current_part_instance_count->count + intval($request->get('number'));
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
                DB::table('part_instance_excel_tagging_identity_codes')->insert($part_instance_excel_tagging_identity_codes->toArray());
                DB::commit();

                return redirect("/part/tagging/{$part_instance_excel_tagging_report_sn}/uploadCreateDeviceReport?" . http_build_query([
                        'page' => $request->get('page'),
                        'type' => $request->get('type'),
                    ]))
                    ->with('success', '设备赋码：' . $request->get('number') . '条。');
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
     * 下载设备赋码Excel错误报告
     * @param string $serial_number
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    final public function getDownloadCreateDeviceErrorExcel(string $serial_number)
    {
        try {
            PartInstanceExcelTaggingReport::with([])->where('serial_number', $serial_number)->firstOrFail();

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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getReport(int $id = 0)
    {
        try {
            // 当前时间
            list($origin_at, $finish_at) = explode('~', request('created_at') ?? join('~', [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')]));

            // 操作人列表
            $accounts = Account::with([])->where('work_area_unique_code', session('account.work_area_unique_code'))->get();

            // 赋码记录表
            $part_instance_excel_tagging_reports = ModelBuilderFacade::init(
                request(),
                PartInstanceExcelTaggingReport::with([]),
                ['created_at']
            )
                ->extension(
                    function ($part_instance_excel_tagging_report) use ($origin_at, $finish_at) {
                        return $part_instance_excel_tagging_report
                            ->whereBetween('created_at', [Carbon::parse($origin_at)->startOfDay()->format('Y-m-d H:i:s'), Carbon::parse($finish_at)->endOfDay()->format('Y-m-d H:i:s')])
                            ->orderByDesc('created_at');
                    }
                )
                ->pagination(50);
            $create_device_error_dir = 'partInstanceTagging/upload/errorExcels/createDevice';

            return view('Part.Tagging.report', [
                'part_instance_excel_tagging_reports' => $part_instance_excel_tagging_reports,
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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function getReportShow(int $id)
    {
        try {
            $part_instance_excel_tagging_report = PartInstanceExcelTaggingReport::with([
                'PartInstanceIdentityCodes',
                'PartInstanceIdentityCodes.PartInstance',
                'PartInstanceIdentityCodes.PartInstance.PartModel',
            ])
                ->where('id', $id)
                ->firstOrFail();

            return view('Part.Tagging.reportShow', [
                'part_instance_excel_tagging_report' => $part_instance_excel_tagging_report,
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
     */
    final public function postRollback(Request $request, int $id)
    {
        try {
            $part_instance_excel_tagging_report = PartInstanceExcelTaggingReport::with([])->where('id', $id)->firstOrFail();
            $identity_codes = PartInstanceExcelTaggingIdentityCode::with([])->where('part_instance_excel_tagging_report_sn', $part_instance_excel_tagging_report->serial_number)->pluck('part_instance_identity_code')->toArray();  // 获取赋码设备器材唯一编号组

            $part_instance_excel_tagging_report->forceDelete();  // 删除设备器材赋码记录单
            PartInstanceExcelTaggingIdentityCode::with([])->where('entire_instance_excel_tagging_report_sn', $part_instance_excel_tagging_report->serial_number)->forceDelete();  // 删除设备器材赋码唯一编号记录
            PartInstance::with([])->whereIn('identity_code', $identity_codes)->forceDelete();  // 删除设备器材
            PartInstanceLog::with([])->whereIn('part_instance_identity_code', $identity_codes)->forceDelete();  // 删除设备器材日志

            $fix_workflow_serial_numbers = FixWorkflow::with([])->whereIn('entire_instance_identity_code', $identity_codes)->pluck('serial_number')->toArray();  // 获取检修单编号组
            $fix_workflow_process_serial_numbers = FixWorkflowProcess::with([])->whereIn('fix_workflow_serial_number', $fix_workflow_serial_numbers)->pluck('serial_number')->toArray();  // 获取检修过程单编号组
            FixWorkflowRecord::with([])->whereIn('fix_workflow_process_serial_number', $fix_workflow_process_serial_numbers)->forceDelete();  // 删除检修记录值
            FixWorkflowProcess::with([])->whereIn('fix_workflow_serial_number', $fix_workflow_serial_numbers)->forceDelete();  // 删除检测过程单
            FixWorkflow::with([])->whereIn('entire_instance_identity_code', $identity_codes)->forceDelete();  // 删除检修单

            $part_instance_excel_tagging_report->forceDelete();  // 删除赋码批次单

            return JsonResponseFacade::deleted([], '回退成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }
}
