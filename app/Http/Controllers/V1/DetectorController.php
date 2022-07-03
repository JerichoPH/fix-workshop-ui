<?php

namespace App\Http\Controllers\V1;

use App\Facades\CodeFacade;
use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\EntireInstance;
use App\Model\EntireInstanceLog;
use App\Model\EntireModel;
use App\Model\FixWorkflow;
use App\Model\FixWorkflowProcess;
use App\Validations\Api\Detector\LoginValidation;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Jericho\FileSystem;
use Throwable;

class DetectorController extends Controller
{
    final public function index(): string
    {
        return '检测台上传测试';
    }

    /**
     * 上传测试数据（JSON）
     * @param Request $request
     * @return mixed
     */
    final public function store(Request $request)
    {
        $request_data = $request->all();
        try {
            if (empty($request_data)) return JsonResponseFacade::errorValidate('没有收到检测数据');

            // 保存日志
            file_put_contents(storage_path('detector-log-request.json'), json_encode($request_data, 256));

            ['header' => $request_header, 'body' => $request_body] = $request_data;
            if (empty($request_header)) return JsonResponseFacade::errorValidate('没有收到检测数据(数据头)');
            if (empty($request_body)) return JsonResponseFacade::errorValidate('没有收到检测数据(数据体)');

            // 判断编号是否是唯一编号
            if (preg_match('/^S.{13}/i', $request_header['identity_code']) || preg_match('/^Q.{18}/i', $request_header['identity_code'])) {
                $entire_instance = EntireInstance::with([])->where('identity_code', $request_header['identity_code'] ?? '')->firstOrFail();
            } else {
                if (!@$request_header['test_device_model']) return JsonResponseFacade::errorForbidden("通过所编号上传的检修记录必须包含种类型(test_device_model)");

                $entire_model = EntireModel::with([])->where('unique_code', $request_header['test_device_model'])->first();
                if (!$entire_model) return JsonResponseFacade::errorForbidden("型号：{$request_header['test_device_model']}没有找到，请联系管理员更新配置");

                $entire_instances = EntireInstance::with([])->where('serial_number', $request_header['identity_code'])->where('model_unique_code', $request_header['test_device_model'])->get();
                if ($entire_instances->isEmpty()) return JsonResponseFacade::errorForbidden("没有找到器材：{$request_header['identity_code']}");
                if (!$entire_instances->count() > 1) return JsonResponseFacade::errorForbidden("存在多台器材：{$request_header['identity_code']}");

                $entire_instance = $entire_instances->first();
            }

            $processor = null;
            $processor = Account::with([])->where('nickname', $request_header['tester_name'])->first();
            if (!$processor) return JsonResponseFacade::errorEmpty('检测人不存在');

            $is_allow = true;
            // $conclusions = array_unique(array_pluck($request_body, 'conclusion'));
            foreach ($request_body as $item) {
                if (array_key_exists('test_value', $item)) {
                    if (empty($item['test_value'])) continue;

                    if ($item['conclusion'] == 0) {
                        $is_allow = false;
                        break;
                    }
                }
            }

            $stages = [
                '检前' => 'FIX_BEFORE',
                '检后' => 'FIX_AFTER',
                '验收' => 'CHECKED',
                '抽验' => 'SPOT_CHECK',
                '工程测试' => 'PROJECT_TEST',
                '新设备' => 'NEW_TEST',
                '新器材' => 'NEW_TEST',
            ];
            if (!@$stages[$request_header['record_type']]) return JsonResponseFacade::errorValidate('类型只能是：' . implode('、', array_keys($stages)));
            $current_stage = $stages[$request_header['record_type']] ?? '';

            $current_status = (
                $current_stage == 'CHECKED'
                || $current_stage == 'SPOT_CHECK'
                || $current_stage == 'PROJECT_TEST'
                || $current_stage == 'NEW_TEST'
            )
                ? ($is_allow ? 'FIXED' : 'FIXING')
                : 'FIXING';

            $testing_time = @$request_header['testing_time'] ?? date('Y-m-d H:i:s');
            try {
                $processed_at = Carbon::parse($testing_time);
            } catch (Exception $e) {
                return JsonResponseFacade::errorForbidden("检测时间格式不正确：{$testing_time}");
            }

            DB::beginTransaction();

            // 获取器材最后一张检修单
            $old_fix_workflow = FixWorkflow::with(['EntireInstance'])->where('serial_number', $entire_instance->fix_workflow_serial_number)->first();
            if (
                !$old_fix_workflow ||
                (
                in_array(
                    @array_flip(FixWorkflow::$STAGE)[$old_fix_workflow->stage] ?? '',
                    ['FIXED', 'CHECKED', 'WORKSHOP', 'SECTION', 'PROJECT_TEST', 'NEW_TEST',]
                )
                )
            ) {
                // 创建新检修单
                $fix_workflow = FixWorkflow::with(['EntireInstance'])
                    ->create([
                        'created_at' => $processed_at,
                        'updated_at' => $processed_at,
                        'entire_instance_identity_code' => $entire_instance->identity_code,
                        'status' => $current_status,
                        'processor_id' => $processor->id ?? 0,
                        'serial_number' => CodeFacade::makeSerialNumber('FIX_WORKFLOW', $processed_at->format('Ymd')),
                        'processed_times' => 0,
                        'stage' => $current_stage,
                        'type' => 'FIX',
                    ]);
            } else {
                $fix_workflow = $old_fix_workflow;
            }
            $fix_workflow->fill(["stage" => $current_stage, "status" => $current_status,])->saveOrFail();

            $fix_workflow_process_sn = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', $processed_at->format('Ymd'));
            $dest_dir = public_path('/check');
            if (!is_dir($dest_dir)) FileSystem::init(__FILE__)->makeDir($dest_dir);
            $filename = "{$fix_workflow_process_sn}.json";
            file_put_contents("{$dest_dir}/{$filename}", json_encode($request_data));

            FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])
                ->create([
                    'created_at' => $processed_at,
                    'updated_at' => $processed_at,
                    'fix_workflow_serial_number' => $fix_workflow->serial_number,
                    'stage' => $current_stage,
                    'type' => 'ENTIRE',
                    'auto_explain' => '',
                    'serial_number' => $fix_workflow_process_sn,
                    'numerical_order' => '1',
                    'is_allow' => intval($is_allow),
                    'processor_id' => $processor->id,
                    'processed_at' => $processed_at->format('Y-m-d H:i:s'),
                    'upload_url' => "/check/{$filename}",
                    'check_type' => 'JSON2',
                    'upload_file_name' => $filename,
                ]);
            EntireInstanceLog::with([])
                ->create([
                    'created_at' => $processed_at,
                    'updated_at' => $processed_at,
                    'name' => FixWorkflowProcess::$STAGE[$current_stage],
                    'description' => '操作人：' . $processor->nickname,
                    'entire_instance_identity_code' => $fix_workflow->entire_instance_identity_code,
                    'type' => 2,
                    'url' => "/measurement/fixWorkflow/{$fix_workflow->serial_number}/edit",
                    'operator_id' => $processor->id,
                    'station_unique_code' => '',
                ]);

