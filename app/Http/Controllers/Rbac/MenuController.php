<?php

namespace App\Http\Controllers\Rbac;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\RbacMenuRequest;
use App\Model\PivotRoleMenu;
use App\Model\RbacMenu;
use App\Model\RbacPermission;
use App\Model\RbacRole;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Jericho\ValidateHelper;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $parentMenus = RbacMenu::with([])->orderByDesc('id')
            ->where(function ($menu) {
                $menu->where('parent_id', 0)
                    ->orWhere('parent_id', null);
            })
            ->pluck('title', 'id');

        if (request('parentId')) {
            $menus = RbacMenu::with(['parent'])->where('parent_id', request('parentId'))->orderByDesc('id')->paginate();
        } else {
            $menus = RbacMenu::with(['parent'])->where('parent_id', request('parentId'))->orderByDesc('id')->paginate();
            // $menus = RbacMenu::with(['parent'])->orderByDesc('id')->paginate();
        }

        return Response::view('Rbac.Menu.index', [
            'menus' => $menus,
            'parentMenus' => $parentMenus,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $menus = RbacMenu::with([])->orderByDesc('id')->get();
        $permissions = RbacPermission::all();
        $roles = RbacRole::with([])->get()->chunk(3);
        return Response::view('Rbac.Menu.create', [
            'menus' => $menus,
            'permissions' => $permissions,
            'roles' => $roles
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return mixed
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        try {
            $v = ValidateHelper::firstErrorByRequest($request, new RbacMenuRequest);
            if ($v !== true) return response()->json(['message' => $v], 422);

            $menu = new RbacMenu;
            $menu->fill($request->except('role_ids'));
            $menu->saveOrFail();

            # 绑定新关系
            if ($request->has('role_ids')) {
                foreach ($request->get('role_ids') as $role_id) {
                    PivotRoleMenu::with([])->create([
                        'rbac_menu_id' => $menu->id,
                        'rbac_role_id' => $role_id,
                    ]);
                }
            }

            return response()->json(['message' => '创建成功']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => '数据不存在'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => '意外错误', 'details' => [class_basename($e), $e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $parentMenus = RbacMenu::with([])->orderByDesc('id')
                ->where(function ($menu) {
                    $menu->where('parent_id', 0)
                        ->orWhere('parent_id', null);
                })
                ->where('id', '<>', $id)
                ->get();

            $roles = RbacRole::with([])->get()->chunk(3);
            $menu = RbacMenu::with(['roles'])->where('id', $id)->firstOrFail();
            $roleIds = $menu->roles->pluck('id')->toArray();

            return Response::view('Rbac.Menu.edit', [
                'menu' => $menu,
                'parent_menus' => $parentMenus,
                'roles' => $roles,
                'roleIds' => $roleIds
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', '数据不存在');
        } catch (\Exception $exception) {
            dd($exception->getMessage());
            return back()->with('danger', '意外错误');
        }
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
        try {
            $v = ValidateHelper::firstErrorByRequest($request, new RbacMenuRequest);
            if ($v !== true) return Response::make($v, 422);

            $menu = RbacMenu::findOrFail($id);
            $menu->fill($request->all());
            $menu->saveOrFail();

            return Response::make('编辑成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            return Response::make($exception->getMessage(), 500);
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
            $menu = RbacMenu::findOrFail($id);
            $menu->delete();
            if (!$menu->trashed()) return Response::make('删除失败', 403);
            return Response::make('删除成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            return Response::make('意外错误', 500);
        }
    }

    /**
     * 绑定菜单到角色
     * @param int $menuId 菜单编号
     * @return \Illuminate\Http\Response
     */
    public function bindRoles($menuId)
    {
        try {
            PivotRoleMenu::where('rbac_menu_id', $menuId)->delete();  # 删除原绑定信息

            # 删除原绑定关系
            DB::table('pivot_role_menus')->where('rbac_menu_id', $menuId)->delete();
            if (request()->has('role_ids')) {
                # 绑定新关系
                $insertData = [];
                foreach (request('role_ids') as $item) {
                    $insertData[] = ['rbac_menu_id' => $menuId, 'rbac_role_id' => $item];
                }
                $insertResult = DB::table('pivot_role_menus')->insert($insertData);
                if (!$insertResult) return Response::make('绑定失败', 500);
            }


            return Response::make('绑定成功');
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            return Response::make('意外错误:' . $exception->getMessage(), 500);
        }
    }
}
