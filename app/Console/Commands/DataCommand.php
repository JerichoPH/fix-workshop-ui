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
        DB::table("kind_categories")->truncate();
        DB::table("kind_entire_types")->truncate();
        DB::table("kind_sub_types")->truncate();
        DB::table("location_centers")->truncate();
        DB::table("location_lines")->truncate();
        DB::table("location_sections")->truncate();
        DB::table("location_stations")->truncate();
        DB::table("menus")->truncate();
        DB::table("organization_paragraphs")->truncate();
        DB::table("organization_railroad_grade_crosses")->truncate();
        DB::table("organization_railways")->truncate();
        DB::table("organization_work_area_types")->truncate();
        DB::table("organization_work_areas")->truncate();
        DB::table("organization_workshop_types")->truncate();
        DB::table("organization_workshops")->truncate();
        DB::table("pivot_location_line_and_location_centers")->truncate();
        DB::table("pivot_location_line_and_location_railroad_grade_crosses")->truncate();
        DB::table("pivot_location_line_and_location_sections")->truncate();
        DB::table("pivot_location_line_and_location_stations")->truncate();
        DB::table("pivot_location_line_and_organization_paragraphs")->truncate();
        DB::table("pivot_location_line_and_organization_railways")->truncate();
        DB::table("pivot_location_line_and_organization_work_areas")->truncate();
        DB::table("pivot_location_line_and_organization_workshops")->truncate();
        DB::table("pivot_rbac_role_and_accounts")->truncate();
        DB::table("pivot_rbac_role_and_menus")->truncate();
        DB::table("pivot_rbac_role_and_rbac_permissions")->truncate();
        DB::table("position_depot_cabinets")->truncate();
        DB::table("position_depot_cells")->truncate();
        DB::table("position_depot_row_types")->truncate();
        DB::table("position_depot_rows")->truncate();
        DB::table("position_depot_sections")->truncate();
        DB::table("position_depot_storehouses")->truncate();
        DB::table("position_depot_tiers")->truncate();
        DB::table("position_indoor_cabinets")->truncate();
        DB::table("position_indoor_cells")->truncate();
        DB::table("position_indoor_room_types")->truncate();
        DB::table("position_indoor_rooms")->truncate();
        DB::table("position_indoor_rows")->truncate();
        DB::table("position_indoor_tiers")->truncate();
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
                        "uri" => "rbacRole/:uuid/bindAccounts",
                        "method" => "PUT",
                    ],
                    "角色绑定权限" => [
                        "uri" => "rbacRole/:uuid/bindPermissions",
                        "method" => "PUT",
                    ],
                ],
            ],
            "权限分组" => ["group" => "rbacPermissionGroup", "subs" => [],],
            "权限" => ["group" => "rbacPermission", "subs" => [],],
            "菜单" => ["group" => "menu", "subs" => [],],
            "种类型-种类" => ["group" => "kindCategory", "subs" => [],],
            "种类型-类型" => ["group" => "kindEntireType", "subs" => [],],
            "种类型-型号" => ["group" => "kindSubType", "subs" => [],],
            "组织机构-站段" => ["group" => "organizationParagraph", "subs" => [],],
            "组织机构-车间" => ["group" => "organizationWorkshop", "subs" => [],],
            "组织机构-车间类型" => ["group" => "organizationWorkshopType", "subs" => [],],
            "组织机构-工区" => ["group" => "organizationWorkArea", "subs" => [],],
            "组织机构-工区类型" => ["group" => "organizationWorkAreaType", "subs" => [],],
            "组织机构-路局" => [
                "group" => "organizationRailway",
                "subs" => [
                    "路局绑定线别" => [
                        "uri" => "organizationRailway/:uuid/bindLocationLines",
                        "method" => "PUT",
                    ],
                ],
            ],
            "使用处所-线别" => [
                "group" => "locationLine",
                "subs" => [
                    "线别绑定路局" => [
                        "uri" => "locationLine/:uuid/bindOrganizationRailways",
                        "method" => "PUT",
                    ],
                    "线别绑定站段" => [
                        "uri" => "locationLine/:uuid/bindOrganizationParagraphs",
                        "method" => "PUT",
                    ],
                    "线别绑定车间" => [
                        "uri" => "locationLine/:uuid/bindOrganizationWorkshops",
                        "method" => "PUT",
                    ],
                    "线别绑定工区" => [
                        "uri" => "locationLine/:uuid/bindOrganizationWorkAreas",
                        "method" => "PUT",
                    ],
                    "线别绑定区间" => [
                        "uri" => "locationLine/:uuid/bindOrganizationSections",
                        "method" => "PUT",
                    ],
                    "线别绑定站场" => [
                        "uri" => "locationLine/:uuid/bindOrganizationStations",
                        "method" => "PUT",
                    ],
                    "线别绑定道口" => [
                        "uri" => "locationLine/:uuid/bindOrganizationRailroadGradeCrosses",
                        "method" => "PUT",
                    ],
                    "线别绑定中心" => [
                        "uri" => "locationLine/:uuid/bindOrganizationCenters",
                        "method" => "PUT",
                    ],
                ],
            ],
            "使用处所-区间" => ["group" => "locationSection", "subs" => [],],
            "使用处所-站场" => ["group" => "locationStation", "subs" => [],],
            "使用处所-道口" => ["group" => "locationRailroadGradeCross", "subs" => [],],
            "使用处所-中心" => ["group" => "locationCenter", "subs" => [],],
            "使用位置-仓储-仓库" => ["group" => "positionDepotStorehouse", "subs" => [],],
            "使用位置-仓储-仓库区域" => ["group" => "positionDepotSection", "subs" => [],],
            "使用位置-仓库-仓库排类型" => ["group" => "positionDepotRowType", "subs" => [],],
            "使用位置-仓储-仓库排" => ["group" => "positionDepotRow", "subs" => [],],
            "使用位置-仓储-仓库柜架" => ["group" => "positionDepotCabinet", "subs" => [],],
            "使用位置-仓储-仓库柜架层" => ["group" => "positionDepotTier", "subs" => [],],
            "使用位置-仓储-仓库柜架格位" => ["group" => "positionDepotCell", "subs" => [],],
            "使用位置-室内上到位置-机房类型" => ["group" => "positionIndoorRoomType", "subs" => [],],
            "使用位置-室内上道位置-机房" => ["group" => "positionIndoorRoom", "subs" => [],],
            "使用位置-室内上道位置-机房排" => ["group" => "positionIndoorRow", "subs" => [],],
            "使用位置-室内上道位置-机房柜架" => ["group" => "positionIndoorCabinet", "subs" => [],],
            "使用位置-室内上道位置-机房柜架层" => ["group" => "positionIndoorTier", "subs" => [],],
            "使用位置-室内上道位置-机房柜架格位" => ["group" => "positionIndoorCell", "subs" => [],],
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
                        "name" => "路局管理",
                        "url" => "/organizationRailway",
                        "uri_name" => "web.OrganizationRailway",
                        "icon" => "fa fa-subway",
                    ],
                    [
                        "name" => "站段管理",
                        "url" => "/organizationParagraph",
                        "uri_name" => "web.OrganizationParagraph",
                        "icon" => "fa fa-th-large",
                    ],
                    [
                        "name" => "车间管理",
                        "url" => "/organizationWorkshop",
                        "uri_name" => "web.OrganizationWorkshop",
                        "icon" => "fa fa-th",
                    ],
                    [
                        "name" => "车间类型管理",
                        "url" => "/organizationWorkshopType",
                        "uri_name" => "web.OrganizationWorkshopType",
                        "icon" => "fa fa-th",
                    ],
                    [
                        "name" => "工区管理",
                        "url" => "/organizationWorkArea",
                        "uri_name" => "web.OrganizationWorkArea",
                        "icon" => "fa fa-th-list",
                    ],
                    [
                        "name" => "工区类型管理",
                        "url" => "/organizationWorkAreaType",
                        "uri_name" => "web.OrganizationWorkAreaType",
                        "icon" => "fa fa-th-list",
                    ],
                    [
                        "name" => "线别管理",
                        "url" => "/locationLine",
                        "uri_name" => "web.LocationLine",
                        "icon" => "fa fa-code-fork"
                    ],
                    [
                        "name" => "站场管理",
                        "url" => "/locationStation",
                        "uri_name" => "web.LocationStation",
                        "icon" => "fa fa-fort-awesome",
                    ],
                    [
                        "name" => "道口管理",
                        "url" => "/locationRailroadGradeCross",
                        "uri_name" => "web.LocationRailroadGradeCross",
                        "icon" => "fa fa-openid"
                    ],
                    [
                        "name" => "区间管理",
                        "url" => "/locationSection",
                        "uri_name" => "web.LocationSection",
                        "icon" => "fa fa-slack",
                    ],
                    [
                        "name" => "中心管理",
                        "url" => "/locationCenter",
                        "uri_name" => "web.LocationCenter",
                        "icon" => "fa fa-yelp",
                    ],
                    [
                        "name" => "仓库位置管理",
                        "url" => "/positionDepotStorehouse",
                        "uri_name" => "web.PositionDepotStorehouse",
                        "icon" => "fa fa-home"
                    ],
                    [
                        "name" => "室内上道位置管理",
                        "url" => "/positionIndoorRoom",
                        "uri_name" => "web.PositionIndoorRoom",
                        "icon" => "fa fa-map-marker"
                    ],
                    [
                        "name" => "室外上道位置管理",
                        "url" => "/positionOutdoor",
                        "uri_name" => "web.PositionOutdoor",
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