            $entire_instance_update_datum = [
                "status" => $current_status,
                "fix_workflow_serial_number" => $fix_workflow->serial_number,
            ];
            if ($current_stage === 'FIX_AFTER') {
                $entire_instance_update_datum["fixer_name"] = $processor->nickname;
                $entire_instance_update_datum["fixed_at"] = $processed_at->format('Y-m-d H:i:s');
            }
            if ($current_status === 'FIXED' && in_array($current_stage, ['CHECKED', 'PROJECT_TEST', 'NEW_TEST',])) {
                $entire_instance_update_datum["checker_name"] = $processor->nickname;
                $entire_instance_update_datum["checked_at"] = $processed_at->format('Y-m-d H:i:s');
            }
            if ($current_status === 'FIXED' && $current_stage == 'SPOT_CHECK') {
                $entire_instance_update_datum["spot_checker_name"] = $processor->nickname;
                $entire_instance_update_datum["spot_checked_at"] = $processed_at->format('Y-m-d H:i:s');
            }

            // 验证生产日期
            $made_at = null;
            $scarping_at = null;
            if (@$request_header['made_at']) {
                try {
                    $made_at = Carbon::parse($request_header['made_at']);

                    // 计算报废日期
                    if (substr($entire_instance->identity_code, 0, 1) === 'S') {
                        if ($entire_instance->EntireModel->life_year > 0) {
                            $scarping_at = $made_at->copy()->addYears($entire_instance->EntireModel->life_year);
                        }
                    } else {
                        if ($entire_instance->SubModel->life_year > 0) {
                            $scarping_at = $made_at->copy()->addYears($entire_instance->SubModel->life_year);
                        }
                    }
                } catch (Exception $e) {
                    return JsonResponseFacade::errorValidate('生产日期格式不正确，请使用：YYYY-MM-DD格式');
                }
            }
            // 厂编号
            $factory_device_code = @$request_header['factory_device_code'] ?? '';
            // 厂家名称
            $factory_name = @$request->get('factory_name', '') ?: '';
            // 线制
            $line_name = @$request->get('line_name', '') ?: '';
            // 表示杆特征
            $said_rod = @$request->get('said_rod', '') ?: '';
            // 防挤压装置
            $extrusion_protect = @$request->get('extrusion_protect', '') ?: '';
            // 备注
            $note = @$request->get('note', '') ?: '';

