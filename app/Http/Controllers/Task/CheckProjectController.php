<?php

namespace App\Http\Controllers\Task;

use App\Facades\JsonResponseFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\CheckProjectStoreRequest;
use App\Http\Requests\Task\CheckProjectUpdateRequest;
use App\Model\CheckProject;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\TextHelper;
use Jericho\ValidateHelper;

class CheckProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function index()
    {
        try {
            $checkProjects = CheckProject::with([])->paginate();

            return view('Task.CheckProject.index', [
                'checkProjects' => $checkProjects
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function create()
    {
        try {
            return view('Task.CheckProject.create', [
                'type' => CheckProject::$TYPE
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return mixed
     * @throws \Throwable
     */
    final public function store(Request $request)
    {
        try {
            $_POST['type'] = $request->get('type', 1);
            $v = ValidateHelper::firstErrorByRequest($request, new CheckProjectStoreRequest());
            if ($v !== true) return JsonResponseFacade::errorValidate($v);
            $checkProject = new CheckProject();
            $req = $request->all();
            $req['name'] = TextHelper::strip($req['name']);
            $checkProject->fill($req)->saveOrFail();
            return JsonResponseFacade::created();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function edit($id)
    {
        try {
            $checkProject = CheckProject::with([])->where('id', $id)->firstOrFail();

            return view('Task.CheckProject.edit', [
                'checkProject' => $checkProject,
                'type' => CheckProject::$TYPE
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            return back()->with('danger', $exception->getMessage() . $exception->getLine() . $exception->getFile());
        }
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws \Throwable
     */
    final public function update(Request $request, $id)
    {
        try {
            $checkProject = CheckProject::with([])->where('id', $id)->firstOrFail();
            $_POST['type'] = $request->get('type', 1);
            $_POST['id'] = $id;
            $v = ValidateHelper::firstErrorByRequest($request, new CheckProjectUpdateRequest());
            if ($v !== true) return JsonResponseFacade::errorForbidden($v);
            $req = $request->all();
            $req['name'] = TextHelper::strip($req['name']);
            $checkProject->fill($req)->saveOrFail();

            return JsonResponseFacade::updated();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }


    /**
     * Remove the specified resource from storage.
     * @param $id
     * @return mixed
     */
    final public function destroy($id)
    {
        try {
            $checkProject = CheckProject::with([])->where('id', $id)->firstOrFail();
            $checkProject->delete();
            return JsonResponseFacade::deleted();
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 根据类型获取列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getProject(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', '');
            $checkProject = DB::table('check_projects')
                ->when(
                    !empty($type),
                    function ($query) use ($type) {
                        return $query->where('type', $type);
                    }
                )->select('id', 'name')->get()->toArray();
            return JsonResponseFacade::data($checkProject);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }
}
