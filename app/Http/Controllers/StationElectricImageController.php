<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Model\Maintain;
use App\Model\StationElectricImage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\FileSystem;

class StationElectricImageController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            $stations = Maintain::with(['ElectricImages'])->where('type', 'STATION')->get();
            return view('StationElectricImage.index', [
                'stations' => $stations,
            ]);
        } catch (\Exception $e) {
            return redirect('/')->with('danger', '意外错误');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function create(): \Illuminate\Http\Response
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param string $maintain_station_unique_code
     * @return \Illuminate\Http\RedirectResponse
     */
    final public function store(Request $request, string $maintain_station_unique_code): \Illuminate\Http\RedirectResponse
    {
        try {
            $station = Maintain::with([])->where('unique_code', $maintain_station_unique_code)->firstOrFail();

            $uploaded_count = 0;

            if (!$request->file('electric_images')) return back()->with('danger', '上传失败');
            foreach ($request->file('electric_images') as $electric_image) {
                $original_filename = $electric_image->getClientOriginalName();  // 原文件名
                $original_extension = $electric_image->getClientOriginalExtension();  // 原文件扩展

                $root_dir = "uploads/stationElectricImage/{$maintain_station_unique_code}";
                if (!is_file(public_path($root_dir))) FileSystem::init(__FILE__)->makeDir(public_path($root_dir));

                $filename = "{$root_dir}/{$original_filename}";
                file_put_contents(public_path($filename), $electric_image);

                $station_electric_image = StationElectricImage::with([])->create([
                    'original_filename' => $original_filename,
                    'original_extension' => $original_extension,
                    'filename' => $filename,
                    'maintain_station_unique_code' => $maintain_station_unique_code,
                ]);

                $uploaded_count++;
            }

            return back()->with('success', "上传成功，共计：{$uploaded_count}。");
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '车站不存在');
        } catch (\Exception $e) {
            dd(date('Y-m-d H:i:s'), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine());
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Display the specified resource.
     * @param string $maintain_station_unique_code
     * @return JsonResponse
     */
    final public function show(string $maintain_station_unique_code): JsonResponse
    {
        try {
            $station_electric_images = StationElectricImage::with([])->where('maintain_station_unique_code', $maintain_station_unique_code)->get();

            return JsonResponseFacade::data(['station_electric_images' => $station_electric_images]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty('该车站下没有电子图纸');
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function edit($id): \Illuminate\Http\Response
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
    final public function update(Request $request, $id): \Illuminate\Http\Response
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    final public function destroy(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            // 删除数据库
            $station_electric_image = StationElectricImage::with([])->where('id', $id)->firstOrFail();
            $station_electric_image->forceDelete();

            // 删除文件
            $fs = FileSystem::init(public_path())->join($station_electric_image->filename);
            if (file_exists($fs->current())) unlink($fs->current());
            DB::commit();

            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return JsonResponseFacade::errorException($e);
        }
    }
}
