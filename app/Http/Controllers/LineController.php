<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use App\Model\Line;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Throwable;

class LineController extends Controller
{
    /**
     * 线别列表
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function index()
    {
        $lines = (new Line)->ReadMany(["from_line_manage"]);
        if (request("from_line_manage") == 1) {
            $lines = (new Line)->ReadMany(["from_line_manage"])->withoutGlobalScope("is_show");
        } else {
            $lines = (new Line)->ReadMany(["from_line_manage"]);
        }
        if (request()->ajax()) {
            return JsonResponseFacade::dict(["lines" => $lines->get(),]);
        } else {
            return view("Line.index", ["lines" => $lines->paginate(),]);
        }
    }

    /**
     * 新建页面
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function create()
    {
        $lineUniqueCode = DB::table('lines')->orderByDesc('unique_code')->value('unique_code');
        if ($lineUniqueCode) {
            $lineUniqueCode_4 = str_pad(substr($lineUniqueCode, -4) + 1, 4, 0, STR_PAD_LEFT);
            $lineUniqueCode = 'E' . $lineUniqueCode_4;
        } else {
            $lineUniqueCode = 'E0001';
        }
        return view('Line.create', [
            'lineUniqueCode' => $lineUniqueCode
        ]);
    }

    /**
     * 新建操作
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $name = $request->get('name');
            $unique_code = $request->get('unique_code');
            $created_at = date('Y-m-d H:i:s');
            if (!$name) return Response::make('请填写名称', 422);
            if (!$unique_code) return Response::make('请填写线别编码', 422);
            if (DB::table('lines')->where('name', $name)->exists()) return Response::make('名称重复', 422);
            if (DB::table('lines')->where('unique_code', $unique_code)->exists()) return Response::make('线别编码重复', 422);
            DB::table('lines')->insert([
                'name' => $name,
                'unique_code' => $unique_code,
                'created_at' => $created_at
            ]);
            return Response::make('新建成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('意外错误', 500);
        }
    }

    /**
     * 编辑页面
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function edit($id)
    {
        $line = DB::table('lines')->where('id', $id)->get()->toArray();
        return view('Line.edit', [
            'line' => $line
        ]);
    }

    /**
     * 编辑操作
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     * @throws Throwable
     */
    public function update(Request $request, $id)
    {
        try {
            $line = Line::withoutGlobalScope("is_show")->with([])->where("id", $id)->firstOrFail();
            $line->fill(["is_show" => $request->get("is_show") ?? false])->saveOrFail();
            return Response::make('编辑成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('线别不存在', 404);
        } catch (Exception $exception) {
            return Response::make('意外错误', 500);
        }
    }

    /**
     * 删除
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            if (DB::table('lines_maintains')->where('lines_id', $id)->exists()) return Response::make('已绑定车站无法删除');
            DB::table('lines')->where('id', $id)->delete();
            return Response::make('删除成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('意外错误', 500);
        }
    }
}
