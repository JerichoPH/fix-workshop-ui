<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Model\MaterialType;
use App\Validations\Web\MaterialTypeStoreValidation;
use App\Validations\Web\MaterialTypeUpdateValidation;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Throwable;

class MaterialTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Factory|Application|View
     */
    final public function index()
    {
        return request()->ajax()
            ? JsonResponseFacade::dict(['material_types' => (new MaterialType)->ReadMany()->get(),])
            : view('MaterialType.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    final public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    final public function store(Request $request): JsonResponse
    {
        $validation = (new MaterialTypeStoreValidation($request));
        $v = $validation->check();
        if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

        $validated = $validation->validated();

        $material_type = MaterialType::with([])->create($validated->merge(['creator_id' => session('account.id'),])->toArray());

        return JsonResponseFacade::created(['material_type' => $material_type,]);
    }

    /**
     * Display the specified resource.
     * @param int $id
     * @return JsonResponse
     */
    final public function show(int $id): JsonResponse
    {
        $material_type = MaterialType::with(['Creator',])->where('id', $id)->firstOrFail();

        return JsonResponseFacade::dict(['material_type' => $material_type,]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    final public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    final public function update(Request $request, int $id): JsonResponse
    {
        $validation = (new MaterialTypeUpdateValidation($request));
        $v = $validation->check()->after(function ($validator) use ($request, $id) {
            if (
            MaterialType::with([])
                ->where('id', '<>', $id)
                ->where('identity_code', $request->get('identity_code'))
                ->exists()
            )
                $validator->errors()->add('name', '材料类型编码重复');

            if (
            MaterialType::with([])
                ->where('id', '<>', $id)
                ->where('name', $request->get('name'))
                ->exists()
            )
                $validator->errors()->add('name', '材料类型名称重复');
        });
        if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

        $validated = $validation->validated();

        MaterialType::with([])->where('id', $id)->update($validated->toArray());

        return JsonResponseFacade::updated();
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return JsonResponse
     */
    final public function destroy(int $id): JsonResponse
    {
        $material_type = MaterialType::with([])->where('id', $id)->firstOrFail();
        $material_type->delete();

        return JsonResponseFacade::deleted();
    }
}