            if ($made_at) $entire_instance_update_datum['made_at'] = $made_at->format('Y-m-d');
            if ($scarping_at) $entire_instance_update_datum['scarping_at'] = $scarping_at->format('Y-m-d');
            if ($factory_device_code) $entire_instance_update_datum['factory_device_code'] = $factory_device_code;
            if ($factory_name) $entire_instance_update_datum['factory_name'] = $factory_name;
            if ($line_name) $entire_instance_update_datum['line_name'] = $line_name;
            if ($said_rod) $entire_instance_update_datum['said_rod'] = $said_rod;
            if ($extrusion_protect) $entire_instance_update_datum['extrusion_protect'] = $extrusion_protect;
            if ($note) $entire_instance_update_datum['note'] = $note;

            $fix_workflow->EntireInstance->fill($entire_instance_update_datum)->saveOrFail();

            DB::table('part_instances')->where('entire_instance_identity_code', $fix_workflow->entire_instance_identity_code)->update(['updated_at' => now(), 'status' => $current_status]);
            DB::table('entire_instances')->where('entire_instance_identity_code', $fix_workflow->entire_instance_identity_code)->update(['updated_at' => now(), 'status' => $current_status]);

            DB::commit();

            file_put_contents(storage_path('detector-log-response.json'), '上传成功');

