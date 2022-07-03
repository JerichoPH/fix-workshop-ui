<?php

namespace App\Http\Controllers\Entire;

use App\Http\Controllers\Controller;
use App\Model\EntireModelIdCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ModelIdCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $type = request('type');
        return Response::json(EntireModelIdCode::where(
            'category_unique_code',
            request('category_unique_code')
        )
            ->where(
                'entire_model_unique_code',
                request('entire_model_unique_code')
            )
            ->get()
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (request()->ajax()) return view($this->view('create_ajax'));
    }

    private function view($viewName = null)
    {
        $viewName = $viewName ?: request()->route()->getActionMethod();
        return "Entire.ModelIdCode.{$viewName}";
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        try {
            $entireModelIdCode = new EntireModelIdCode;
            $entireModelIdCode->fill([
                'category_unique_code' => $request->get('category_unique_code'),
                'entire_model_unique_code' => $request->get('entire_model_unique_code'),
                'code' => $request->get('code'),
            ])
                ->saveOrFail();

            return Response::make('添加成功');
        } catch (\Exception $exception) {
            return Response::make('意外错误', 500);
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
