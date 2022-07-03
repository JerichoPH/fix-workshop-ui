<?php

namespace App\Console\Commands;

use App\Exceptions\EmptyException;
use App\Model\EntireInstance;
use Carbon\Carbon;
use Curl\Curl;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jericho\FileSystem;

class DetectorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'detector:go {work_area_name} {type?} {code?}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'detector from qsz';

    protected $__go_url = "http://127.0.0.1:8080/api/v1";
    private $__curl = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->__curl = new Curl();
    }

    /**
     * 解析继电器数据
     * @param array $request_data
     * @param EntireInstance|null $entire_instance
     * @return array[]
     * @throws Exception
     */
    final private function __analysis_relay(array $request_data = [], EntireInstance $entire_instance = null): array
    {
        if (empty($request_data)) throw new Exception("没有收到检测数据");

        $maintain_data = collect(@$request_data["台账"] ?? []);
        $before_fix_data = collect(@$request_data["检前"] ?? []);
        $fix_data = collect(@$request_data["检修"] ?? []);
        $check_data = collect(@$request_data["验收"] ?? []);
        $standard_data = collect(@$request_data["标准"] ?? []);

        $before_fix_body = [];
        $before_fix_header = [];
        if ($before_fix_data->isNotEmpty()) {
            $before_fix_data->each(function ($value, $key) use (&$before_fix_body, $standard_data) {
                if (empty($value)) return null;
                $test_project_name = ltrim($key, "A");
                $value = str_replace("/", "\r\n", $value);
                $before_fix_body[] = [
                    "conclusion" => true,
                    "unit" => "",
                    "standard_value" => $standard_data->get($key, ""),
                    "test_value" => $value,
                    "test_project_name" => $test_project_name,
                ];
            });
        }
        if (!empty($before_fix_body)) {
            $before_fix_header = [
                "testing_time" => @$maintain_data["FixedAt"] ? Carbon::parse(@$maintain_data["FixedAt"])->format("Y-m-d H:i:s") : "",
                "identity_code" => $entire_instance->identity_code,
                "tester_name" => @$maintain_data["FixerName"] ?? "",
                "record_type" => "检前",
                "detecting_set_number" => "历史记录导入",
                "serial_number" => $maintain_data["Sn"],
                "model_name" => $maintain_data["Model"],
            ];
        }

        $fix_body = [];
        $fix_header = [];
        if ($fix_data->isNotEmpty()) {
            $fix_data->each(function ($value, $key) use (&$fix_body, $standard_data) {
                if (empty($value)) return null;
                $test_project_name = ltrim($key, "A");
                $value = str_replace("/", "\r\n", $value);
                $fix_body[] = [
                    "conclusion" => true,
                    "unit" => "",
                    "standard_value" => $standard_data->get($key, ""),
                    "test_value" => $value,
                    "test_project_name" => $test_project_name,
                ];
            });
        }
        if (!empty($fix_body)) {
            $fix_header = [
                "testing_time" => @$maintain_data["FixedAt"] ? Carbon::parse(@$maintain_data["FixedAt"])->format("Y-m-d H:i:s") : "",
                "identity_code" => $entire_instance->identity_code,
                "tester_name" => @$maintain_data["FixerName"] ?? "",
                "record_type" => "检后",
                "detecting_set_number" => "历史记录导入",
                "serial_number" => $maintain_data["Sn"],
                "model_name" => $maintain_data["Model"],
            ];
        }

        $check_body = [];
        $check_header = [];
        if ($check_data->isNotEmpty()) {
            $check_data->each(function ($value, $key) use (&$check_body, $standard_data) {
                if (empty($value)) return null;
                $test_project_name = ltrim($key, "A");
                $value = str_replace("/", "\r\n", $value);
                $check_body[] = [
                    "conclusion" => true,
                    "unit" => "",
                    "standard_value" => $standard_data->get($key, ""),
                    "test_value" => $value,
                    "test_project_name" => $test_project_name,
                ];
            });
        }
        if (!empty($check_body)) {
            $check_header = [
                "testing_time" => @$maintain_data["CheckedAt"] ? Carbon::parse(@$maintain_data["CheckedAt"])->format("Y-m-d H:i:s") : "",
                "identity_code" => $entire_instance->identity_code,
                "tester_name" => @$maintain_data["CheckerName"] ?? "",
                "record_type" => "验收",
                "detecting_set_number" => "历史记录导入",
                "serial_number" => $maintain_data["Sn"],
                "model_name" => $maintain_data["Model"],
            ];
        }

        return [
            "before_fix_header" => $before_fix_header,
            "before_fix_body" => $before_fix_body,
            "fix_header" => $fix_header,
            "fix_body" => $fix_body,
            "check_header" => $check_header,
            "check_body" => $check_body,
        ];
    }

    final public function SaveErrorLog(string $log_dir, EntireInstance $entire_instance)
    {
        // file_put_contents("{$log_dir}/{$entire_instance->serial_number}_错误.json", json_encode(['error_code' => $this->_curl->errorCode, 'error_message' => $this->_curl->errorMessage,]));
        Log::channel("detection-data-go")->error("错误", [
            "identity_code" => $entire_instance->identity_code,
            "serial_number" => $entire_instance->serial_number,
            "error_code" => $this->__curl->errorCode,
            "error_message" => $this->__curl->errorMessage,
        ]);
        $this->error("--------------错误--------------");
        $this->error("所编号：{$entire_instance->serial_number}");
        $this->error("型号：{$entire_instance->model_name}");
        $this->error("错误码：{$this->__curl->errorCode}");
        $this->error("错误内容：{$this->__curl->errorMessage}");
        $this->error("-------------------------------");
        $log[] = "错误：所编号：{$entire_instance->serial_number} 型号：{$entire_instance->model_name} 错误码：{$this->__curl->errorCode} 错误内容：{$this->__curl->errorMessage}";
    }

    /**
     * 获取继电器数据
     * @param string $work_area_name
     * @param string $type
     * @param string|null $code
     * @throws EmptyException
     */
    final private function relay(string $work_area_name, string $type, ?string $code = null): void
    {
        // 创建日志目录
        $log_dir = storage_path("logs/detector/go/{$work_area_name}");
        if (!is_dir($log_dir))
            FileSystem::init(__FILE__)
                ->makeDir($log_dir);

        $url = 'http://fix-workshop.test:8888/api/detector';

        switch ($type) {
            case "all":
                EntireInstance::with([])
                    ->where('category_unique_code', 'Q01')
                    ->chunk(50, function ($entire_instances) use ($work_area_name, $log_dir, $url) {
                        $entire_instances->each(function ($entire_instance) use ($work_area_name, $log_dir, $url) {
                            if ($entire_instance->serial_number) {
                                $this->__curl->get("{$this->__go_url}/{$work_area_name}/{$entire_instance->serial_number}", [
                                    'model_name' => $entire_instance->model_name,
                                ]);

                                if ($this->__curl->error) {
                                    $this->SaveErrorLog($log_dir, $entire_instance);
                                } else {
                                    $this->generateFixWorkflow($work_area_name, $entire_instance, $url, $log_dir);
                                }
                            }
                        });
                    });
                break;
            case "identity_code":
                $entire_instance = EntireInstance::with([])
                    ->where("category_unique_code", "Q01")
                    ->where("identity_code", $code)
                    ->first();
                if (!$entire_instance) throw new EmptyException("器材不存在");

                $this->__curl->get("{$this->__go_url}/{$work_area_name}/{$entire_instance->serial_number}", [
                    "model_name" => $entire_instance->model_name,
                ]);

                if ($this->__curl->error) {
                    $this->SaveErrorLog($log_dir, $entire_instance);
                } else {
                    $this->generateFixWorkflow($work_area_name, $entire_instance, $url, $log_dir);
                }
                break;
        }
    }

    /**
     * Execute the console command.
     * @throws Exception
     */
    final public function handle(): void
    {
        $work_area_name = $this->argument('work_area_name');
        $type = $this->argument("type") ?? "all";
        $code = $this->argument("code") ?? "";

        $this->$work_area_name($work_area_name, $type, $code);
    }

    /**
     * 生成检修单
     * @param string $work_area_name
     * @param $entire_instance
     * @param string $url
     * @param string $log_dir
     * @return void
     */
    private function generateFixWorkflow(string $work_area_name, $entire_instance, string $url, string $log_dir): void
    {
        Log::channel("detection-data-go")->info("响应体", (array)$this->__curl->response->content);
        $analysis = $this->{"__analysis_{$work_area_name}"}((array)$this->__curl->response->content, $entire_instance);
        Log::channel("detection-data-go")->info("解析后", (array)$analysis);
        $this->info("请求成功：{$entire_instance->identity_code} {$entire_instance->serial_number} {$entire_instance->model_name}");

        [
            'before_fix_header' => $before_fix_header,
            'before_fix_body' => $before_fix_body,
            'fix_header' => $fix_header,
            'fix_body' => $fix_body,
            'check_header' => $check_header,
            'check_body' => $check_body,
        ] = $analysis;

        // 检查修前检是否重复
        if (
            !DB::table("fix_workflows as fw")
                ->join(DB::raw("fix_workflow_processes fwp"), "fw.serial_number", "=", "fwp.fix_workflow_serial_number")
                ->join(DB::raw("entire_instances ei"), "fw.entire_instance_identity_code", "=", "ei.identity_code")
                ->where("ei.serial_number", $before_fix_header["serial_number"])
                ->where("ei.model_name", $before_fix_header["model_name"])
                ->where("fwp.created_at", $before_fix_header["testing_time"])
                ->where("fwp.stage", "FIX_BEFORE")
                ->exists() && !empty($before_fix_header)
        ) {
            $this->__curl->post($url, ['header' => $before_fix_header, 'body' => $before_fix_body]);
            if ($this->__curl->error) {
                Log::channel("detection-data-go")->error("生成修前检错误", (array)$this->__curl->response);
            } else {
                $msg = "保存成功(检前)：{$entire_instance->identity_code} {$entire_instance->serial_number}";
                Log::channel("detection-data-go")->info($msg);
                $this->info($msg);
            }
        } else {
            $msg = "修前检已经存在，跳过：{$entire_instance->identity_code} {$before_fix_header["serial_number"]} {$before_fix_header["model_name"]} {$before_fix_header["testing_time"]}";
            $this->comment($msg);
            Log::channel("detection-data-go")->info($msg);
        }

        if (
            !DB::table("fix_workflows as fw")
                ->join(DB::raw("fix_workflow_processes fwp"), "fw.serial_number", "=", "fwp.fix_workflow_serial_number")
                ->join(DB::raw("entire_instances ei"), "fw.entire_instance_identity_code", "=", "ei.identity_code")
                ->where("ei.serial_number", $fix_header["serial_number"])
                ->where("ei.model_name", $fix_header["model_name"])
                ->where("fwp.created_at", $fix_header["testing_time"])
                ->where("fwp.stage", "FIX_AFTER")
                ->exists() && !empty($fix_header)
        ) {
            $this->__curl->post($url, ['header' => $fix_header, 'body' => $fix_body]);
            if ($this->__curl->error) {
                Log::channel("detection-data-go")->error("生成修后检错误", (array)$this->__curl->response);
            } else {
                $msg = "保存成功(检后)：{$entire_instance->identity_code} {$entire_instance->serial_number}";
                Log::channel("detection-data-go")->info($msg);
                $this->info($msg);
            }
        } else {
            $msg = "修后检已经存在，跳过：{$entire_instance->identity_code} {$fix_header["serial_number"]} {$fix_header["model_name"]} {$fix_header["testing_time"]}";
            $this->comment($msg);
            Log::channel("detection-data-go")->info($msg);
        }

        if (
            !DB::table("fix_workflows as fw")
                ->join(DB::raw("fix_workflow_processes fwp"), "fw.serial_number", "=", "fwp.fix_workflow_serial_number")
                ->join(DB::raw("entire_instances ei"), "fw.entire_instance_identity_code", "=", "ei.identity_code")
                ->where("ei.serial_number", $check_header["serial_number"])
                ->where("ei.model_name", $check_header["model_name"])
                ->where("fwp.created_at", $check_header["testing_time"])
                ->where("fwp.stage", "CHECKED")
                ->exists() && !empty($check_header)
        ) {
            $this->__curl->post($url, ['header' => $check_header, 'body' => $check_body]);
            if ($this->__curl->error) {
                Log::channel("detection-data-go")->error("生成验收错误", (array)$this->__curl->response);
            } else {
                $msg = "保存成功(验收)：{$entire_instance->identity_code} {$entire_instance->serial_number}";
                Log::channel("detection-data-go")->info($msg);
                $this->info($msg);
            }
        } else {
            $msg = "验收已经存在，跳过：{$entire_instance->identity_code} {$check_header["serial_number"]} {$check_header["model_name"]} {$check_header["testing_time"]}";
            $this->comment($msg);
            Log::channel("detection-data-go")->info($msg);
        }
    }
}
