<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use App\Model\SourceName;
use App\Validations\Web\SourceNameStoreValidation;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Throwable;

class SourceNameController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Factory|Application|JsonResponse|View
     */
    final public function index()
    {
        return request()->ajax()
            ? JsonResponseFacade::dict([
                'source_names' => (new SourceName)
                    ->ReadMany(['name',])
                    ->when(request('name'), function ($query, $name) {
                        $query->where('name', 'like', "%$name%");
                    })
                    ->get(),
            ])
            : view('SourceName.index');
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
        $validation = (new SourceNameStoreValidation($request));
        $v = $validation->check();
        if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

        $validated = $validation->validated();

        $source_name = (new SourceName)->create([
            'source_type' => $validated->get('source_type'),
            'name' => $validated->get('name'),
        ]);

        return JsonResponseFacade::created(['source_name' => $source_name,]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    final public function show(int $id): JsonResponse
    {
        $source_name = SourceName::with([])->where('id', $id)->firstOrFail();

        return JsonResponseFacade::dict(['source_name' => $source_name,]);
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
     * @throws Throwable
     */
    final public function update(Request $request, int $id): JsonResponse
    {
        $validation = (new SourceNameStoreValidation($request));
        $v = $validation->check()->after(function ($validator) use ($id, $request) {
            if (
            SourceName::with([])
                ->where('id', '<>', $id)
                ->where('source_type', $request->get('source_type'))
                ->where('name', $request->get('name'))
                ->exists()
            ) {
                $validator->errors()->add('name', '来源名称在当前来源类型下重复');
            }
        });
        if ($v->fails()) return JsonResponseFacade::errorValidate($v->errors()->first());

        $validated = $validation->validated();

        $source_name = SourceName::with([])->where('id', $id)->where('id', $id)->firstOrFail();

        $source_name->fill($validated->toArray())->saveOrFail();
        EntireInstance::with([])->where('source_name', $source_name->name)->update(['source_name' => $validated->get('name'),]);

        return JsonResponseFacade::updated();
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return JsonResponse
     */
    final public function destroy(int $id): JsonResponse
    {
        $source_name = SourceName::with([])->where('id', $id)->firstOrFail();

        EntireInstance::with([])->where('source_name', $source_name->name)->update(['source_name' => '',]);
        $source_name->delete();

        return JsonResponseFacade::deleted();
    }
}
