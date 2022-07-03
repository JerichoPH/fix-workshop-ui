<?php

namespace App\Http\Controllers\Part;

use App\Http\Controllers\Controller;
use App\Model\Category;
use App\Model\EntireModel;
use App\Model\PartCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\HttpResponseHelper;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            $part_categories = PartCategory::with(['Category', 'EntireModel', 'EntireModel.Category'])->orderByDesc('id')->paginate();

            return view('Part.Category.index', ['part_categories' => $part_categories]);
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function create()
    {
        $categories_S = Category::with([])->where('unique_code', 'like', 'S%')->get();
        $categories_Q = Category::with([])->where('unique_code', 'like', 'Q%')->get();
        $entire_models = EntireModel::with([])
            ->where('is_sub_model', false)
            ->whereIn('category_unique_code',$categories_Q->pluck('unique_code'))
            ->get()
            ->groupBy('category_unique_code');

        return view("Part.Category.create", [
            "categories_S" => $categories_S,
            'categories_Q_as_json' => $categories_Q->toJson(),
            'entire_models_as_json' => $entire_models->toJson(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function store(Request $request)
    {
        if (!$request->get('name')) return HttpResponseHelper::errorForbidden('名称不能为空');
        $repeat = DB::table("part_categories as pc")
            ->where("pc.deleted_at", null)
            ->where("pc.name", $request->get("name"))
            ->where('pc.category_unique_code', $request->get('category_unique_code'))
            ->first();
        if ($repeat) return HttpResponseHelper::errorForbidden("名称重复");

        DB::table("part_categories")->insert([
            "created_at" => date("Y-m-d H:i:s"),
            "updated_at" => date("Y-m-d H:i:s"),
            "name" => $request->get("name"),
            "category_unique_code" => $request->get("category_unique_code"),
            "entire_model_unique_code"=>$request->get("entire_model_unique_code"),
            "is_main" => $request->get("is_main"),
        ]);

        return HttpResponseHelper::created("新建成功");
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function edit(int $id)
    {
        try {
            $categories_S = Category::with([])->where('unique_code', 'like', 'S%')->get();
            $categories_Q = Category::with([])->where('unique_code', 'like', 'Q%')->get();
            $entire_models = EntireModel::with([])
                ->where('is_sub_model', false)
                ->whereIn('category_unique_code',$categories_Q->pluck('unique_code'))
                ->get()
                ->groupBy('category_unique_code');

            $part_category = PartCategory::with([])->where('id',$id)->firstOrFail();

            return view("Part.Category.edit", [
                "part_category" => $part_category,
                'categories_S' => $categories_S,
                'categories_Q_as_json' => $categories_Q->toJson(),
                'entire_models_as_json' => $entire_models->toJson(),
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
            return back()->with('danger', '意外错误');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function update(Request $request, int $id)
    {
        $repeat = DB::table("part_categories as pc")->where("pc.deleted_at", null)->where("name", $request->get("name"))->where("pc.id", "<>", $id)->first();
        if ($repeat) return HttpResponseHelper::errorForbidden("名称重复");
        DB::table("part_categories as pc")->where("pc.id", $id)->update($request->all());
        return HttpResponseHelper::created("修改成功");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function destroy($id)
    {
        DB::table("part_categories as pc")->where("pc.deleted_at", null)->where("pc.id", $id)->update(["deleted_at" => date("Y-m-d H:i:s")]);
        return HttpResponseHelper::created("删除成功");
    }
}
