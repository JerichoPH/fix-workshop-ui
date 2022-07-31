<?php

namespace App\Console\Commands;

use App\Facades\TextFacade;
use App\Models\Account;
use App\Models\Menu;
use App\Models\PivotRbacRoleAndRbacPermission;
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
    protected $signature = 'data {operator} {arg1?}';

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
            "用户" => ["group" => "account", "subs" => [],],
            "角色" => [
                "group" => "rbacRole",
                "subs" => [
                    "角色绑定用户" => [
                        "uri" => "rbacRole/:uuidbindAccounts",
                        "method" => "PUT",
                    ],
                    "角色绑定权限" => [
                        "uri" => "rbacRole/:uuidbindPermissions",
                        "method" => "PUT",
                    ],
                ],
            ],
            "权限分组" => ["group" => "rbacPermissionGroup", "subs" => [],],
            "权限" => ["group" => "rbacPermission", "subs" => [],],
            "菜单" => ["group" => "menu", "subs" => [],],
            "组织机构-线别" => [
                "group" => "organization/line",
                "subs" => [
                    "线别绑定路局" => [
                        "uri" => "organization/line/:uuid/bindOrganizationRailways",
                        "method" => "PUT",
                    ],
                    "线别绑定站段" => [
                        "uri" => "organization/line/:uuid/bindOrganizationParagraphs",
                        "method" => "PUT",
                    ],
                    "线别绑定车间" => [
                        "uri" => "organization/line/:uuid/bindOrganizationWorkshops",
                        "method" => "PUT",
                    ],
                    "线别绑定工区" => [
                        "uri" => "organization/line/:uuid/bindOrganizationWorkAreas",
                        "method" => "PUT",
                    ],
                    "线别绑定区间" => [
                        "uri" => "organization/line/:uuid/bindOrganizationSections",
                        "method" => "PUT",
                    ],
                    "线别绑定站场" => [
                        "uri" => "organization/line/:uuid/bindOrganizationStations",
                        "method" => "PUT",
                    ],
                    "线别绑定道口" => [
                        "uri" => "organization/line/:uuid/bindOrganizationRailroadGradeCrosses",
                        "method" => "PUT",
                    ],
                    "线别绑定中心" => [
                        "uri" => "organization/line/:uuid/bindOrganizationCenters",
                        "method" => "PUT",
                    ],
                ],
            ],
            "组织机构-路局" => [
                "group" => "organization/railway",
                "subs" => [
                    "路局绑定线别" => [
                        "uri" => "organization/railway/:uuid/bindOrganizationLines",
                        "method" => "PUT",
                    ],
                ],
            ],
            "组织机构-站段" => ["group" => "organization/paragraph", "subs" => [],],
            "组织机构-车间" => ["group" => "organization/workshop", "subs" => [],],
            "组织机构-车间类型" => ["group" => "organization/workshopType", "subs" => [],],
            "组织机构-工区" => ["group" => "organization/workArea", "subs" => [],],
            "组织机构-工区类型" => ["group" => "organization/workAreaType", "subs" => [],],
            "组织机构-区间" => ["group" => "organization/section", "subs" => [],],
            "组织机构-站场" => ["group" => "organization/station", "subs" => [],],
            "组织机构-道口" => ["group" => "organization/railroadGradeCross", "subs" => [],],
            "组织机构-中心" => ["group" => "organization/center", "subs" => [],],
            "种类型-种类" => ["group" => "kind/category", "subs" => [],],
            "种类型-类型" => ["group" => "kind/entireType", "subs" => [],],
            "种类型-型号" => ["group" => "kind/subType", "subs" => [],],
            "位置-仓储-仓库" => ["group" => "location/depotStorehouse", "subs" => [],],
            "位置-仓储-仓库区域" => ["group" => "location/depotSection", "subs" => [],],
            "位置-仓库-仓库排类型" => ["group" => "location/depotRowType", "subs" => [],],
            "位置-仓储-仓库排" => ["group" => "location/depotRow", "subs" => [],],
            "位置-仓储-仓库柜架" => ["group" => "location/depotCabinet", "subs" => [],],
            "位置-仓储-仓库柜架层" => ["group" => "location/depotTier", "subs" => [],],
            "位置-仓储-仓库柜架格位" => ["group" => "location/depotCell", "subs" => [],],
            "位置-室内上到位置-机房类型" => ["group" => "location/indoorRoomType", "subs" => [],],
            "位置-室内上道位置-机房" => ["group" => "location/indoorRoom", "subs" => [],],
            "位置-室内上道位置-机房排" => ["group" => "location/indoorRow", "subs" => [],],
            "位置-室内上道位置-机房柜架" => ["group" => "location/indoorCabinet", "subs" => [],],
            "位置-室内上道位置-机房柜架层" => ["group" => "location/indoorTier", "subs" => [],],
            "位置-室内上道位置-机房柜架格位" => ["group" => "location/indoorCell", "subs" => [],],
        ])->each(function ($rbacPermissionGroupUri, $rbacPermissionGroupName) use ($rbacRole) {
            ["group" => $group, "subs" => $subs,] = $rbacPermissionGroupUri;
            $rbacPermissionGroup = $this->createPermissionGroup(
                $rbacPermissionGroupName,
                $group,
                $rbacRole
            );

            if (!empty($subs)) {
                collect($subs)->each(function ($datum, $name) use ($rbacPermissionGroup, $rbacRole) {
                    $rbacPermission = RbacPermission::with([])
                        ->create([
                            "uuid" => Str::uuid(),
                            "sort" => 0,
                            "name" => $name,
                            "uri" => TextFacade::joinWithNotEmpty("/", [$this->argument("arg1"), $datum["uri"]]),
                            "method" => $datum["method"],
                            "rbac_permission_group_uuid" => $rbacPermissionGroup->uuid,
                        ]);
                    $this->comment("创建权限：{$name}");
                    PivotRbacRoleAndRbacPermission::with([])->insert(["rbac_role_id" => $rbacRole->id, "rbac_permission_id" => $rbacPermission->id,]);
                    $this->comment("绑定角色与权限：{$rbacRole->name}→{$rbacPermission->name}");
                });
            }
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

    /**
     * @param string $rbacPermissionGroupName
     * @param string $rbacPermissionGroupUri
     * @param RbacRole $rbacRole
     * @return RbacPermissionGroup
     */
    private function createPermissionGroup(string $rbacPermissionGroupName, string $rbacPermissionGroupUri, RbacRole $rbacRole): RbacPermissionGroup
    {
        $rbacPermissionGroup = RbacPermissionGroup::with([])
            ->create([
                "uuid" => Str::uuid(),
                "name" => $rbacPermissionGroupName,
            ]);
        $this->comment("创建权限分组：$rbacPermissionGroup->name");

        // 创建权限（资源权限组）
        collect([
            "列表" => ["uri" => "", "method" => "GET"],
            "新建页面" => ["uri" => "create", "method" => "GET",],
            "新建" => ["uri" => "", "method" => "POST",],
            "详情页面" => ["uri" => ":uuid", "method" => "GET",],
            "编辑页面" => ["uri" => ":uuid/edit", "method" => "GET",],
            "编辑" => ["uri" => ":uuid", "method" => "PUT",],
            "删除" => ["uri" => ":uuid", "method" => "DELETE",],
        ])
            ->each(function ($datum, $rbacPermissionName)
            use ($rbacPermissionGroupUri, $rbacPermissionGroup, $rbacRole) {
                $rbacPermission = RbacPermission::with([])
                    ->create([
                        "uuid" => Str::uuid(),
                        "name" => $rbacPermissionName,
                        "uri" => TextFacade::joinWithNotEmpty("/", [$this->argument("arg1"), $rbacPermissionGroupUri, $datum["uri"]]),
                        "method" => $datum["method"],
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

        return $rbacPermissionGroup;
    }
}
