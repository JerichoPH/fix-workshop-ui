<?php

namespace App\Http\Controllers\RepairBase;

use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Facades\TextFacade;
use App\Model\BreakdownReportFile;
use Faker\Provider\Text;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Jericho\FileSystem;

class BreakdownReportFileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function index()
    {
        try {
            $breakdown_report_files = ModelBuilderFacade::init(
                request(),
                BreakdownReportFile::with([])
            )
                ->all();

            return JsonResponseFacade::data(['breakdown_report_files' => $breakdown_report_files]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function store(Request $request)
    {
        try {
            $root_dir = storage_path('breakdownReportFiles');
            if (!is_dir($root_dir)) FileSystem::init($root_dir)->makeDir($root_dir);

            DB::beginTransaction();
            if (!$request->file('file')) return back()->with('danger','上传文件失败');

            foreach ($request->file('file') as $file) {
                $repeat = BreakdownReportFile::with([])->where('filename', $file->getClientOriginalName())->first();
                if ($repeat) {
                    // 已经存在，覆盖原文件
                    $new_filename = date('YmdHis') . TextFacade::rand('Admix', 16) . '.' . $file->getClientOriginalExtension();
                    $file->move($root_dir, $new_filename);

                    // 保存到数据
                    $old_filename = $repeat->filename;
                    $repeat->fill(['filename' => $new_filename, 'ex_name' => $file->getClientOriginalExtension()])->saveOrFail();

                    // 删除旧文件
                    unlink("{$root_dir}/{$old_filename}");
                } else {
                    // 保存文件
                    $new_filename = date('YmdHis') . TextFacade::rand('Admix', 16) . '.' . $file->getClientOriginalExtension();
                    $file->move($root_dir, $new_filename);

                    BreakdownReportFile::with([])->create([
                        'filename' => $new_filename,
                        'source_filename' => $file->getClientOriginalName(),
                        'ex_name' => $file->getClientOriginalExtension(),
                        'breakdown_order_entire_instance_id' => $request->get('breakdown_order_entire_instance_id'),
                    ]);
                }
            }
            DB::commit();

            return back()->with('success', '上传成功');
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '没有找到故障设备');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
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
        try {
            $breakdown_report_file = ModelBuilderFacade::init(
                request(),
                BreakdownReportFile::with([])
            )
                ->extension(function ($builder) use ($id) {
                    return $builder->where('id', $id);
                })
                ->firstOrFail();

            return JsonResponseFacade::data(['breakdown_report_file' => $breakdown_report_file]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function edit($id)
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
    final public function update(Request $request, $id)
    {
        //
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
            $root_dir = storage_path('breakdownReportFiles');
            if (!is_dir($root_dir)) FileSystem::init('')->makeDir($root_dir);

            $breakdown_report_file = BreakdownReportFile::with([])->where('id', $id)->firstOrFail();
            $filename = "{$root_dir}/{$breakdown_report_file->filename}";
            if (is_file($filename)) @unlink($filename);  // 如果文件存在则删除文件

            $breakdown_report_file->forceDelete();
            $breakdown_report_files = BreakdownReportFile::with([])->where('breakdown_order_entire_instance_id', $breakdown_report_file->breakdown_order_entire_instance_id)->get();

            return JsonResponseFacade::deleted([
                'breakdown_report_files' => $breakdown_report_files,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 下载文件
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    final public function getDownload(int $id)
    {
        try {
            $root_dir = storage_path('breakdownReportFiles');
            if (!is_dir($root_dir)) FileSystem::init('')->makeDir($root_dir);

            $breakdown_report_file = BreakdownReportFile::with([])->where('id', $id)->firstOrFail();
            $filename = "{$root_dir}/{$breakdown_report_file->filename}";
            if (!is_file($filename)) return JsonResponseFacade::errorEmpty('文件已被删除，您可以删除此条记录并请重新上传');

            return response()->download($filename, $breakdown_report_file->source_filename);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Throwable $e) {
            return JsonResponseFacade::errorException($e);
        }
    }
}
