<?php

namespace App\Console\Commands;

use App\Facades\TextFacade;
use App\Models\Account;
use App\Models\Menu;
use App\Models\OrganizationParagraph;
use App\Models\OrganizationRailway;
use App\Models\OrganizationWorkArea;
use App\Models\OrganizationWorkAreaProfession;
use App\Models\OrganizationWorkAreaType;
use App\Models\OrganizationWorkshop;
use App\Models\OrganizationWorkshopType;
use App\Models\PivotRbacRoleAndAccount;
use App\Models\PivotRbacRoleAndRbacPermission;
use App\Models\PositionDepotRowType;
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
        $this->line("截断现有数据");
        collect([
            "accounts",
            "entire_instance_locks",
            "entire_instance_log_types",
            "entire_instance_logs",
            "entire_instance_statuses",
            "entire_instances",
            "factories",
            "kind_categories",
            "kind_entire_types",
            "kind_sub_types",
            "location_centers",
            "location_lines",
            "location_railroads",
            "location_sections",
            "location_stations",
            "menus",
            "migrations",
            "organization_paragraphs",
            "organization_railways",
            "organization_work_area_professions",
            "organization_work_area_types",
            "organization_work_areas",
            "organization_workshop_types",
            "organization_workshops",
            "pivot_location_line_and_location_centers",
            "pivot_location_line_and_location_railroads",
            "pivot_location_line_and_location_sections",
            "pivot_location_line_and_location_stations",
            "pivot_rbac_role_and_accounts",
            "pivot_rbac_role_and_menus",
            "pivot_rbac_role_and_rbac_permissions",
            "position_depot_cabinets",
            "position_depot_cells",
            "position_depot_row_types",
            "position_depot_rows",
            "position_depot_sections",
            "position_depot_storehouses",
            "position_depot_tiers",
            "position_indoor_cabinets",
            "position_indoor_cells",
            "position_indoor_room_types",
            "position_indoor_rooms",
            "position_indoor_rows",
            "position_indoor_tiers",
            "rbac_permission_groups",
            "rbac_permissions",
            "rbac_roles",
            "source_names",
            "source_types",
        ])->each(function ($tableName) {
            DB::table($tableName)->truncate();
        });

        $this->line("初始化数据开始");

        // 创建角色
        $rbacRole = RbacRole::with([])
            ->create([
                "uuid" => Str::uuid(),
                "name" => "默认",
            ]);
        $this->comment("创建角色：$rbacRole->name");

        // 创建用户（admin）
        $accountAdmin = Account::with([])
            ->create([
                "uuid" => Str::uuid(),
                "username" => "admin",
                "password" => bcrypt("zces@1234"),
                "nickname" => "Admin",
                "be_super_admin" => true,
            ]);
        // 创建角色→用户
        DB::table("pivot_rbac_role_and_accounts")->insert([
            "rbac_role_uuid" => $rbacRole->uuid,
            "account_uuid" => $accountAdmin->uuid,
        ]);
        $this->comment("角色绑定用户：$accountAdmin->nickname → $rbacRole->id");

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
                    "角色绑定权限（根据权限分组）" => [
                        "uri" => "rbacRole/:uuid/bindRbacPermissionsByRbacPermissionGroup",
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
            "组织机构-路局" => ["group" => "organizationRailway", "subs" => [],],
            "组织机构-站段" => ["group" => "organizationParagraph", "subs" => [],],
            "组织机构-车间" => ["group" => "organizationWorkshop", "subs" => [],],
            "组织机构-车间类型" => ["group" => "organizationWorkshopType", "subs" => [],],
            "组织机构-工区" => ["group" => "organizationWorkArea", "subs" => [],],
            "组织机构-工区类型" => ["group" => "organizationWorkAreaType", "subs" => [],],
            "组织机构-工区专业" => ["group" => "organizationWorkAreaProfession", "subs" => [],],
            "使用地点-线别" => ["group" => "locationLine", "subs" => [],],
            "使用地点-区间" => [
                "group" => "locationSection",
                "subs" => [
                    "站场绑定线别" => [
                        "uri" => "locationSection/:uuid/bindLocationLines",
                        "method" => "PUT",
                    ],
                ],
            ],
            "使用地点-站场" => [
                "group" => "locationStation",
                "subs" => [
                    "站场绑定线别" => [
                        "uri" => "locationStation/:uuid/bindLocationLines",
                        "method" => "PUT",
                    ],
                ],
            ],
            "使用地点-道口" => [
                "group" => "locationRailroadGradeCross",
                "subs" => [
                    "站场绑定线别" => [
                        "uri" => "locationRailroadGradeCross/:uuid/bindLocationLines",
                        "method" => "PUT",
                    ],
                ],
            ],
            "使用地点-中心" => [
                "group" => "locationCenter",
                "subs" => [
                    "中心绑定线别" => [
                        "uri" => "locationCenter/:uuid/bindLocationLines",
                        "method" => "PUT",
                    ],
                ],
            ],
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
            "赋码" => ["group" => "tag", "subs" => [],],
            "数据同步" => [
                "group" => "sync",
                "subs" => [
                    "仓库位置（段中心→检修车间）" => [
                        "uri" => "sync/positionDepotFromParagraphCenter",
                        "method" => "POST",
                    ],
                ],
            ],
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
                    PivotRbacRoleAndRbacPermission::with([])->insert(["rbac_role_uuid" => $rbacRole->uuid, "rbac_permission_uuid" => $rbacPermission->uuid,]);
                    $this->comment("绑定角色与权限：{$rbacRole->name}→{$rbacPermission->name}");
                });
            }
        });

        // 创建菜单
        collect([[
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
                    "name" => "工区专业管理",
                    "url" => "/organizationWorkAreaProfession",
                    "uri_name" => "web.OrganizationWorkAreaProfession",
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
        ], [
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
        ],])
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
                    "menu_uuid" => $newMenu1->uuid,
                    "rbac_role_uuid" => $rbacRole->uuid,
                ]);
                $this->comment("角色绑定菜单：$rbacRole->name → $newMenu1->name");

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
                    $this->comment("创建菜单：$newMenu1->name → $newMenu2->name");
                    DB::table("pivot_rbac_role_and_menus")->insert([
                        "menu_uuid" => $newMenu2->uuid,
                        "rbac_role_uuid" => $rbacRole->uuid,
                    ]);
                    $this->comment("角色绑定菜单：$rbacRole->name → $newMenu1->name($newMenu2->name)");
                });
            });

        // 创建器材状态
        collect([
            [
                "unique_code" => "FIXING",
                "name" => "待修",
                "number_code" => "07",
            ], [
                "unique_code" => "FIXED",
                "name" => "所内成品",
                "number_code" => "08",
            ], [
                "unique_code" => "TRANSFER_OUT",
                "name" => "出所在途",
                "number_code" => "02",
            ], [
                "unique_code" => "INSTALLED",
                "name" => "上道使用",
                "number_code" => "01",
            ], [
                "unique_code" => "INSTALLING",
                "name" => "备品",
                "number_code" => "03",
            ], [
                "unique_code" => "TRANSFER_IN",
                "name" => "入所在途",
                "number_code" => "06",
            ], [
                "unique_code" => "UNINSTALLED",
                "name" => "下道停用",
                "number_code" => "04",
            ], [
                "unique_code" => "UNINSTALLED_BREAKDOWN",
                "name" => "故障停用",
                "number_code" => "05",
            ], [
                "unique_code" => "SEND_REPAIR",
                "name" => "返厂修",
                "number_code" => "09",
            ], [
                "unique_code" => "SCRAP",
                "name" => "报废",
                "number_code" => "10",
            ],
        ])
            ->each(function ($status) {
                DB::table("entire_instance_statuses")
                    ->insert([
                        "created_at" => now(),
                        "updated_at" => now(),
                        "uuid" => Str::uuid(),
                        "unique_code" => $status["unique_code"],
                        "name" => $status["name"],
                        "number_code" => $status["number_code"],
                    ]);
                $this->comment("创建器材状态：{$status["unique_code"]} {$status["number_code"]} {$status["name"]}");
            });

        // 创建仓库排类型
        collect([[
            "unique_code" => "FIXED",
            "name" => "成品",
        ], [
            "unique_code" => "EMERGENCY",
            "name" => "应急备品",
        ], [
            "unique_code" => "FIXING",
            "name" => "待修",
        ]])
            ->each(function ($datum) {
                PositionDepotRowType::with([])
                    ->create([
                        "uuid" => Str::uuid(),
                        "unique_code" => $datum["unique_code"],
                        "name" => $datum["name"],
                    ]);
                $this->comment("创建仓库排：{$datum["name"]}");
            });

        // 创建器材日志类型
        collect([[
            "unique_code" => "TAG",
            "name" => "赋码",
            "icon" => "fa fa-envelope-o",
            "unique_code_for_paragraph" => "TAG",
        ], [
            "unique_code" => "IN",
            "name" => "入所",
            "icon" => "fa fa-home",
            "unique_code_for_paragraph" => "TRANSFER_IN",
        ], [
            "unique_code" => "OUT",
            "name" => "出所",
            "icon" => "fa fa-home",
            "unique_code_for_paragraph" => "OUT",
        ], [
            "unique_code" => "FIX-BEFORE",
            "name" => "修前检",
            "icon" => "fa fa-wrench",
            "unique_code_for_paragraph" => "FIX_BEFORE",
        ], [
            "unique_code" => "FIX-AFTER",
            "name" => "修后检",
            "icon" => "fa fa-wrench",
            "unique_code_for_paragraph" => "FIX_AFTER",
        ], [
            "unique_code" => "CHECK",
            "name" => "验收",
            "icon" => "fa fa-wrench",
            "unique_code_for_paragraph" => "CHECKED",
        ], [
            "unique_code" => "SPOT-CHECK",
            "name" => "抽验",
            "icon" => "fa fa-wrench",
            "unique_code_for_paragraph" => "CHECKED",
        ], [
            "unique_code" => "INSTALLED",
            "name" => "上道",
            "icon" => "fa fa-map-signs",
            "unique_code_for_paragraph" => "INSTALLED",
        ], [
            "unique_code" => "UNINSTALL",
            "name" => "下道",
            "icon" => "fa fa-map-signs",
            "unique_code_for_paragraph" => "UNBINDING",
        ], [
            "unique_code" => "WAREHOUSE_IN",
            "name" => "入库",
            "icon" => "fa fa-home",
            "unique_code_for_paragraph" => "IN",
        ], [
            "unique_code" => "BREAKDOWN",
            "name" => "故障",
            "icon" => "fa fa-exclamation",
            "unique_code_for_paragraph" => "",
        ], [
            "unique_code" => "ALARM",
            "name" => "报警",
            "icon" => "fa fa-exclamation",
            "unique_code_for_paragraph" => "",
        ],])
            ->each(function ($type) {
                DB::table("entire_instance_log_types")
                    ->insert([
                        "created_at" => now(),
                        "updated_at" => now(),
                        "uuid" => Str::uuid(),
                        "unique_code" => $type["unique_code"],
                        "name" => $type["name"],
                        "icon" => $type["icon"],
                        "unique_code_for_paragraph" => $type["unique_code_for_paragraph"] ?? "",
                    ]);
                $this->comment("创建器材日志类型：{$type["unique_code"]} {$type["name"]}");
            });

        // 车间类型
        $scene_workshop = OrganizationWorkshopType::with([])->create(["uuid" => Str::uuid(), "sort" => 1, "unique_code" => "SCENE-WORKSHOP", "name" => "现场车间", "number_code" => "01",]);
        $this->comment("创建车间类型：{$scene_workshop->name}");
        $fix_workshop = OrganizationWorkshopType::with([])->create(["uuid" => Str::uuid(), "sort" => 2, "unique_code" => "FIX-WORKSHOP", "name" => "检修车间", "number_code" => "02",]);
        $this->comment("创建车间类型：{$fix_workshop->name}");
        $ele_workshop = OrganizationWorkshopType::with([])->create(["uuid" => Str::uuid(), "sort" => 3, "unique_code" => "ELE-WORKSHOP", "name" => "电子车间", "number_code" => "03",]);
        $this->comment("创建车间类型：{$ele_workshop->name}");
        $veh_workshop = OrganizationWorkshopType::with([])->create(["uuid" => Str::uuid(), "sort" => 4, "unique_code" => "VEH-WORKSHOP", "name" => "车载车间", "number_code" => "04",]);
        $this->comment("创建车间类型：{$veh_workshop->name}");
        $hump_workshop = OrganizationWorkshopType::with([])->create(["uuid" => Str::uuid(), "sort" => 5, "unique_code" => "HUMP-WORKSHOP", "name" => "驼峰车间", "number_code" => "05",]);
        $this->comment("创建车间类型：{$hump_workshop->name}");
        // 工区类型
        $scene_work_area = OrganizationWorkAreaType::with([])->create(["uuid" => Str::uuid(), "sort" => 1, "unique_code" => "SCENE-WORK-AREA", "name" => "现场工区"]);
        $this->comment("创建工区类型：{$scene_work_area->name}");
        $fix_work_area = OrganizationWorkAreaType::with([])->create(["uuid" => Str::uuid(), "sort" => 2, "unique_code" => "FIX-WORK-AREA", "name" => "检修车间专业工区"]);
        $this->comment("创建工区类型：{$fix_work_area->name}");
        $ele_work_area = OrganizationWorkAreaType::with([])->create(["uuid" => Str::uuid(), "sort" => 3, "unique_code" => "ELE-WORK-AREA", "name" => "电子车间专业工区"]);
        $this->comment("创建工区类型：{$ele_work_area->name}");
        $veh_work_area = OrganizationWorkAreaType::with([])->create(["uuid" => Str::uuid(), "sort" => 4, "unique_code" => "VEH-WORK-AREA", "name" => "车载车间专业工区"]);
        $this->comment("创建工区类型：{$veh_work_area->name}");
        $hump_work_area = OrganizationWorkAreaType::with([])->create(["uuid" => Str::uuid(), "sort" => 5, "unique_code" => "HUMP-WORK-AREA", "name" => "驼峰车间专业工区"]);
        $this->comment("创建工区类型：{$hump_work_area->name}");
        // 工区专业
        $point_switch_profession = OrganizationWorkAreaProfession::with([])->create(["uuid" => Str::uuid(), "sort" => 1, "unique_code" => "POINT-SWITCH", "name" => "转辙机"]);
        $this->comment("创建工区专业：{$point_switch_profession->name}");
        $relay_profession = OrganizationWorkAreaProfession::with([])->create(["uuid" => Str::uuid(), "sort" => 2, "unique_code" => "RELAY", "name" => "继电器"]);
        $this->comment("创建工区专业：{$relay_profession->name}");
        $synthesize_profession = OrganizationWorkAreaProfession::with([])->create(["uuid" => Str::uuid(), "sort" => 3, "unique_code" => "SYNTHESIZE", "name" => "综合"]);
        $this->comment("创建工区专业：{$synthesize_profession->name}");
        $power_supply_panel_profession = OrganizationWorkAreaProfession::with([])->create(["uuid" => Str::uuid(), "sort" => 4, "unique_code" => "POWER-SUPPLY-PANEL", "name" => "电源屏"]);
        $this->comment("创建工区专业：{$power_supply_panel_profession->name}");

        // 路局
        collect([
            ["unique_code" => "A00", "name" => "中国铁路总公司", "short_name" => "铁总", "sort" => 1, "paragraph_name" => "",],
            ["unique_code" => "A01", "name" => "哈尔滨铁路局", "short_name" => "哈尔滨局", "sort" => 2, "paragraph_name" => "哈尔滨",],
            ["unique_code" => "A02", "name" => "沈阳铁路局", "short_name" => "沈阳局", "sort" => 3, "paragraph_name" => "沈阳",],
            ["unique_code" => "A03", "name" => "北京铁路局", "short_name" => "北京局", "sort" => 4, "paragraph_name" => "北京",],
            ["unique_code" => "A04", "name" => "太原铁路局", "short_name" => "太原局", "sort" => 5, "paragraph_name" => "太原",],
            ["unique_code" => "A05", "name" => "呼和浩特铁路局", "short_name" => "呼和浩特局", "sort" => 6, "paragraph_name" => "呼和浩特",],
            ["unique_code" => "A06", "name" => "郑州铁路局", "short_name" => "郑州局", "sort" => 7, "paragraph_name" => "郑州",],
            ["unique_code" => "A07", "name" => "武汉铁路局", "short_name" => "武汉局", "sort" => 8, "paragraph_name" => "武汉",],
            ["unique_code" => "A08", "name" => "西安铁路局", "short_name" => "西安局", "sort" => 9, "paragraph_name" => "西安",],
            ["unique_code" => "A09", "name" => "济南铁路局", "short_name" => "济南局", "sort" => 10, "paragraph_name" => "济南",],
            ["unique_code" => "A10", "name" => "上海铁路局", "short_name" => "上海局", "sort" => 11, "paragraph_name" => "上海",],
            ["unique_code" => "A11", "name" => "南昌铁路局", "short_name" => "南昌局", "sort" => 12, "paragraph_name" => "南昌",],
            ["unique_code" => "A12", "name" => "广州铁路（集团）公司", "short_name" => "广州局", "sort" => 13, "paragraph_name" => "广州",],
            ["unique_code" => "A13", "name" => "南宁铁路局", "short_name" => "南宁局", "sort" => 14, "paragraph_name" => "南宁",],
            ["unique_code" => "A14", "name" => "成都铁路局", "short_name" => "成都局", "sort" => 15, "paragraph_name" => "成都",],
            ["unique_code" => "A15", "name" => "昆明铁路局", "short_name" => "昆明局", "sort" => 16, "paragraph_name" => "昆明",],
            ["unique_code" => "A16", "name" => "兰州铁路局", "short_name" => "兰州局", "sort" => 17, "paragraph_name" => "兰州",],
            ["unique_code" => "A17", "name" => "乌鲁木齐铁路局", "short_name" => "乌鲁木齐局", "sort" => 18, "paragraph_name" => "乌鲁木齐",],
            ["unique_code" => "A18", "name" => "青藏铁路公司", "short_name" => "青藏局", "sort" => 19, "paragraph_name" => "青藏",],
        ])->each(function ($datum) use (
            $scene_workshop,
            $fix_workshop,
            $ele_workshop,
            $veh_workshop,
            $hump_workshop,
            $scene_work_area,
            $fix_work_area,
            $ele_work_area,
            $veh_work_area,
            $hump_work_area,
            $point_switch_profession,
            $relay_profession,
            $synthesize_profession,
            $power_supply_panel_profession
        ) {
            $organization_railway = OrganizationRailway::with([])->create([
                "uuid" => Str::uuid(),
                "sort" => $datum["sort"],
                "unique_code" => $datum["unique_code"],
                "name" => $datum["name"],
                "short_name" => $datum["short_name"],
                "be_enable" => true,
            ]);
            $this->comment("创建路局：{$organization_railway->name}");

            if ($organization_railway->unique_code == "A12") {
                // 电务段
                collect([
                    ["unique_code" => "B048", "name" => "广州电务段", "short_name" => "广州",],
                    ["unique_code" => "B049", "name" => "长沙电务段", "short_name" => "长沙",],
                    ["unique_code" => "B050", "name" => "怀化电务段", "short_name" => "怀化",],
                    ["unique_code" => "B051", "name" => "衡阳电务段", "short_name" => "衡阳",],
                    ["unique_code" => "B052", "name" => "惠州电务段", "short_name" => "惠州",],
                    ["unique_code" => "B053", "name" => "肇庆电务段", "short_name" => "肇庆",],
                    ["unique_code" => "B074", "name" => "海口电务段", "short_name" => "海口",],
                ])->each(function ($datum) use (
                    $organization_railway,
                    $scene_workshop,
                    $fix_workshop,
                    $ele_workshop,
                    $veh_workshop,
                    $hump_workshop,
                    $scene_work_area,
                    $fix_work_area,
                    $ele_work_area,
                    $veh_work_area,
                    $hump_work_area,
                    $point_switch_profession,
                    $relay_profession,
                    $synthesize_profession,
                    $power_supply_panel_profession
                ) {
                    $organization_paragraph = OrganizationParagraph::with([])
                        ->create([
                            "uuid" => Str::uuid(),
                            "sort" => 1,
                            "unique_code" => $datum["unique_code"],
                            "name" => $datum["name"],
                            "be_enable" => true,
                            "organization_railway_uuid" => $organization_railway["uuid"],
                        ]);
                    $this->comment("创建站段：{$organization_paragraph->name}");

                    // 创建检修车间
                    $fix_organization_workshop = OrganizationWorkshop::with([])
                        ->create([
                            "uuid" => Str::uuid(),
                            "sort" => 0,
                            "unique_code" => "{$datum["unique_code"]}C01",
                            "name" => "{$datum["short_name"]}电务段检修车间",
                            "be_enable" => true,
                            "organization_workshop_type_uuid" => $fix_workshop->uuid,
                            "organization_paragraph_uuid" => $organization_paragraph->uuid,
                        ]);
                    $this->comment("创建车间：{$fix_organization_workshop->name}");

                    // 创建工区
                    $point_switch_organization_work_area = OrganizationWorkArea::with([])
                        ->create([
                            "uuid" => Str::uuid(),
                            "sort" => 0,
                            "unique_code" => "{$datum["unique_code"]}D001",
                            "name" => "转辙机工区",
                            "be_enable" => true,
                            "organization_work_area_type_uuid" => $fix_work_area->uuid,
                            "organization_work_area_profession_uuid" => $point_switch_profession->uuid,
                            "organization_workshop_uuid" => $fix_organization_workshop->uuid,
                        ]);
                    $this->comment("创建工区：{$point_switch_organization_work_area->name}");
                    $relay_organization_work_area = OrganizationWorkArea::with([])
                        ->create([
                            "uuid" => Str::uuid(),
                            "sort" => 0,
                            "unique_code" => "{$datum["unique_code"]}D002",
                            "name" => "继电器工区",
                            "be_enable" => true,
                            "organization_work_area_type_uuid" => $fix_work_area->uuid,
                            "organization_work_area_profession_uuid" => $relay_profession->uuid,
                            "organization_workshop_uuid" => $fix_organization_workshop->uuid,
                        ]);
                    $this->comment("创建工区：{$relay_organization_work_area->name}");
                    $synthesize_organization_work_area = OrganizationWorkArea::with([])
                        ->create([
                            "uuid" => Str::uuid(),
                            "sort" => 0,
                            "unique_code" => "{$datum["unique_code"]}D003",
                            "name" => "综合工区",
                            "be_enable" => true,
                            "organization_work_area_type_uuid" => $fix_work_area->uuid,
                            "organization_work_area_profession_uuid" => $synthesize_profession->uuid,
                            "organization_workshop_uuid" => $fix_organization_workshop->uuid,
                        ]);
                    $this->comment("创建工区：{$synthesize_organization_work_area->name}");
                    $power_supply_panel_organization_work_area = OrganizationWorkArea::with([])
                        ->create([
                            "uuid" => Str::uuid(),
                            "sort" => 0,
                            "unique_code" => "{$datum["unique_code"]}D004",
                            "name" => "电源屏工区",
                            "be_enable" => true,
                            "organization_work_area_type_uuid" => $fix_work_area->uuid,
                            "organization_work_area_profession_uuid" => $power_supply_panel_profession->uuid,
                            "organization_workshop_uuid" => $fix_organization_workshop->uuid,
                        ]);
                    $this->comment("创建工区：{$power_supply_panel_organization_work_area->name}");

                    // 创建电子车间
                    $ele_organization_workshop = OrganizationWorkshop::with([])
                        ->create([
                            "uuid" => Str::uuid(),
                            "sort" => 0,
                            "unique_code" => "{$datum["unique_code"]}C02",
                            "name" => "{$datum["short_name"]}电务段电子车间",
                            "be_enable" => true,
                            "organization_workshop_type_uuid" => $ele_workshop->uuid,
                            "organization_paragraph_uuid" => $organization_paragraph->uuid,
                        ]);
                    $this->comment("创建车间：{$ele_organization_workshop->name}");

                    // 创建车载车间
                    $veh_organization_workshop = OrganizationWorkshop::with([])
                        ->create([
                            "uuid" => Str::uuid(),
                            "sort" => 0,
                            "unique_code" => "{$datum["unique_code"]}C03",
                            "name" => "{$datum["short_name"]}电务段车载车间",
                            "be_enable" => true,
                            "organization_workshop_type_uuid" => $veh_workshop->uuid,
                            "organization_paragraph_uuid" => $organization_paragraph->uuid,
                        ]);
                    $this->comment("创建车间：{$veh_organization_workshop->name}");

                    // 创建驼峰车间
                    $hump_organization_workshop = OrganizationWorkshop::with([])
                        ->create([
                            "uuid" => Str::uuid(),
                            "sort" => 0,
                            "unique_code" => "{$datum["unique_code"]}C04",
                            "name" => "{$datum["short_name"]}电务段驼峰车间",
                            "be_enable" => true,
                            "organization_workshop_type_uuid" => $hump_workshop->uuid,
                            "organization_paragraph_uuid" => $organization_paragraph->uuid,
                        ]);
                    $this->comment("创建车间：{$hump_organization_workshop->name}");
                });
            }
        });

        $this->info("初始化数据完成");
    }

    /**
     * 初始化测试数据
     */
    private function testUser()
    {
        $rbacRole = RbacRole::with([])->first();

        // 路局用户
        $accountRailway = Account::with([])
            ->create([
                "uuid" => Str::uuid(),
                "username" => "railway",
                "password" => bcrypt("zces@1234"),
                "nickname" => "路局测试用户",
                "be_super_admin" => false,
            ]);
        $this->comment("创建用户：$accountRailway->nickname");
        PivotRbacRoleAndAccount::with([])->insert(["rbac_role_uuid" => $rbacRole->uuid, "account_uuid" => $accountRailway->uuid]);

        // 站段用户
        $accountParagraph = Account::with([])
            ->create([
                "uuid" => Str::uuid(),
                "username" => "paragraph",
                "password" => bcrypt("zces@1234"),
                "nickname" => "站段测试用户",
                "be_super_admin" => false,
            ]);
        $this->comment("创建用户：$accountParagraph->nickname");
        PivotRbacRoleAndAccount::with([])->insert(["rbac_role_uuid" => $rbacRole->uuid, "account_uuid" => $accountParagraph->uuid]);

        // 车间用户
        $accountWorkshop = Account::with([])
            ->create([
                "uuid" => Str::uuid(),
                "username" => "workshop",
                "password" => bcrypt("zces@1234"),
                "nickname" => "车间测试用户",
                "be_super_admin" => false,
            ]);
        $this->comment("创建用户：$accountWorkshop->nickname");
        PivotRbacRoleAndAccount::with([])->insert(["rbac_role_uuid" => $rbacRole->uuid, "account_uuid" => $accountWorkshop->uuid]);

        // 转辙机用户
        $accountPointSwitch = Account::with([])
            ->create([
                "uuid" => Str::uuid(),
                "username" => "pointSwitch",
                "password" => bcrypt("zces@1234"),
                "nickname" => "转辙机测试用户",
                "be_super_admin" => false,
            ]);
        $this->comment("创建用户：$accountPointSwitch->nickname");
        PivotRbacRoleAndAccount::with([])->insert(["rbac_role_uuid" => $rbacRole->uuid, "account_uuid" => $accountPointSwitch->uuid]);

        // 继电器用户
        $accountRelay = Account::with([])
            ->create([
                "uuid" => Str::uuid(),
                "username" => "relay",
                "password" => bcrypt("zces@1234"),
                "nickname" => "继电器测试用户",
                "be_super_admin" => false,
            ]);
        $this->comment("创建用户：$accountRelay->nickname");
        PivotRbacRoleAndAccount::with([])->insert(["rbac_role_uuid" => $rbacRole->uuid, "account_uuid" => $accountRelay->uuid]);

        // 综合用户
        $accountSynthesize = Account::with([])
            ->create([
                "uuid" => Str::uuid(),
                "username" => "synthesize",
                "password" => bcrypt("zces@1234"),
                "nickname" => "综合测试用户",
                "be_super_admin" => false,
            ]);
        $this->comment("创建用户：$accountSynthesize->nickname");
        PivotRbacRoleAndAccount::with([])->insert(["rbac_role_uuid" => $rbacRole->uuid, "account_uuid" => $accountSynthesize->uuid]);

        // 电源屏用户
        $accountPowerSupplyPanel = Account::with([])
            ->create([
                "uuid" => Str::uuid(),
                "username" => "powerSupplyPanel",
                "password" => bcrypt("zces@1234"),
                "nickname" => "电源屏测试用户",
                "be_super_admin" => false,
            ]);
        $this->comment("创建用户：$accountPowerSupplyPanel->nickname");
        PivotRbacRoleAndAccount::with([])->insert(["rbac_role_uuid" => $rbacRole->uuid, "account_uuid" => $accountPowerSupplyPanel->uuid]);
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
                $this->comment("创建权限：$rbacPermissionGroup->name → $rbacPermission->name");
                // 创建角色→权限
                DB::table("pivot_rbac_role_and_rbac_permissions")
                    ->insert([
                        "rbac_permission_uuid" => $rbacPermission->uuid,
                        "rbac_role_uuid" => $rbacRole->uuid,
                    ]);
                $this->comment("角色绑定权限：$rbacRole->name → $rbacPermissionGroup->name($rbacPermission->name)");
            });

        return $rbacPermissionGroup;
    }
}
