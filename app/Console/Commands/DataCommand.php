<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Menu;
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

    /**
     * 初始化数据库
     */
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
            "路局" => "web.OrganizationRailway",
            "站段" => "web.OrganizationParagraph",
            "线别" => "web.OrganizationLine",
            "车间" => "web.OrganizationWorkshop",
            "车间类型" => "web.OrganizationWorkshopType",
            "工区" => "web.OrganizationWorkArea",
            "工区类型" => "web.OrganizationWorkAreaType",
            "站场" => "web.OrganizationStation",
            "道口" => "web.OrganizationRailroadGradeCross",
            "区间" => "web.OrganizationSection",
            "中心" => "web.OrganizationCenter",
            "种类型-种类" => "web.KindCategory",
            "种类型-类型" => "web.KindEntireType",
            "种类型-型号" => "web.KindSubType",
            "位置-仓储-仓库" => "web.LocationDepotStorehouse",
            "位置-仓储-仓库区域" => "web.LocationDepotSection",
            "位置-仓库-仓库排类型" => "web.LocationDepotRowType",
            "位置-仓储-仓库排" => "web.LocationDepotRow",
            "位置-仓储-仓库柜架" => "web.LocationDepotCabinet",
            "位置-仓储-仓库柜架层" => "web.LocationDepotTier",
            "位置-仓储-仓库柜架格位" => "web.LocationDepotCell",
            "位置-室内上道位置-仓库" => "web.LocationDepotStorehouse",
            "位置-室内上道位置-仓库区域" => "web.LocationDepotSection",
            "位置-室内上道位置-仓库排" => "web.LocationDepotRow",
            "位置-室内上道位置-仓库柜架" => "web.LocationDepotCabinet",
            "位置-室内上道位置-仓库柜架层" => "web.LocationDepotTier",
            "位置-室内上道位置-仓库柜架格位" => "web.LocationDepotCell",
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
                "name" => "基础信息",
                "url" => "",
                "uri_name" => "",
                "icon" => "fa fa-cog",
                "subs" => [
                    [
                        "name" => "线别管理",
                        "url" => "/organization/line",
                        "uri_name" => "web.OrganizationLine",
                        "icon" => "fa fa-code-fork"
                    ],
                    [
                        "name" => "路局管理",
                        "url" => "/organization/railway",
                        "uri_name" => "web.OrganizationRailway",
                        "icon" => "fa fa-subway",
                    ],
                    [
                        "name" => "站段管理",
                        "url" => "/organization/paragraph",
                        "uri_name" => "web.OrganizationParagraph",
                        "icon" => "fa fa-th-large",
                    ],
                    [
                        "name" => "车间管理",
                        "url" => "/organization/workshop",
                        "uri_name" => "web.OrganizationWorkshop",
                        "icon" => "fa fa-th",
                    ],
                    [
                        "name" => "车间类型管理",
                        "url" => "/organization/workshopType",
                        "uri_name" => "web.OrganizationWorkshopType",
                        "icon" => "fa fa-th",
                    ],
                    [
                        "name" => "工区管理",
                        "url" => "/organization/workArea",
                        "uri_name" => "web.OrganizationWorkArea",
                        "icon" => "fa fa-th-list",
                    ],
                    [
                        "name" => "工区类型管理",
                        "url" => "/organization/workAreaType",
                        "uri_name" => "web.OrganizationWorkAreaType",
                        "icon" => "fa fa-th-list",
                    ],
                    [
                        "name" => "站场管理",
                        "url" => "/organization/station",
                        "uri_name" => "web.OrganizationStation",
                        "icon" => "fa fa-fort-awesome",
                    ],
                    [
                        "name" => "道口管理",
                        "url" => "/organization/railroadGradeCross",
                        "uri_name" => "web.OrganizationRailroadGradeCross",
                        "icon" => "fa fa-openid"
                    ],
                    [
                        "name" => "区间管理",
                        "url" => "/organization/section",
                        "uri_name" => "web.OrganizationSection",
                        "icon" => "fa fa-slack",
                    ],
                    [
                        "name" => "中心管理",
                        "url" => "/organization/center",
                        "uri_name" => "web.OrganizationCenter",
                        "icon" => "fa fa-yelp",
                    ],
                    [
                        "name" => "仓库位置管理",
                        "url" => "/location/depotStorehouse",
                        "uri_name" => "web.LocationDepotStorehouse",
                        "icon" => "fa fa-home"
                    ],
                    [
                        "name" => "室内上道位置管理",
                        "url" => "/location/indoorRoom",
                        "uri_name" => "web.LocationIndoorRoom",
                        "icon" => "fa fa-map-marker"
                    ],
                    [
                        "name" => "室外上道位置管理",
                        "url" => "/location/indoorRoom",
                        "uri_name" => "web.LocationIndoorRoom",
                        "icon" => "fa fa-map-marker"
                    ],
                ],
            ],
            [
                "name" => "系统设置",
                "url" => "",
                "uri_name" => "",
                "icon" => "fa fa-cogs",
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
     * 初始化测试数据
     */
    private function test()
    {

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
