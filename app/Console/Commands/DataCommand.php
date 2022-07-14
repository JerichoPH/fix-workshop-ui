<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Menu;
use App\Models\PivotRbacRoleAndMenu;
use App\Models\RbacPermission;
use App\Models\RbacPermissionGroup;
use App\Models\RbacRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data {operator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private function init()
    {
        $this->line("初始化数据开始");
        DB::table("accounts")->truncate();
        DB::table("menus")->truncate();
        DB::table("pivot_rbac_role_and_accounts")->truncate();
        DB::table("pivot_rbac_role_and_menus")->truncate();
        DB::table("pivot_rbac_role_and_rbac_permissions")->truncate();
        DB::table("rbac_permission_groups")->truncate();
        DB::table("rbac_permissions")->truncate();
        DB::table("rbac_roles")->truncate();

        // 注册用户
        $account = Account::with([])
            ->create([
                "uuid" => Str::uuid(),
                "username" => "admin",
                "password" => bcrypt("zces@1234"),
                "nickname" => "admin",
            ]);
        $this->comment("创建用户：$account->nickname");

        // 创建角色
        $rbacRole = RbacRole::with([])
            ->create([
                "uuid" => Str::uuid(),
                "name" => "默认",
            ]);
        $this->comment("创建角色：$rbacRole->name");

        // 创建角色➡用户
        DB::table("pivot_rbac_role_and_accounts")->insert([
            "rbac_role_id" => $rbacRole->id,
            "account_id" => $account->id,
        ]);
        $this->comment("角色绑定用户：$account->nickname ➡ $rbacRole->id");

        // 创建权限分组
        collect([
            "用户" => "web.Account",
            "角色" => "web.RbacRole",
            "权限分组" => "web.RbacPermissionGroup",
            "权限" => "web.RbacPermission",
            "菜单" => "web.Menu",
        ])->each(function ($rbacPermissionGroupUri, $rbacPermissionGroupName) use ($rbacRole) {
            $rbacPermissionGroup = RbacPermissionGroup::with([])
                ->create([
                    "uuid" => Str::uuid(),
                    "name" => $rbacPermissionGroupName,
                ]);
            $this->comment("创建权限分组：$rbacPermissionGroup->name");

            // 创建权限（资源权限组）
            collect([
                "列表" => "GET",
                "新建页面" => "GET",
                "新建" => "POST",
                "详情页面" => "GET",
                "编辑页面" => "GET",
                "编辑" => "PUT",
                "删除" => "DELETE",
            ])
                ->each(function ($rbacPermissionMethod, $rbacPermissionName) use ($rbacPermissionGroupUri, $rbacPermissionGroup, $rbacRole) {
                    $rbacPermission = RbacPermission::with([])
                        ->create([
                            "uuid" => Str::uuid(),
                            "name" => $rbacPermissionName,
                            "uri" => $rbacPermissionGroupUri,
                            "method" => $rbacPermissionMethod,
                            "rbac_permission_group_uuid" => $rbacPermissionGroup->uuid,
                        ]);
                    $this->comment("创建权限：$rbacPermissionGroup->name ➡ $rbacPermission->name");
                    // 创建角色➡权限
                    DB::table("pivot_rbac_role_and_rbac_permissions")->insert([
                        "rbac_permission_id" => $rbacPermission->id,
                        "rbac_role_id" => $rbacRole->id,
                    ]);
                    $this->comment("角色绑定权限：$rbacRole->name ➡ $rbacPermissionGroup->name($rbacPermission->name)");
                });
        });

        // 创建菜单
        collect([
            [
                "name" => "基础数据",
                "url" => "",
                "uri_name" => "",
                "icon" => "",
                "subs" => [
                    [
                        "name" => "用户管理",
                        "url" => "/account",
                        "uri_name" => "web.Account",
                        "icon" => "fa fa-user",
                    ],
                    [
                        "name" => "角色管理",
                        "url" => "/rbacRole",
                        "uri_name" => "web.RbacRole",
                        "icon" => "fa fa-users",
                    ],
                    [
                        "name" => "权限分组管理",
                        "url" => "/rbacPermissionGroup",
                        "uri_name" => "web.RbacPermissionGroup",
                        "icon" => "fa fa-lock",
                    ],
                    [
                        "name" => "权限管理",
                        "url" => "/rbacPermission",
                        "uri_name" => "web.RbacPermission",
                        "icon" => "fa fa-key",
                    ],
                    [
                        "name" => "菜单管理",
                        "url" => "/menu",
                        "uri_name" => "web.Menu",
                        "icon" => "fa fa-bars",
                    ],
                ],
            ],
        ])
            ->each(function ($menu1) use ($rbacRole) {
                $newMenu1 = Menu::with([])
                    ->create([
                        "uuid" => Str::uuid(),
                        "name" => $menu1["name"],
                        "url" => $menu1["url"],
                        "uri_name" => $menu1["uri_name"],
                        "icon" => $menu1["icon"],
                    ]);
                $this->comment("创建菜单：$newMenu1->name");
                DB::table("pivot_rbac_role_and_menus")->insert([
                    "menu_id" => $newMenu1->id,
                    "rbac_role_id" => $rbacRole->id,
                ]);
                $this->comment("角色绑定菜单：$rbacRole->name ➡ $newMenu1->name");

                collect($menu1["subs"])->each(function ($menu2) use ($newMenu1, $rbacRole) {
                    $newMenu2 = Menu::with([])
                        ->create([
                            "uuid" => Str::uuid(),
                            "name" => $menu2["name"],
                            "url" => $menu2["url"],
                            "uri_name" => $menu2["uri_name"],
                            "parent_uuid" => $newMenu1->uuid,
                            "icon" => $menu2["icon"],
                        ]);
                    $this->comment("创建菜单：$newMenu1->name ➡ $newMenu2->name");
                    DB::table("pivot_rbac_role_and_menus")->insert([
                        "menu_id" => $newMenu2->id,
                        "rbac_role_id" => $rbacRole->id,
                    ]);
                    $this->comment("角色绑定菜单：$rbacRole->name ➡ $newMenu1->name($newMenu2->name)");
                });
            });

        $this->info("初始化数据完成");
    }

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->{$this->argument("operator")}();
    }
}
