<?php

namespace App\Http\Controllers\Part;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartModelStoreRequest;
use App\Http\Requests\PartModelUpdateRequest;
use App\Http\Requests\V1\PartModelRequest;
use App\Model\PartModel;
use App\Model\PivotEntireModelAndPartModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\HttpResponseHelper;
use Jericho\TextHelper;

class ModelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return PartModel[]|\Illuminate\Contracts\View\Factory|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            $type = request('type');
            switch ($type) {
                case 'entire_model_unique_code':
                    return Response::json(PivotEntireModelAndPartModel::with(['PartModel'])->where('entire_model_unique_code', request(request('type')))->get());
                    break;
                case 'category_unique_code':
                    return Response::json(PartModel::where(request('type'), request(request('type')))->get());
                    break;
                case 'partModeEdit':
                    return Response::json(PartModel::where('entire_model_unique_code', request('entire_model_unique_code'))->where('category_unique_code', request('category_unique_code'))->get());
                case 'bindingFixWorkflowProcess':
                    session()->push('bindingFixWorkflowProcess', ['categoryUniqueCode' => request('categoryUniqueCode')]);
                    return PartModel::with(['Category'])->whereHas('Category', function ($category) {
                        $category->where('unique_code', request('categoryUniqueCode'));
                    })
                        ->get();
                    break;
                default:
                    return Response::json(PartModel::where($type, request($type))->orderByDesc('id')->pluck('name', 'unique_code'));
                    break;
            }
        } else {
            if(request('download')==1){
                if (request('download') == 1) {
                    $part_models = DB::table('part_models as pm')
                        ->select([
                            'pm.name as pm_name',
                            'pm.unique_code as pm_unique_code',
                            'em.name as em_name',
                            'em.unique_code as em_unique_code',
                            'c.name as c_name',
                            'c.unique_code as c_unique_code',
                        ])
                        ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                        ->join(DB::raw('categories c'), 'c.unique_code', '=', 'em.category_unique_code')
                        ->get();

                    ExcelWriteHelper::download(function ($excel) use ($part_models) {
                        $excel->setActiveSheetIndex(0);
                        $sheet = $excel->getActiveSheet();

                        $sheet->setCellValue('A1', '种类名称');
                        $sheet->setCellValue('B1', '种类代码');
                        $sheet->setCellValue('C1', '类型名称');
                        $sheet->setCellValue('D1', '类型代码');
                        $sheet->setCellValue('E1', '型号名称');
                        $sheet->setCellValue('F1', '型号代码');

                        $row = 1;
                        foreach ($part_models as $part_model) {
                            $row++;
                            $sheet->setCellValue("A{$row}", $part_model->c_name);
                            $sheet->setCellValue("B{$row}", $part_model->c_unique_code);
                            $sheet->setCellValue("C{$row}", $part_model->em_name);
                            $sheet->setCellValue("D{$row}", $part_model->em_unique_code);
                            $sheet->setCellValue("E{$row}", $part_model->pm_name);
                            $sheet->setCellValue("F{$row}", $part_model->pm_unique_code);
                        }

                        return $excel;
                    }, '型号');
                }
            }

            $partModels = PartModel::with(['Category'])->orderByDesc('id')->paginate();
            return view($this->view())
                ->with('partModels', $partModels);
        }
    }

    public function view(string $viewName = null)
    {
        $viewName = $viewName ?: request()->route()->getActionMethod();
        return "Part.Model.{$viewName}";
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $categories = DB::table("categories as c")->where("c.deleted_at", null)->pluck("name", "unique_code");
        $part_categories = DB::table("part_categories as pc")
            ->join(DB::raw("categories c"), "c.unique_code", "=", "pc.category_unique_code")
            ->where("pc.deleted_at", null)
            ->where("c.deleted_at", null)
            ->get(["c.name as c_name", "pc.name", "pc.id"]);
        $part_categories2 = [];
        foreach ($part_categories as $part_category) $part_categories2[$part_category->c_name][$part_category->id] = $part_category->name;
        $entire_models = DB::table("entire_models as em")
            ->join(DB::raw("categories c"), "c.unique_code", "=", "em.category_unique_code")
            ->where("em.deleted_at", null)
            ->where("c.deleted_at", null)
            ->get(["c.name as c_name", "em.name", "em.unique_code"]);
        $entire_models2 = [];
        foreach ($entire_models as $entire_model) $entire_models2[$entire_model->c_name][$entire_model->unique_code] = $entire_model->name;

        if (request()->ajax()) return view($this->view('create_ajax'))->with('categories', $categories);
        return view("Part.Model.create", [
            "categories" => TextHelper::toJson($categories),
            "part_categories" => TextHelper::toJson($part_categories2),
            "entire_models" => TextHelper::toJson($entire_models2),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        try {
            $v = Validator::make($request->all(), PartModelStoreRequest::$RULES, PartModelStoreRequest::$MESSAGES);
            if ($v->fails()) return response()->json(['message' => $v->errors()->first()], 422);

            $partModel = new PartModel;
            $partModel->fill($request->all())->saveOrFail();

            return response()->json(['message' => '新建成功']);
        } catch (ModelNotFoundException $exception) {
            return response()->json(['message' => '数据不存在'], 404);
        } catch (\Exception $exception) {
            $msg = $exception->getMessage();
            $line = $exception->getLine();
            $file = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return response()->json(['message' => $msg], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param string $unique_code
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function edit(string $unique_code)
    {
        try {
            $categories = DB::table("categories as c")->where("c.deleted_at", null)->pluck("name", "unique_code");
            $part_categories = DB::table("part_categories as pc")
                ->join(DB::raw("categories c"), "c.unique_code", "=", "pc.category_unique_code")
                ->where("pc.deleted_at", null)
                ->where("c.deleted_at", null)
                ->get(["c.name as c_name", "pc.name", "pc.id"]);
            $part_categories2 = [];
            foreach ($part_categories as $part_category) $part_categories2[$part_category->c_name][$part_category->id] = $part_category->name;
            $entire_models = DB::table("entire_models as em")
                ->join(DB::raw("categories c"), "c.unique_code", "=", "em.category_unique_code")
                ->where("em.deleted_at", null)
                ->where("c.deleted_at", null)
                ->get(["c.name as c_name", "em.name", "em.unique_code"]);
            $entire_models2 = [];
            foreach ($entire_models as $entire_model) $entire_models2[$entire_model->c_name][$entire_model->unique_code] = $entire_model->name;

            $part_model = PartModel::with([])->where("unique_code", $unique_code)->first();
            return view("Part.Model.edit", [
                "categories" => TextHelper::toJson($categories),
                "part_categories" => TextHelper::toJson($part_categories2),
                "entire_models" => TextHelper::toJson($entire_models2),
                'part_model' => $part_model,
            ]);

        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return back()->with('danger', '意外错误' . $exceptionFile . $exceptionLine);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param string $unique_code
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     * @throws \Throwable
     */
    public function update(Request $request, string $unique_code)
    {
        try {
            $v = Validator::make($request->all(), PartModelUpdateRequest::$RULES, PartModelUpdateRequest::$MESSAGES);
            if ($v->fails()) return HttpResponseHelper::errorValidate($v->errors()->first());

            $repeat_name = DB::table("part_models as pm")->where("pm.deleted_at", null)->where("pm.name", $request->get("name"))->where("pm.unique_code", "<>", $unique_code)->first();
            if ($repeat_name) return HttpResponseHelper::errorForbidden("名称重复");

            $part_model = PartModel::with([])->where("unique_code", $unique_code)->first();
            $part_model->fill($request->all())->saveOrFail();

            return Response::make('编辑成功');
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty("数据不存在");
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return HttpResponseHelper::errorForbidden($exceptionMessage);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $partModel = PartModel::with([])->findOrFail($id);
            $partModel->delete();
            if (!$partModel->trashed()) return Response::make('删除失败', 500);

            return Response::make('删除成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            $exceptionMessage = $exception->getMessage();
            $exceptionLine = $exception->getLine();
            $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            // return back()->withInput()->with('danger',"{$exceptionMessage}「{$exceptionLine}:{$exceptionFile}」");
            return Response::make('意外错误', 500);
        }
    }
}
