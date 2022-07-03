<?php

namespace App\Http\Controllers;

use App\Exceptions\ExcelInException;
use App\Exceptions\ValidateException;
use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\MaterialFacade;
use App\Model\CollectionOrder;
use App\Model\CollectionOrderEntireInstance;
use App\Model\EntireInstance;
use App\Model\Install\InstallPosition;
use App\Model\Maintain;
use App\Services\ExcelReaderService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;
use Throwable;

class CollectionOrderController extends Controller
{

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index(Request $request)
    {
        try {
            $collectionOrders = CollectionOrder::with(['WithStationInstallUser'])
                ->orderByDesc('updated_at')
                ->paginate(30);
            return view('CollectionOrder.index', [
                'collectionOrders' => $collectionOrders,
            ]);
        } catch (Exception $e) {
            CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * 生成Excel
     * @param string $uniqueCode
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    final public function makeExcel(string $uniqueCode)
    {
        try {
            $collectionOrder = CollectionOrder::with([])->where('unique_code', $uniqueCode)->firstOrFail();
            $collectionOrderMaterials = DB::table('collection_order_entire_instances')->where('collection_order_unique_code', $uniqueCode)->get()->toArray();
            $excelUrl = '';
            if ($collectionOrder->type->value == 'MATERIAL') {
                $excelUrl = MaterialFacade::makeMaterialExcel($collectionOrderMaterials, '/collection/material/' . date('Y-m-d'), $uniqueCode, 'xlsx');
            }
            if ($collectionOrder->type->value == 'LOCATION') {
                $excelUrl = MaterialFacade::makeMaterialLocationExcel($collectionOrderMaterials, '/collection/location/' . date('Y-m-d'), $uniqueCode, 'xlsx');
            }
            DB::table('collection_orders')->where('unique_code', $uniqueCode)->update([
                'updated_at' => date('Y-m-d H:i:s'),
                'excel_url' => $excelUrl,
            ]);

            return JsonResponseFacade::data([], '生成Excel成功');
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 下载Excel
     * @param string $uniqueCode
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    final public function downloadExcel(string $uniqueCode)
    {
        try {
            $collectionOrder = CollectionOrder::with([])->where('unique_code', $uniqueCode)->where('excel_url', '<>', '')->firstOrFail();
            $excelUrl = storage_path($collectionOrder->excel_url);
            if (!is_file($excelUrl)) return back()->with('danger', '文件不存在');
            return response()->download($excelUrl);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '数据不存在');
        } catch (Exception $e) {
            return back()->with('danger', $e->getMessage() . $e->getLine() . $e->getFile());
        }
    }

    /**
     * 数据采集单器材列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function indexWithCollectOrderMaterial(Request $request)
    {
        try {
            $scene_workshops = DB::table('maintains as sc')
                ->where('sc.parent_unique_code', env('ORGANIZATION_CODE'))
                ->where('sc.type', 'SCENE_WORKSHOP')
                ->get();
            $stations = DB::table('maintains as s')
                ->where('s.type', 'STATION')
                ->get()
                ->groupBy('parent_unique_code');

            $unique_code = $request->get('unique_code', '');
            $collection_order = CollectionOrder::with([])->where('unique_code', $unique_code)->firstOrFail();
            $collection_order_entire_instances = CollectionOrderEntireInstance::with([
                'EntireInstance',
                'WithStation',
                'WithWorkshop'
            ])
                ->when(
                    request('station_unique_code'),
                    function ($query) {
                        $query->where('station_unique_code', request('station_unique_code'));
                    }
                )
                ->where('collection_order_unique_code', $unique_code)
                ->orderBy('id')
                ->paginate(50);

            $view_name = strtolower($collection_order->type->value);
            return view("CollectionOrder.Material.{$view_name}", [
                'collectionOrderMaterials' => $collection_order_entire_instances,
                'scene_workshops_as_json' => $scene_workshops->toJson(),
                'stations_as_json' => $stations->toJson(),
            ]);
        } catch (Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    final public function postMarkNeedDelete(Request $request)
    {
        try {
            $file = $request->file('file');
            if (!$file) return back()->with('danger', '上传失败');

            $current_row = 2;
            $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
                ->originRow($current_row)
                ->withSheetIndex(0);

            $errors = [];
            $error_rows_for_identity_code = [];
            $error_rows_for_serial_number = [];
            $edit_count = 0;

            // 读取拼装Excel数据
            $edit_data_identity_codes = collect([]);
            $edit_data_serial_numbers = collect([]);
            $row_by_code = collect([]);
            $edit_maintain_location_codes = [];
            foreach ($excel['success'] as $row_datum) {
                if (empty(array_filter($row_datum, function ($value) {
                    return !empty($value);
                }))) continue;

                try {
                    // list($code, $install_position_unique_code, $manual_install_position_unique_code) = $row_datum;
                    list($code, $install_position_unique_code) = $row_datum;
                    $code = trim(strval($code));
                    $install_position_unique_code = trim(strval($install_position_unique_code));
                } catch (Exception $e) {
                    $pattern = '/Undefined offset: /';
                    $offset = preg_replace($pattern, '', $e->getMessage());
                    $column_name = ExcelWriteHelper::int2Excel($offset);
                    throw new ExcelInException("读取：{$column_name}列失败。");
                }

                if (preg_match('/^Q(.){18}/i', $code)) {
                    $edit_data_identity_codes[$current_row] = ['code' => $code, 'maintain_location_code' => $install_position_unique_code];
                } else {
                    $edit_data_serial_numbers[$current_row] = ['code' => $code, 'maintain_location_code' => $install_position_unique_code];
                }
                $row_by_code[$code] = $current_row;

                $current_row++;
            }

            // 检查唯一编号是否存在
            $entire_instances = DB::table('entire_instances as ei')
                ->select(['identity_code'])
                ->whereIn('ei.identity_code', $edit_data_identity_codes->pluck('code')->toArray())
                ->get();
            $diff = [];
            $diff = array_diff($edit_data_identity_codes->pluck('code')->toArray(), $entire_instances->pluck('identity_code')->toArray());
            // 找到不存在的唯一编号
            if (!empty($diff)) {
                foreach ($diff as $identity_code) {
                    $error_rows_for_identity_code[] = $row_by_code->get($identity_code);
                    $errors[] = [
                        'error_position' => "第{$row_by_code->get($identity_code)}行",
                        'error_message' => "{$identity_code} 器材不存在",
                    ];
                }
            }

            // 检查所编号是否存在
            $entire_instances = DB::table('entire_instances as ei')
                ->selectRaw(implode(',', [
                    'count(ei.serial_number) as aggregate',
                    'ei.serial_number',
                ]))
                ->whereIn('ei.serial_number', $edit_data_serial_numbers->pluck('code')->toArray())
                ->groupBy(['ei.serial_number'])
                ->get();
            // if ($entire_instances->isEmpty()) throw new Exception('所有所编号均没有找到');

            // 找到不存在的所编号
            $diff = [];
            $diff = array_diff($edit_data_serial_numbers->pluck('code')->toArray(), $entire_instances->pluck('serial_number')->toArray());
            if (!empty($diff)) {
                foreach ($diff as $serial_number) {
                    $error_rows_for_serial_number[] = $row_by_code->get($serial_number);
                    $errors[] = [
                        'error_position' => "第{$row_by_code->get($serial_number)}行",
                        'error_message' => "{$serial_number} 器材不存在",
                    ];
                }
            }
            // 找到所编号重复的
            foreach ($entire_instances as $entire_instance) {
                if ($entire_instance->aggregate === 0) {
                    $error_rows_for_serial_number[] = $row_by_code->get($entire_instance->serial_number);
                    $errors[] = [
                        'error_position' => "第{$row_by_code->get($entire_instance->serial_number)}行",
                        'error_message' => "{$entire_instance->serial_number} 器材存在多台设备器材",
                    ];
                }
            }

            DB::beginTransaction();
            // 根据唯一编号修改
            foreach ($edit_data_identity_codes as $item) {
                DB::table('entire_instances as ei')
                    ->where('identity_code', $item['code'])
                    ->update([
                        'maintain_location_code' => $item['maintain_location_code'],
                        'need_delete' => true,
                    ]);
                $edit_count++;
            }

            // 根据所编号修改
            foreach ($edit_data_serial_numbers as $item) {
                DB::table('entire_instances as ei')
                    ->where('identity_code', $item['code'])
                    ->update([
                        'maintain_location_code' => $item['maintain_location_code'],
                        'need_delete' => true,
                    ]);
                $edit_count++;
            }

            // 保存错误信息
            if (!empty($errors)) {
                $session_id = session()->getId();
                $root_dir = storage_path('collectionOrder/upload/errorJson');
                if (!is_dir($root_dir)) FileSystem::init(__FILE__)->makeDir($root_dir);
                file_put_contents("$root_dir/{$session_id}.json", json_encode($errors));
            }
            DB::commit();

            if (empty($errors)) {
                $with_key = 'success';
                $with_value = "成功导入：{$edit_count}条。";
            } else {
                $with_key = 'warning';
                $with_value = "成功导入：{$edit_count}条，失败：" . count($errors) . "条。"
                    . '&nbsp;<a href="' . url('collectionOrder/downloadErrorExcel') . '" target="_blank">点击下载</a>';
            }

            return back()->with($with_key, $with_value);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            if (request()->ajax()) return JsonResponseFacade::errorException($e);
            CommonFacade::ddExceptionWithAppDebug($e);
            return back()->withInput()->with('danger', '意外错误');
        }
    }

    /**
     * 上传采集单修改器材上道位置信息（室内）
     */
    final public function postUploadInDevice(Request $request)
    {
        try {
            $file = $request->file('file');
            if (!$file) return back()->with('danger', '上传失败');

            $current_row = 2;
            DB::beginTransaction();

            $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
                ->originRow($current_row)
                ->withSheetIndex(0);

            $errors = [];
            $edit_count = 0;
            foreach ($excel['success'] as $row_datum) {
                if (empty(array_filter($row_datum, function ($value) {
                    return !empty($value);
                }))) continue;

                try {
                    // list($code, $install_position_unique_code, $manual_install_position_unique_code) = $row_datum;
                    list($code, $install_position_unique_code) = $row_datum;
                    $code = trim(strval($code));
                    $install_position_unique_code = trim(strval($install_position_unique_code));
                } catch (Exception $e) {
                    $pattern = '/Undefined offset: /';
                    $offset = preg_replace($pattern, '', $e->getMessage());
                    $column_name = ExcelWriteHelper::int2Excel($offset);
                    throw new ExcelInException("读取：{$column_name}列失败。");
                }

                if (preg_match('/^S.{13}/i', $code) || preg_match('/^Q.{18}/i', $code)) {
                    // 按照唯一编号进行搜索
                    $entire_instance = EntireInstance::with([])->where('identity_code', $code)->first();
                    if (!$entire_instance) {
                        $errors[] = [
                            'error_position' => "第{$current_row}行",
                            'error_message' => "{$code} 器材不存在",
                        ];
                        $current_row++;
                        continue;
                    }
                } else {
                    $entire_instances = EntireInstance::with([])->where('serial_number', $code)->get();
                    if ($entire_instances->isEmpty()) {
                        $errors[] = [
                            'error_position' => "第{$current_row}行",
                            'error_message' => "{$code} 器材不存在",
                        ];
                        $current_row++;
                        continue;
                    }
                    if ($entire_instances->count() > 1) {
                        $errors[] = [
                            'error_position' => "第{$current_row}行",
                            'error_message' => "{$code} 有多台重复的器材存在",
                        ];
                        $current_row++;
                        continue;
                    }
                    $entire_instance = $entire_instances->first();
                }

                $install_position = InstallPosition::with([
                    'WithInstallTier',
                    'WithInstallTier.WithInstallShelf',
                    'WithInstallTier.WithInstallShelf.WithInstallPlatoon',
                    'WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom',
                    'WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation',
                    'WithInstallTier.WithInstallShelf.WithInstallPlatoon.WithInstallRoom.WithStation.Parent',
                ])
                    ->where('unique_code', $install_position_unique_code)
                    ->first();
                if (!$install_position) {
                    $errors[] = [
                        'error_position' => "第{$current_row}行",
                        'error_message' => "位置不存在",
                    ];
                    $current_row++;
                    continue;
                }
                $edit_count++;


                $entire_instance->fill([
                    'maintain_location_code' => $install_position_unique_code,
                    'maintain_station_name' => $install_position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->name,
                    'maintain_workshop_name' => $install_position->WithInstallTier->WithInstallShelf->WithInstallPlatoon->WithInstallRoom->WithStation->Parent->name,
                    'status' => 'INSTALLED',
                ])->saveOrFail();
            }

            // 保存错误信息
            if ($errors) {
                $session_id = session()->getId();
                $root_dir = storage_path('collectionOrder/upload/errorJson');
                if (!is_dir($root_dir)) FileSystem::init(__FILE__)->makeDir($root_dir);
                file_put_contents("$root_dir/{$session_id}.json", json_encode($errors));
            }
            DB::commit();

            if (!$errors) {
                $with_key = 'success';
                $with_value = "成功导入：{$edit_count}条。";
            } else {
                $with_key = 'warning';
                $with_value = "成功导入：{$edit_count}条，失败：" . count($errors) . "条。"
                    . '&nbsp;<a href="' . url('collectionOrder/downloadErrorExcel') . '" target="_blank">点击下载</a>';
            }

            return back()->with($with_key, $with_value);
        } catch (ModelNotFoundException $e) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 上传室外上道采集位置单
     * @param Request $request
     * @return RedirectResponse
     * @throws ExcelInException
     * @throws Throwable
     * @throws \PHPExcel_Exception
     */
    final public function postUploadOutDevice(Request $request): RedirectResponse
    {
        // $excel_reader = ExcelReaderService::Request($request, "file")
        //     ->SetOriginRow(2)
        //     ->SetFinishColText("H")
        //     ->ReadBySheetIndex();
        // $excel_data = $excel_reader->GetData(["序号", "唯一编号", "种类", "类型", "型号", "所编号", "车站", "安装位置"]);
        // if ($excel_data->isEmpty()) return back()->with("danger", "Excel中没有数据");
        //
        // $edited_count = 0;
        // $excel_data->each(function ($excel_datum) use (&$edited_count) {
        //     if (preg_match('/^S.{13}/i', $code) || preg_match('/^Q.{18}/i', $code)) {
        //         // 按照唯一编号进行搜索
        //         $entire_instance = EntireInstance::with([])->where('identity_code', $code)->first();
        //         if (!$entire_instance) {
        //             $errors[] = [
        //                 'error_position' => "第{$current_row}行",
        //                 'error_message' => "{$code} 器材不存在",
        //             ];
        //             $current_row++;
        //             return null;
        //         }
        //     } else {
        //         $entire_instances = EntireInstance::with([])->where('serial_number', $code)->get();
        //         if ($entire_instances->isEmpty()) {
        //             $errors[] = [
        //                 'error_position' => "第{$current_row}行",
        //                 'error_message' => "{$code} 器材不存在",
        //             ];
        //             $current_row++;
        //             return null;
        //         }
        //         if ($entire_instances->count() > 1) {
        //             $errors[] = [
        //                 'error_position' => "第{$current_row}行",
        //                 'error_message' => "{$code} 有多台重复的器材存在",
        //             ];
        //             $current_row++;
        //             continue;
        //         }
        //         $entire_instance = $entire_instances->first();
        //     }
        // });


        $file = $request->file('file');
        if (!$file) return back()->with('danger', '上传失败');

        $current_row = 2;
        DB::beginTransaction();

        $excel = ExcelReadHelper::FROM_REQUEST($request, 'file')
            ->originRow($current_row)
            ->withSheetIndex(0);

        $errors = [];
        $edit_count = 0;

        foreach ($excel['success'] as $row_datum) {
            if (empty(array_filter($row_datum, function ($value) {
                return !empty($value);
            }))) continue;

            $update_data = [];

            try {
                list(
                    $num
                    , $code
                    , $category_name
                    , $entire_model_name
                    , $sub_model_name
                    , $o_serial_number
                    , $o_maintain_station_name
                    , $o_maintain_send_or_receive
                    , $o_last_installed_at
                    ) = $row_datum;

                $code = trim(strval($code));
                $o_serial_number = trim(strval($o_serial_number));
                $o_maintain_station_name = trim(strval($o_maintain_station_name));
                $o_maintain_send_or_receive = trim(strval($o_maintain_send_or_receive));

                // 验证车站
                $station = Maintain::with([])->where("type", "STATION")->where("name", $o_maintain_station_name)->first();
                if (!$station) throw new ValidateException("第{$current_row}行，车站不存在");

                // 验证上道日期
                $last_installed_at = null;
                if ($o_last_installed_at) {
                    try {
                        if (is_numeric($o_last_installed_at)) {
                            $last_installed_at = Carbon::parse(ExcelReadHelper::excelToDatetime($o_last_installed_at));
                        } else {
                            $last_installed_at = Carbon::parse($o_last_installed_at);
                        }
                    } catch (Exception $e) {
                        throw new ExcelInException("第{$current_row}行，日期格式错误：{$o_last_installed_at}");
                    }
                }

                $update_datum = [
                    'serial_number' => $o_serial_number
                    , 'maintain_send_or_receive' => $o_maintain_send_or_receive
                    , 'installed_at' => @$last_installed_at
                    , 'maintain_station_name' => @$station->name ?: ""
                    , "maintain_workshop_name" => @$station->Parent->name ?: ""
                ];
            } catch (Exception $e) {
                $pattern = '/Undefined offset: /';
                $offset = preg_replace($pattern, '', $e->getMessage());
                $column_name = ExcelWriteHelper::int2Excel($offset);
                throw new ExcelInException("读取：{$column_name}列失败");
            }

            if (preg_match('/^S.{13}/i', $code) || preg_match('/^Q.{18}/i', $code)) {
                // 按照唯一编号进行搜索
                $entire_instance = EntireInstance::with([])->where('identity_code', $code)->first();
                if (!$entire_instance) {
                    $errors[] = [
                        'error_position' => "第{$current_row}行",
                        'error_message' => "{$code} 器材不存在",
                    ];
                    $current_row++;
                    continue;
                }
            } else {
                $entire_instances = EntireInstance::with([])->where('serial_number', $code)->get();
                if ($entire_instances->isEmpty()) {
                    $errors[] = [
                        'error_position' => "第{$current_row}行",
                        'error_message' => "{$code} 器材不存在",
                    ];
                    $current_row++;
                    continue;
                }
                if ($entire_instances->count() > 1) {
                    $errors[] = [
                        'error_position' => "第{$current_row}行",
                        'error_message' => "{$code} 有多台重复的器材存在",
                    ];
                    $current_row++;
                    continue;
                }
                $entire_instance = $entire_instances->first();
            }

            // 修改设备器材信息
            $update_datum = array_filter($update_datum, function ($val) {
                return !empty($val);
            });
            $entire_instance->fill(array_merge($update_datum))->saveOrFail();

            $edit_count++;
        }

        // 保存错误信息
        if ($errors) {
            $session_id = session()->getId();
            $root_dir = storage_path('collectionOrder/upload/errorJson');
            if (!is_dir($root_dir)) FileSystem::init(__FILE__)->makeDir($root_dir);
            file_put_contents("$root_dir/{$session_id}.json", json_encode($errors));
        }
        DB::commit();

        if (!$errors) {
            $with_key = 'success';
            $with_value = "成功导入：{$edit_count}条。";
        } else {
            $with_key = 'warning';
            $with_value = "成功导入：{$edit_count}条，失败：" . count($errors) . "条。"
                . '&nbsp;<a href="' . url('collectionOrder/downloadErrorExcel') . '" target="_blank">点击下载</a>';
        }

        return back()->with($with_key, $with_value);
    }

    /*
     * 下载错误报告excel
     */
    final public function getDownloadErrorExcel()
    {
        $session_id = session()->getId();
        $filename = storage_path("collectionOrder/upload/errorJson/{$session_id}.json");
        if (!file_exists($filename)) return back()->with('danger', '错误报告excel不存在');

        $errors = json_decode(file_get_contents($filename), true);
        ExcelWriteHelper::download(
            function ($excel) use ($errors) {
                $excel_row = 2;
                $excel->setActiveSheetIndex(0);
                $current_sheet = $excel->getActiveSheet();

                // 首行
                $current_sheet->setCellValueExplicit("A1", '错误位置');
                $current_sheet->setCellValueExplicit("B1", '错误描述');

                foreach ($errors as $error) {
                    $current_sheet->setCellValueExplicit("A{$excel_row}", $error['error_position']);
                    $current_sheet->setCellValueExplicit("B{$excel_row}", $error['error_message']);
                    $excel_row++;
                }

                return $excel;
            },
            '设备采集错误报告'
        );
        return null;
    }

}
