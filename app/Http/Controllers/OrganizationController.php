<?php

namespace App\Http\Controllers;

use App\Facades\OrganizationLevelFacade;
use App\Http\Requests\V1\OrganizationRequest;
use App\Model\Organization;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Jericho\ValidateHelper;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $currentOrganizationId = Session::get('account.organization_id');
        $organization = Organization::find($currentOrganizationId);
        $subOrganizationIds = OrganizationLevelFacade::getDeep($currentOrganizationId);
        $subOrganizations = Organization::with(['parent'])->whereIn('id', $subOrganizationIds)->orderByDesc('id')->paginate();
        return Response::view('Organization.index', ['organization' => $organization, 'subOrganizations' => $subOrganizations]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $currentOrganizationId = Session::get('account.organization_id');
        $organization = Organization::find($currentOrganizationId);
        $subOrganizationIds = OrganizationLevelFacade::getDeep($currentOrganizationId);
        $subOrganizations = Organization::whereIn('id', $subOrganizationIds)->orderByDesc('id')->get();
        return Response::view('Organization.create', ['organization' => $organization, 'subOrganizations' => $subOrganizations]);
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
            $v = ValidateHelper::firstErrorByRequest($request, new OrganizationRequest);
            if ($v !== true) return Response::make($v, 422);

            $organization = new Organization;

            $req = $request->all();
            if ($request->get('parent_id')) {
                $parent = Organization::where('id', $request->get('parent_id'))->first();
                if (!$parent) return Response::make('?????????????????????', 422);
                @$req['level'] = $parent['level'] ? $parent['level'] : 0;
            }

            $organization->fill($req);
            $organization->saveOrFail();
            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????', 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $currentOrganizationId = Session::get('account.organization_id');
            if ($id == $currentOrganizationId) {
                $type = 'self';
            } else {
                $type = 'sub';
            }
            $organization = Organization::find($id);
            $subOrganizationIds = OrganizationLevelFacade::getDeep($currentOrganizationId);
            $subOrganizations = Organization::whereIn('id', $subOrganizationIds)->orderByDesc('id')->get();

            return Response::view('Organization.edit', ['organization' => $organization, 'subOrganizations' => $subOrganizations, 'type' => $type]);
        } catch (ModelNotFoundException $exception) {
            return back()->withInput()->with('danger', '???????????????');
        } catch (\Exception $exception) {
            // $exceptionMessage = $exception->getMessage();
            // $exceptionLine = $exception->getLine();
            // $exceptionFile = $exception->getFile();
            // dd("{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            // return back()->withInput()->with('danger',"{$exceptionMessage}???{$exceptionLine}:{$exceptionFile}???");
            return back()->with('danger', '????????????' . $exception->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $v = Validator::make($request->all(),
                [
                    'name' => 'required|between:2,191',
                    'parent_id' => 'nullable|integer|min:0',
                    'level' => 'nullable|integer|min:0',
                    'is_main' => 'nullable|in:0,1',
                ],
                [
                    'name.required' => '??????????????????',
                    'name.between' => '??????????????????2????????????191???',
                    'parent_id.integer' => '???????????????????????????',
                    'parent_id.min' => '????????????????????????0',
                    'level.integer' => '?????????????????????',
                    'level.min' => '??????????????????0',
                    'is_main.in' => '?????????????????????????????????0???1',
                ]);
            if ($v->fails()) return Response::make($v->errors()->first(), 422);

            if (Organization::where('id', '<>', $id)->where('name', $request->get('name'))->first()) return Response::make('???????????????',500);

            $req = $request->all();
            if ($request->get('parent_id', null) == $id) return Response::make('???????????????????????????',500);
            if ($request->get('parent_id')) {
                $parent = Organization::where('id', $request->get('parent_id'))->first();
                if (!$parent) return Response::make('?????????????????????', 422);
                @$req['level'] = $parent['level'] ? $parent['level'] : 0;
            }

            $organization = Organization::where('id', $id)->firstOrFail();
            $organization->fill($req);
            $organization->saveOrFail();
            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $organization = Organization::findOrFail($id);
            $organization->delete();
            if (!$organization->trashed()) return Response::make('????????????', 500);
            return Response::make('????????????');
        } catch (ModelNotFoundException $exception) {
            return Response::make('???????????????', 404);
        } catch (\Exception $exception) {
            return Response::make('????????????', 500);
        }
    }
}