            return JsonResponseFacade::created([], '上传成功');
        } catch (ModelNotFoundException $e) {
            file_put_contents(storage_path('detector-log-response.json'), "没有找到设备器材：{$request_data['header']['identity_code']}");
            return JsonResponseFacade::errorEmpty("没有找到设备器材");
        } catch (Throwable $e) {
            file_put_contents(storage_path('detector-log-response.json'), [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return JsonResponseFacade::errorException($e);
            // return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 上传测试数据（PDF）
     * @param Request $request
     * @return mixed
     */
    final public function postFile(Request $request)
    {
        try {
            $entire_instance = EntireInstance::with([])->where('identity_code', $request->get('identity_code'))->firstOrFail();
            $processor = null;
            $processor = Account::with([])->where('nickname', $request->get('tester_name'))->first();
            if (!$processor) return JsonResponseFacade::errorEmpty("检测人：【{$request->get('tester_name')}】 不存在");
            if (!$request->hasFile('file')) return JsonResponseFacade::errorValidate('文件上传失败');
            $is_allow = boolval($request->get('is_allow'));

            $file = $request->file('file');
            $allowed_extensions = ['pdf'];
            if (!in_array($file->getClientOriginalExtension(), $allowed_extensions))
                return JsonResponseFacade::errorValidate('只能上传' . implode(',', $allowed_extensions) . '格式的文件');

            $stages = [
                '检前' => 'FIX_BEFORE',
                '检后' => 'FIX_AFTER',
                '验收' => 'CHECKED',
                '抽验' => 'SPOT_CHECK',
                '工程测试' => 'PROJECT_TEST',
                '新设备' => 'NEW_TEST',
                '新器材' => 'NEW_TEST',
            ];
            $current_stage = $stages[$request->get('record_type')] ?? 'CHECKED';
            $current_status = (
                $current_stage == 'CHECKED'
                || $current_stage == 'SPOT_CHECK'
                || $current_stage == 'PROJECT_TEST'
                || $current_stage == 'NEW_TEST'
            )
                ? ($is_allow ? 'FIXED' : 'FIXING')
                : 'FIXING';

            try {
                $processed_at = Carbon::parse($request->get('testing_time'));
            } catch (Exception $e) {
                return JsonResponseFacade::errorForbidden("检测时间格式不正确：{$request->get('testing_time')}");
            }

            DB::beginTransaction();
            # 创建检修单
            $fix_workflow = FixWorkflow::with(['EntireInstance'])
                ->create([
                    'created_at' => $processed_at,
                    'updated_at' => $processed_at,
                    'entire_instance_identity_code' => $entire_instance->identity_code,
                    'status' => $current_status,
                    'processor_id' => $processor->id ?? 0,
                    'serial_number' => CodeFacade::makeSerialNumber('FIX_WORKFLOW', $processed_at->format('Ymd')),
                    'processed_times' => 0,
                    'stage' => 'CHECKED',
                    'type' => 'FIX',
                ]);

            $fix_workflow_process_sn = CodeFacade::makeSerialNumber('FIX_WORKFLOW_PROCESS', $processed_at->format('Ymd'));
            $dest_dir = public_path('/check');
            if (!is_dir($dest_dir)) FileSystem::init(__FILE__)->makeDir($dest_dir);
            $extension = $file->getClientOriginalExtension();
            $filename = "{$fix_workflow_process_sn}.{$extension}";
            $file->move($dest_dir, $filename);

            $fix_workflow_process = FixWorkflowProcess::with(['FixWorkflow', 'FixWorkflow.EntireInstance'])
                ->create([
                    'created_at' => $processed_at->format('Y-m-d H:i:s'),
                    'updated_at' => $processed_at->format('Y-m-d H:i:s'),
                    'fix_workflow_serial_number' => $fix_workflow->serial_number,
                    'stage' => $current_stage,
                    'type' => 'ENTIRE',
                    'auto_explain' => '',
                    'serial_number' => $fix_workflow_process_sn,
                    'numerical_order' => '1',
                    'is_allow' => intval($request->get('is_allow')),
                    'processor_id' => $processor->id,
                    'processed_at' => $processed_at->format('Y-m-d H:i:s'),
                    'upload_url' => "/check/{$filename}",
                    'check_type' => strtoupper($extension),
                    'upload_file_name' => $filename,
                ]);
            EntireInstanceLog::with([])
                ->create([
                    'created_at' => $processed_at->format('Y-m-d H:i:s'),
                    'updated_at' => $processed_at->format('Y-m-d H:i:s'),
                    'name' => FixWorkflowProcess::$STAGE[$current_stage],
                    'description' => '操作人：' . $processor->nickname ?? $request->get('tester_name'),
                    'entire_instance_identity_code' => $entire_instance->identity_code,
                    'type' => 2,
                    'url' => "/measurement/fixWorkflow/{$fix_workflow->serial_number}/edit",
                    'operator_id' => $processor->id ?? 0,
                    'station_unique_code' => '',
                ]);

            $entire_instance_update_datum = [
                "status" => $current_status,
                "fix_workflow_serial_number" => $fix_workflow->serial_number,
            ];
            if ($current_stage === 'FIX_AFTER') {
                $entire_instance_update_datum["fixer_name"] = $processor->nickname;
                $entire_instance_update_datum["fixed_at"] = $processed_at->format('Y-m-d H:i:s');
            }
            if ($current_status === 'FIXED' && in_array($current_stage, ['CHECKED', 'PROJECT_TEST', 'NEW_TEST',])) {
                $entire_instance_update_datum["checker_name"] = $processor->nickname;
                $entire_instance_update_datum["checked_at"] = $processed_at->format('Y-m-d H:i:s');
            }
            if ($current_status === 'FIXED' && $current_stage == 'SPOT_CHECK') {
                $entire_instance_update_datum["spot_checker_name"] = $processor->nickname;
                $entire_instance_update_datum["spot_checked_at"] = $processed_at->format('Y-m-d H:i:s');
            }

            // 验证生产日期
            $made_at = null;
            $scarping_at = null;
            if ($request->get('made_at')) {
                try {
                    $made_at = Carbon::parse($request->get('made_at'));

                    // 计算报废日期
                    if (substr($entire_instance->identity_code, 0, 1) === 'S') {
                        if ($entire_instance->EntireModel->life_year > 0) {
                            $scarping_at = $made_at->copy()->addYears($entire_instance->EntireModel->life_year);
                        }
                    } else {
                        if ($entire_instance->SubModel->life_year > 0) {
                            $scarping_at = $made_at->copy()->addYears($entire_instance->SubModel->life_year);
                        }
                    }
                } catch (Exception $e) {
                    return JsonResponseFacade::errorValidate('生产日期格式不正确，请使用：YYYY-MM-DD格式');
                }
            }
            // 厂编号
            $factory_device_code = @$request->get('factory_device_code', '') ?: '';
            // 厂家名称
            $factory_name = @$request->get('factory_name', '') ?: '';
            // 线制
            $line_name = @$request->get('line_name', '') ?: '';
            // 表示杆特征
            $said_rod = @$request->get('said_rod', '') ?: '';
            // 防挤压装置
            $extrusion_protect = @$request->get('extrusion_protect', '') ?: '';
            // 备注
            $note = @$request->get('note', '') ?: '';

            if ($made_at) $entire_instance_update_datum['made_at'] = $made_at->format('Y-m-d');
            if ($scarping_at) $entire_instance_update_datum['scarping_at'] = $scarping_at->format('Y-m-d');
            if ($factory_device_code) $entire_instance_update_datum['factory_device_code'] = $factory_device_code;
            if ($factory_name) $entire_instance_update_datum['factory_name'] = $factory_name;
            if ($line_name) $entire_instance_update_datum['line_name'] = $line_name;
            if ($said_rod) $entire_instance_update_datum['said_rod'] = $said_rod;
            if ($extrusion_protect) $entire_instance_update_datum['extrusion_protect'] = $extrusion_protect;
            if ($note) $entire_instance_update_datum['note'] = $note;

            $fix_workflow->EntireInstance->fill($entire_instance_update_datum)->saveOrFail();

            DB::table('part_instances')->where('entire_instance_identity_code', $fix_workflow->entire_instance_identity_code)->update(['updated_at' => now(), 'status' => $current_status]);
            DB::table('entire_instances')->where('entire_instance_identity_code', $fix_workflow->entire_instance_identity_code)->update(['updated_at' => now(), 'status' => $current_status]);
            DB::commit();

            return JsonResponseFacade::created([], '上传成功');
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty("没有找到设备器材");
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 通过Excel上传检测数据
     * @param Request $request
     */
    final public function postExcel(Request $request)
    {
        return JsonResponseFacade::errorForbidden('暂无');
    }

    /**
     * 检查设备器材是否存在并返回设备种类型名称
     * @return mixed
     */
    final public function getEntireInstance()
    {
        try {
            $identity_code = request('identity_code', '') ?? '';
            if (!$identity_code) return JsonResponseFacade::errorValidate('唯一编号不能为空');

            if (preg_match('/^S.{13}/i', $identity_code) || preg_match('/^Q.{18}/i', $identity_code)) {
                $entire_instance = EntireInstance::with([
                    'Category',
                    'EntireModel',
                    'EntireModel.Parent',
                ])
                    ->where('identity_code', $identity_code)
                    ->first();
                if (!$entire_instance) return JsonResponseFacade::errorEmpty("设备器材不存在：{$identity_code}");

                return JsonResponseFacade::dict([
                    'category_name' => @$entire_instance->Category->name ?: '',
                    'entire_model_name' => @$entire_instance->EntireModel->Parent ? $entire_instance->EntireModel->Parent->name : (@$entire_instance->EntireModel->name ?: ''),
                    'model_name' => @$entire_instance->EntireModel->Parent ? $entire_instance->EntireModel->name : '',
                    'factory_device_code' => @$entire_instance->factory_device_code ?: '',
                    'factory_name' => @$entire_instance->factory_name ?: '',
                    'made_at' => @$entire_instance->made_at ? Carbon::parse(@$entire_instance->made_at)->format('Y-m-d') : '',
                    'line_name' => @$entire_instance->line_name ?: '',
                    'said_rod' => @$entire_instance->said_rod ?: '',
                    'extrusion_protect' => @$entire_instance->extrusion_protect ? '是' : '否',
                    'note' => @$entire_instance->note ?: '',
                ]);
            } else {
                return JsonResponseFacade::errorValidate('唯一编号格式不正确');
            }
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 检查设备器材是否存在
     * @param string $code
     * @return mixed
     */
    final public function getCheckExists(string $code)
    {
        // 判断是否是唯一编号
        if (preg_match('/^S.{13}/i', $code) || preg_match('/^Q.{18}/i', $code)) {
            $entire_instance = DB::table('entire_instance as ei')
                ->selectRaw(implode(',', [
                    'c.name as category_name',
                    'sm.name as model_name',
                ]))
                ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_name')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'sm.category_unique_code')
                ->where('ei.identity_code', $code)
                ->first();
            if (!$entire_instance) return JsonResponseFacade::errorEmpty("唯一编号：{$code}没有找到设备器材");

            return JsonResponseFacade::dict(['category_name' => $entire_instance->cateogry_name, 'model_name' => $entire_instance->model_name,], '查验成功');
        } else {
            $entire_instances = DB::table('entire_instance as ei')
                ->selectRaw(implode(',', [
                    'c.name as category_name',
                    'sm.name as model_name',
                ]))
                ->join(DB::raw('entire_models sm'), 'sm.unique_code', '=', 'ei.model_name')
                ->join(DB::raw('categories c'), 'c.unique_code', '=', 'sm.category_unique_code')
                ->where('ei.serial_number', $code)
                ->get();
            if ($entire_instances->isEmpty()) return JsonResponseFacade::errorEmpty("所编号:{$code}没有找到设备器材");
            if ($entire_instances->count() > 1) return JsonResponseFacade::errorForbidden("所编号：{$code} 找到：" . $entire_instances->count() . '条设备器材。请手动选择型号');
            $entire_instance = $entire_instances->first();
            return JsonResponseFacade::dict(['category_name' => $entire_instance->cateogry_name, 'model_name' => $entire_instance->model_name,], '查验成功');
        }
    }

    /**
     * 登录
     * @param Request $request
     */
    final public function postLogin(Request $request)
    {
        $validation = new LoginValidation($request);
        $v = $validation->check();
        if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

        $validated = $validation->validated();

        $account = Account::with([])->where('account', $validated->get('account'))->first();
        if (!$account) return JsonResponseFacade::errorEmpty('用户不存在');

        if (!Hash::check($validated->get('password'), $account->get('password'))) return JsonResponseFacade::errorUnauthorized('账号或密码不正确');

        return JsonResponseFacade::dict(['name' => $account->nickname,], '登陆成功');
    }
}
