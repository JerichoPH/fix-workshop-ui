<?php

namespace App\Http\Controllers;

use App\Exceptions\FuncNotFoundException;
use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Model\EntireModel;
use App\Model\EntireModelImage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Jericho\FileSystem;
use Throwable;

class EntireModelImageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function index()
    {
        if (request()->ajax()) {
            $entire_model_images = ModelBuilderFacade::init(request(), EntireModelImage::with([]))->all();
            return JsonResponseFacade::dict(['entire_model_images' => $entire_model_images]);
        }
        return null;
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
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function store(Request $request)
    {
        try {
            if (!$request->hasFile('entire_model_images')) return back()->with('danger', '上传失败');
            if (!$request->get('entire_model_unique_code')) return back()->with('danger', '型号参数错误');

            $entire_model = EntireModel::with([])->where('unique_code', $request->get('entire_model_unique_code'))->first();
            if (!$entire_model) return back()->with('danger', '没有找到对应的型号');

            $dest_dir = 'entireModelImages';
            if (!Storage::disk('public')->exists($dest_dir)) Storage::disk('public')->makeDirectory($dest_dir);

            $upload_count = 0;

            $last = EntireModelImage::getLastFilenameByEntireModelUniqueCode($entire_model->unique_code);
            foreach ($request->file('entire_model_images') as $file) {
                $original_extension = $file->getClientOriginalExtension();
                $original_filename = $file->getClientOriginalName();

                $filename = $entire_model->unique_code . str_pad($last += 1, 5, '0', STR_PAD_LEFT);
                if (!in_array(strtoupper($file->getClientOriginalExtension()), ['JPG', 'JPEG', 'PNG',])) return back()->with('danger', '图片格式错误，只支持：JPG、PNG');
                $file->move(storage_path('app/public/entireModelImages'), "{$filename}.{$original_extension}");

                EntireModelImage::with([])->create([
                    'original_filename' => $original_filename,
                    'original_extension' => $original_extension,
                    'filename' => "{$filename}.{$original_extension}",
                    'url' => "{$filename}.{$original_extension}",
                    'entire_model_unique_code' => $entire_model->unique_code,
                ]);
                $upload_count++;
            }

            return back()->with('success', "成功上传：{$upload_count}张");
        } catch (FuncNotFoundException $e) {
            return back()->withInput()->with('danger', $e->getMessage());
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
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
            return JsonResponseFacade::dict(['entire_model_image' => EntireModelImage::with([])->where('id', $id)->firstOrFail()]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
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
            $entire_model_image = EntireModelImage::with([])->where('id', $id)->firstOrFail();

            $filename = storage_path($entire_model_image->filename);
            if (!file_exists($filename)) return JsonResponseFacade::errorEmpty('文件不存在');

            unlink($filename);
            $entire_model_image->delete();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('没有找到图片');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }
}
