<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitStatementCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:statement {version} {conn_code?}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $__sql = [
        "b049_upgrade" => [
            "create index entire_instances_serial_number_index on entire_instances (serial_number)",
            "alter table accounts add task_station_check_account_level_id int default 0 not null comment '现场检查任务职级'",
            "alter table areas add drawing_direction enum('H', 'V') default 'H' not null comment '绘制方向：
H 垂直
V 水平'",
            "alter table categories add nickname varchar(8) default '' not null comment '别名'",
            "create table coding_logs
(
    id          int auto_increment
        primary key,
    created_at  datetime            null,
    updated_at  datetime            null,
    deleted_at  datetime            null,
    unique_code char(10) default '' not null,
    account_id  int      default 0  not null comment '操作人',
    type        tinyint  default 1  not null comment '类型：1 Excel赋码；2 系统赋码',
    status      tinyint  default 0  not null comment '状态：0未回退；1已回退；2不可回退',
    constraint coding_logs_unique_code_uindex
        unique (unique_code)
)
    comment '赋码日志表'",
            "create table collection_orders
(
    id                      int auto_increment
        primary key,
    created_at              datetime                                                    null,
    updated_at              datetime                                                    null,
    unique_code             varchar(50)                              default ''         not null comment '编码',
    station_install_user_id int                                      default 0          not null comment '第三方用户id',
    excel_url               varchar(200)                             default ''         not null comment 'excel文件路径',
    paragraph_unique_code   varchar(50)                              default ''         not null,
    type                    enum ('MATERIAL', 'LOCATION', 'STATION') default 'MATERIAL' not null comment '类型，MATERIAL数据，LOCATION定位，STATION车站或现场车间'
)
    comment '采集单表'",
            "create table collection_order_entire_instances
(
    id                                  int auto_increment
        primary key,
    created_at                          datetime                  null,
    updated_at                          datetime                  null,
    collection_order_unique_code        varchar(50)  default ''   not null comment '采集单编码',
    category_name                       varchar(50)  default ''   not null comment '种类名称',
    entire_model_name                   varchar(50)  default ''   not null comment '类型名称',
    sub_model_name                      varchar(50)  default ''   not null comment '型号名称',
    ex_factory_at                       datetime                  null comment '出厂日期',
    factory_number                      varchar(100) default ''   null comment '厂编号',
    service_life                        double(5, 2) default 0.00 not null comment '使用年限，0.0没有年限',
    cycle_fix_at                        datetime                  null comment '周期修时间',
    cycle_fix_year                      double(5, 2) default 0.00 null comment '周期修年限',
    last_installed_at                   datetime                  null comment '最后上道时间',
    factory_name                        varchar(100) default ''   null comment '供应商',
    workshop_unique_code                varchar(7)   default ''   not null comment '车间编码',
    station_unique_code                 varchar(6)   default ''   not null comment '车站编码',
    version_number                      varchar(100)              null comment '版本号',
    state_unique_code                   varchar(50)  default ''   not null,
    equipment_category_name             varchar(50)  default ''   not null comment '设备种类名称',
    equipment_entire_model_name         varchar(50)  default ''   not null comment '设备类型名称',
    equipment_sub_model_name            varchar(50)  default ''   not null comment '设备型号名称',
    entire_instance_identity_code       varchar(50)  default ''   not null comment '设备编码',
    install_location_unique_code        varchar(50)  default ''   not null comment '上道位置编码',
    manual_install_location_unique_code varchar(50)  default ''   not null comment '手填位置',
    last_fixed_at                       datetime                  null comment '最后检修时间',
    next_fixing_at                      datetime                  null comment '下次周期修时间',
    scraping_at                         datetime                  null comment '到期时间'
)
    comment '设备采集表'",
            "alter table distance add from_unique_code varchar(50) default '' not null",
            "alter table distance add to_unique_code varchar(50) default '' not null",
            "alter table distance add from_type enum('WORKSHOP', 'SCENE_WORKSHOP', 'STATION') null",
            "alter table distance add to_type enum('WORKSHOP', 'SCENE_WORKSHOP', 'STATION') null",
            "alter table distance add from_unique_code varchar(50) default '' not null",
            "alter table distance add to_unique_code varchar(50) default '' not null",
            "alter table entire_instances add last_installed_at datetime null comment '上道时间'",
            "alter table entire_instances add is_emergency bool default false not null comment '是否是应急备品'",
            "alter table entire_instances add source_type varchar(2) default '' not null comment '来源类型'",
            "alter table entire_instances add source_name varchar(50) default '' not null comment '来源名称'",
            "alter table entire_instances add is_part bool default false not null comment '是否是部件'",
            "alter table entire_instances add entire_instance_identity_code varchar(50) default '' not null comment '整件设备器材编号'",
            "alter table entire_instances add part_model_unique_code varchar(50) default '' not null comment '部件型号代码'",
            "alter table entire_instances add part_model_name varchar(50) default '' not null comment '部件型号名称'",
            "alter table entire_instances add part_category_id int default 0 not null comment '部件种类编号'",
            "alter table entire_instances add old_model_unique_code varchar(50) default '' not null comment '旧型号代码'",
            "alter table entire_instances add old_entire_model_unique_code varchar(50) default '' not null comment '旧类型代码'",
            "alter table entire_instances add old_category_unique_code varchar(50) default '' not null comment '旧种类代码'",
            "alter table entire_instances add old_model_name varchar(50) default '' not null comment '旧型号名称'",
            "alter table entire_instances add old_category_name varchar(50) default '' not null comment '旧种类名称'",
            "create table entire_instance_alarm_logs
(
    id                            int auto_increment
        primary key,
    created_at                    datetime                                                                null,
    updated_at                    datetime                                                                null,
    entire_instance_identity_code varchar(50)                                           default ''        not null,
    station_unique_code           varchar(6)                                            default ''        not null comment '车站编码',
    alarm_at                      datetime                                                                null comment '报警时间',
    alarm_level                   varchar(200)                                          default ''        not null comment '报警级别',
    alarm_content                 varchar(225)                                          default ''        not null comment '报警内容',
    alarm_cause                   varchar(255)                                          default ''        not null comment '报警原因',
    msg_id                        varchar(200)                                          default ''        not null,
    status                        enum ('WARNING', 'MANUAL_RELEASE', 'MONITOR_RELEASE') default 'WARNING' not null comment '状态'
)
    comment '设备报警记录表'",
            "create table entire_instance_excel_edit_reports
(
    id                    int auto_increment
        primary key,
    created_at            datetime                not null,
    updated_at            datetime                not null,
    processor_id          int          default 0  not null comment '操作用户',
    work_area_unique_code varchar(50)  default '' not null comment '所属工区',
    filename              varchar(100) default '' not null comment '文件保存路径',
    original_filename     varchar(50)  default '' not null comment '原始文件名'
)
    charset = utf8mb4",
            "alter table entire_instance_excel_tagging_reports add filename varchar(100) default '' not null comment '文件保存路径'",
            "alter table entire_instance_excel_tagging_reports add original_filename varchar(100) default '' not null comment '文件保存路径'",
            "alter table entire_models add life_year int default 15 not null comment '使用寿命'",
            "alter table entire_models add nickname varchar(8) default '' not null comment '类型或型号昵称'",
            "create table entire_model_images
(
    id                       int auto_increment
        primary key,
    created_at               datetime               not null,
    updated_at               datetime               not null,
    original_filename        varchar(50) default '' not null,
    original_extension       varchar(50) default '' not null,
    filename                 varchar(50) default '' not null,
    entire_model_unique_code varchar(50) default '' not null,
    url                      varchar(50) default '' not null comment '外部访问地址'
)
    comment '设备类型和器材型号照片表'",
            "alter table fix_mission_orders add complete varchar(50) null comment '按时完成数量'",
            "alter table fix_mission_orders add abort_date datetime not null comment '截止日期'",
            "alter table fix_mission_order_entire_instances add acceptance_date datetime null comment '验收日期'",
            "alter table fix_mission_order_entire_instances add work_area_id int not null comment '所属工区'",
            "alter table fix_mission_order_entire_instances add model_name varchar(50) null comment '型号名称'",
            "create table install_platoons
(
    id                       int auto_increment
        primary key,
    created_at               datetime               null,
    updated_at               datetime               null,
    name                     varchar(20) default '' not null,
    unique_code              varchar(50) default '' not null comment '排编码',
    install_room_unique_code varchar(50) default '' not null comment '机房关联编码',
    constraint install_platoons_unique_code_uindex
        unique (unique_code)
)
    comment '安装位置-排表'",
            "create table install_positions
(
    id                       int auto_increment
        primary key,
    created_at               datetime               null,
    updated_at               datetime               null,
    unique_code              varchar(50) default '' not null comment '位编码',
    install_tier_unique_code varchar(50) default '' not null comment '层关联编码',
    name                     varchar(50) default '' not null comment '位置名称',
    volume                   tinyint     default 1  not null comment '上道位置容量',
    constraint install_positions_unique_code_uindex
        unique (unique_code)
)
    comment '安装位置-位表'",
            "create table install_rooms
(
    id                  int auto_increment
        primary key,
    created_at          datetime                 null,
    updated_at          datetime                 null,
    unique_code         varchar(50) default ''   not null comment '机房编码',
    station_unique_code varchar(50) default ''   not null comment '车站编码',
    type                char(2)     default '11' not null comment '机房类型：11微机房',
    constraint install_rooms_unique_code_uindex
        unique (unique_code)
)
    comment '安装位置-机房表'",
            "create table install_shelves
(
    id                          int auto_increment
        primary key,
    created_at                  datetime               null,
    updated_at                  datetime               null,
    name                        varchar(20) default '' not null,
    unique_code                 varchar(50) default '' not null comment '架编码',
    install_platoon_unique_code varchar(50) default '' not null comment '排关联编码',
    constraint install_shelves_unique_code_uindex
        unique (unique_code)
)
    comment '安装位置-架表'",
            "create table install_tiers
(
    id                        int auto_increment
        primary key,
    created_at                datetime               null,
    updated_at                datetime               null,
    name                      varchar(20) default '' not null,
    unique_code               varchar(50) default '' not null comment '层编码',
    install_shelf_unique_code varchar(50) default '' not null comment '架关联编码',
    constraint install_tiers_unique_code_uindex
        unique (unique_code)
)
    comment '安装位置-层表'",
            "create table lp
(
    id int auto_increment
        primary key
)",
            "alter table maintains add line varchar(50) null",
            "alter table maintains add line_number varchar(50) null",
            "alter table maintains add line_unique_code varchar(50) null",
            "alter table maintains add parent_line_code varchar(50) null",
            "create table part_model_images
(
    id                     int auto_increment
        primary key,
    created_at             datetime               not null,
    updated_at             datetime               not null,
    original_filename      varchar(50) default '' not null,
    original_extension     varchar(50) default '' not null,
    filename               varchar(50) default '' not null,
    part_model_unique_code varchar(50) default '' not null,
    url                    varchar(50) default '' not null comment '外部访问地址'
)
    comment '部件型号照片表'",
            "create table pivot_coding_log_and_materials
(
    id                     int auto_increment
        primary key,
    created_at             datetime            null,
    updated_at             datetime            null,
    coding_log_unique_code char(10) default '' not null comment '赋码日志编码',
    material_unique_code   char(19) default '' not null comment '器材编码',
    constraint pivot_coding_log_and_materials_material_unique_code_uindex
        unique (material_unique_code)
)
    comment '赋码日志设备表'",
            "create index pivot_coding_log_and_materials_coding_log_unique_code_index on pivot_coding_log_and_materials (coding_log_unique_code)",
            "create table pivot_model_and_part_categories
(
    id                int auto_increment
        primary key,
    created_at        datetime               not null,
    updated_at        datetime               not null,
    name              varchar(50) default '' not null,
    model_unique_code varchar(50) default '' not null,
    part_category_id  int         default 0  not null
)
    comment '型号（不仅限型号）与部件种类对应关系表' charset = utf8mb4",
            "create table print_new_location_and_old_entire_instances
(
    id                                int auto_increment
        primary key,
    old_maintain_workshop_name        varchar(50) default '' null comment '旧车间名称',
    old_maintain_station_name         varchar(50) default '' not null comment '旧车站名称',
    old_maintain_location_code        varchar(50) default '' not null comment '旧组合位置',
    old_crossroad_number              varchar(50) default '' not null comment '旧道岔号',
    old_open_direction                varchar(50) default '' not null comment '旧开向',
    new_entire_instance_identity_code varchar(50) default '' null comment '新设备器材唯一编号',
    account_id                        int         default 0  not null comment '操作人'
)
    comment '打印新位置和老设备表（出所标签）'",
            "create table print_serial_numbers
(
    id            int auto_increment
        primary key,
    created_at    datetime               not null,
    updated_at    datetime               not null,
    serial_number varchar(50) default '' not null comment '所编号',
    account_id    int         default 0  not null comment '用户编号',
    model_name    varchar(50) default '' not null comment '型号名称'
)",
            "create table repair_base_status_out_fix_bills
(
    id                           int auto_increment
        primary key,
    created_at                   datetime                           not null,
    updated_at                   datetime                           not null,
    serial_number                varchar(50) default ''             not null comment '流水单号',
    operator_id                  int         default 0              not null comment '操作人',
    status                       enum ('ORIGIN', 'FINISH', 'CLOSE') null comment '状态：ORIGIN任务开启、FINISH任务完成、CLOSE关闭',
    year                         int         default 0              not null comment '任务所属年份',
    month                        int         default 0              not null comment '任务所属月份',
    work_area_unique_code        varchar(50) default ''             not null comment '所属工区',
    maintain_station_unique_code varchar(50) default ''             not null comment '所属车站',
    scene_workshop_unique_code   varchar(50) default ''             not null comment '所属现场车间'
)
    comment '状态修出所任务'",
            "create table repair_base_status_out_fix_entire_instances
(
    id                           int auto_increment
        primary key,
    bill_id                      int         default 0  not null,
    old                          varchar(50) default '' not null comment '待下道设备器材代码',
    new                          varchar(50) default '' not null comment '待出所设备器材代码',
    is_scan_in                   tinyint(1)  default 0  not null comment '是否入所扫码',
    is_scan_out                  tinyint(1)  default 0  not null comment '是否出所扫码',
    in_warehouse_sn              varchar(50) default '' not null comment '入所单流水号',
    out_warehouse_sn             varchar(50) default '' not null comment '出所单流水号',
    maintain_station_unique_code varchar(50) default '' not null comment '车站代码',
    scene_workshop_unique_code   varchar(50) default '' not null comment '现场车间代码'
)
    comment '状态修任务设备器材表'",
            "create table sub_models
(
    id                       int unsigned auto_increment
        primary key,
    created_at               datetime                null,
    updated_at               datetime                null,
    name                     varchar(50)             not null comment '名称',
    unique_code              char(7)      default '' not null comment '型号编码',
    entire_model_unique_code char(5)      default '' not null comment '类型编码',
    remark                   varchar(200) default '' not null comment '备注',
    service_life             double(5, 2)            null comment '使用年限',
    img_path                 varchar(200) default '' not null comment '图片',
    constraint sub_models_unique_code_uindex
        unique (unique_code)
)
    comment '器材型号表'",
            "create index sub_models_entire_model_unique_code_index on sub_models (entire_model_unique_code)",
            "create table task_station_check_projects
(
    id         int auto_increment
        primary key,
    created_at datetime                null,
    updated_at datetime                null,
    name       varchar(100) default '' not null,
    type       tinyint      default 1  not null comment '类型：1临时、2年表维修'
)
    comment '现场检修任务项目表'",
            "create table tmp_entire_instance_collections
(
    id                            int auto_increment
        primary key,
    created_at                    datetime                  null,
    updated_at                    datetime                  null,
    station_install_user_id       int          default 0    not null comment '第三方用户id',
    category_name                 varchar(50)               not null comment '种类名称',
    entire_model_name             varchar(50)               not null comment '类型名称',
    sub_model_name                varchar(50)               not null comment '型号名称',
    ex_factory_at                 datetime                  null comment '出厂日期',
    factory_number                varchar(100) default ''   null comment '厂编号',
    service_life                  double(5, 2) default 0.00 not null comment '使用年限，0.0没有年限',
    cycle_fix_at                  datetime                  null comment '周期修时间',
    cycle_fix_year                double(5, 2) default 0.00 not null comment '周期修年限',
    last_installed_at             datetime                  null comment '最后上道时间',
    factory_name                  varchar(100) default ''   null comment '供应商名称',
    workshop_unique_code          varchar(7)   default ''   not null comment '车间编码',
    station_unique_code           varchar(6)   default ''   not null comment '车站编码',
    version_number                varchar(100)              null comment '版本号',
    state_unique_code             varchar(50)  default ''   not null,
    equipment_category_name       varchar(50)  default ''   not null comment '设备种类名称',
    equipment_entire_model_name   varchar(50)  default ''   not null comment '设备类型名称',
    equipment_sub_model_name      varchar(50)  default ''   not null comment '设备型号名称',
    entire_instance_identity_code varchar(50)  default ''   not null comment '设备编码',
    install_location_unique_code  varchar(50)  default ''   not null comment '上道位置编码'
)
    comment '临时设备采集表'",
            "alter table warehouse_reports add work_area_unique_code varchar(50) default '' not null comment '所属工区代码'",
            "alter table warehouse_reports add sign_image_url varchar(50) default '' not null comment '出入所签字照片位置'",
            "alter table warehouse_reports add sign_image longtext null comment 'base64格式签名'",
            "alter table warehouse_report_entire_instances add line_unique_code varchar(50) default '' not null comment '线别代码'",
            "alter table platoons
	add type enum('', 'FIXED', 'FIXING', 'MATERIAL', 'EMERGENCY') default '' not null comment '排类型：
FIXED：成品
FIXING：待修
MAETRIAL：材料
EMERGENCY：应急'",
            "create table warehouse_report_display_board_statistics
(
    id                         int auto_increment
        primary key,
    created_at                 datetime                        not null,
    updated_at                 datetime                        not null,
    category_unique_code       varchar(50) default ''          not null,
    entire_model_unique_code   varchar(50) default ''          null,
    model_unique_code          varchar(50) default ''          not null,
    category_name              varchar(50) default ''          not null,
    entire_model_name          varchar(50) default ''          not null,
    model_name                 varchar(50) default ''          not null,
    scene_workshop_unique_code varchar(50) default ''          not null,
    scene_workshop_name        varchar(50) default ''          not null,
    station_unique_code        varchar(50) default ''          not null,
    count                      int         default 0           not null,
    direction                  enum ('IN', 'OUT')              not null comment '出入所方向',
    time_type                  enum ('TODAY', 'WEEK', 'MONTH') not null,
    work_area_unique_code      varchar(50) default ''          not null
)
    comment '出入所统计展板表'",
        ],
        "2.6.7-33" => [
            "alter table categories add nickname varchar(50) default '' not null comment '别名'",
            "alter table entire_models add nickname varchar(50) default '' not null comment '别名'",
            "alter table install_positions add volume tinyint default 1 not null comment '上道位置容量'",
            "alter table entire_instance_excel_tagging_reports add filename varchar(100) default '' not null comment '文件名'",
            "alter table entire_instance_excel_tagging_reports add original_filename varchar(100) default '' not null comment '原始文件名'",
            "create index entire_instances_serial_number_index on entire_instances (serial_number)",
            "create table source_names
(
    id          int auto_increment
        primary key,
    created_at  datetime               not null,
    updated_at  datetime               not null,
    name        varchar(50) default '' not null,
    source_type varchar(2)  default '' not null
)
    comment '来源名称表'",
            "alter table categories add workshop_types varchar(50) default '' not null comment '车间显示范围'",
        ],
        "2.6.7-34" => [
            "create table if not exists materials
(
    id                          bigint auto_increment
        primary key,
    created_at                  datetime                                                    not null,
    updated_at                  datetime                                                    not null,
    deleted_at                  datetime                                                    null,
    identity_code               varchar(64)                                default ''       not null,
    asset_code                  varchar(64)                                default ''       not null comment '物资编码',
    fixed_asset_code            varchar(64)                                default ''       not null comment '固资编码',
    material_type_identity_code varchar(64)                                default ''       not null comment '材料类型编号',
    workshop_unique_code        varchar(50)                                default ''       not null comment '车间代码',
    station_unique_code         varchar(50)                                default ''       not null comment '车站代码',
    position_unique_code        varchar(50)                                default ''       not null comment '仓库位置代码',
    work_area_unique_code       varchar(50)                                default ''       not null comment '工区代码',
    source_type                 varchar(2)                                 default ''       not null comment '来源类型',
    source_name                 varchar(50)                                default ''       not null comment '来源名城',
    status                      enum ('TAGGED', 'STORED_IN', 'STORED_OUT') default 'TAGGED' not null comment '状态：
TAGGED：已赋码
STORED_IN：已入库
STORED_OUT：已出库',
    tmp_scan                    datetime                                                    null comment '出入库扫码标记',
    tmp_scan_processor_id       int                                        default 0        not null comment '出入库扫码操作人',
    operation_direction         enum ('', 'IN', 'OUT')                     default ''       not null comment '出入库操作方向',
    constraint materials_identity_code_uindex
        unique (identity_code)
)
    comment '材料'",
            "create table if not exists material_types
(
    id            int auto_increment
        primary key,
    created_at    datetime               not null,
    updated_at    datetime               not null,
    identity_code varchar(64) default '' not null comment '材料类型唯一编号',
    name          varchar(50) default '' not null comment '材料类型名称',
    unit          varchar(50) default '' not null comment '单位',
    creator_id    int         default 0  not null comment '创建人编号',
    constraint material_types_identity_code_uindex
        unique (identity_code),
    constraint material_types_name_uindex
        unique (name)
)
    comment '材料类型表'",
            "create table if not exists material_storehouse_orders
(
    id            bigint auto_increment
        primary key,
    created_at    datetime               not null,
    updated_at    datetime               not null,
    serial_number varchar(64) default '' not null comment '流水号',
    operator_id   int         default 0  not null comment '操作人',
    direction     enum ('IN', 'OUT')     null comment '出入库方向'
)
    comment '材料出入库单'",
            "create table if not exists material_storehouse_order_items
(
    id                                      bigint auto_increment
        primary key,
    created_at                              datetime               not null,
    updated_at                              datetime               not null,
    material_storehouse_order_serial_number varchar(50) default '' not null comment '材料出入库单流水号',
    material_identity_code                  varchar(64) default '' not null comment '材料唯一编号',
    workshop_unique_code                    varchar(50) default '' not null comment '车间编号',
    station_unique_code                     varchar(50) default '' not null comment '车站编号',
    work_area_unique_code                   varchar(50) default '' not null comment '工区编号',
    position_unique_code                    varchar(50) default '' not null comment '仓库位置编号'
)
    comment '材料出入库详情'",
            "create table if not exists material_logs
(
    id                     bigint auto_increment
        primary key,
    created_at             datetime                                                        not null,
    updated_at             datetime                                                        not null,
    material_identity_code varchar(64)                                   default ''        not null comment '材料唯一编号',
    operator_id            int                                           default 0         not null comment '操作人编号',
    workshop_unique_code   varchar(50)                                   default ''        not null,
    station_unique_code    varchar(50)                                   default ''        not null,
    work_area_unique_code  varchar(50)                                   default ''        not null,
    position_unique_code   varchar(50)                                   default ''        not null,
    content                varchar(100)                                  default ''        not null,
    type                   enum ('', 'TAGGING', 'STORE_IN', 'STORE_OUT') default 'TAGGING' not null comment '类型：
TAGGING：赋码
STORE_IN：入库
STORE_OUT：出库'
)
    comment '材料操作日志'",
            "create table if not exists railroad_grade_crosses
(
    id int auto_increment,
    created_at datetime null,
    updated_at datetime null,
    unique_code varchar(50) not null,
    name varchar(50) not null,
    constraint railroad_grade_crosses_pk
        primary key (id)
) comment '道口'",
            "create unique index railroad_grade_crosses_name_uindex on railroad_grade_crosses (name)",
            "create unique index railroad_grade_crosses_unique_code_uindex on railroad_grade_crosses (unique_code)",
            "create table if not exists centres
(
    id int auto_increment,
    created_at datetime null,
    updated_at datetime null,
    unique_code varchar(50) not null,
    name varchar(50) not null,
    constraint centres_pk
        primary key (id)
)
    comment '中心'",
            "create unique index centres_name_uindex on centres (name)",
            "create unique index centres_unique_code_uindex on centres (unique_code)",
            "alter table centres add scene_workshop_unique_code varchar(50) default '' not null comment '所属现场车间代码'",
            "alter table entire_instances add railroad_grade_cross_unique_code varchar(50) default '' not null comment '上道位置-道口'",
            "alter table entire_instances add centre_unique_code varchar(50) default '' not null comment '上道位置-中心'",
            "create table if not exists pivot_line_railroad_grade_crosses
(
    id int auto_increment,
    created_at datetime null,
    updated_at datetime null,
    line_id int default 0 not null comment '线别id',
    railroad_grade_cross_id int default 0 not null comment '道口id',
    constraint pivot_line_railroad_grade_crosses_pk
        primary key (id)
)
    comment '线别与道口对应关系'",
            "create table if not exists pivot_line_centres
(
    id int auto_increment,
    created_at datetime null,
    updated_at datetime null,
    line_id int default 0 not null comment '线别id',
    centre_id int default 0 not null comment '中心id',
    constraint pivot_line_centres_pk
        primary key (id)
)
    comment '线别与中心代码'",
            "create table if not exists bind_serial_numbers
(
    id                 int auto_increment
        primary key,
    created_at         datetime               null,
    updated_at         datetime               null,
    identity_code      varchar(50) default '' not null,
    serial_number      varchar(50) default '' null,
    category_name      varchar(50) default '' not null,
    entire_model_name  varchar(50) default '' not null,
    sub_model_name     varchar(50) default '' not null,
    processor_nickname varchar(50) default '' not null
)
    comment '唯一编号绑定所编号'",
            "alter table entire_instance_excel_tagging_reports add upload_create_device_excel_error_filename varchar(100) default '' not null",
            "alter table entire_instance_excel_tagging_reports add correct_count int default 0 not null comment '成功数量'",
            "alter table entire_instance_excel_tagging_reports add fail_count int default 0 not null comment '失败数量'",
            "alter table install_shelves add type enum('CABINET') default 'CABINET' not null comment '架类型:CABINET机柜'",
            "alter table install_shelves add line_unique_code varchar(50) default '' not null comment '所属线别'",
            "alter table install_shelves add image_path varchar(100) default '' not null comment '图片路径'",
            "alter table install_shelves add sort tinyint unsigned default 0 not null comment '排序'",
            "alter table install_shelves add vr_image_path varchar(50) default '' not null comment 'vr图片'",
            "alter table install_shelves add var_lon varchar(100) default '' not null comment 'vr精度'",
            "alter table install_shelves add vr_lat varchar(100) default '' not null comment 'vr纬度'",
            "alter table entire_instances add maintain_work_area_unique_code varchar(50) default '' not null comment '出所工区代码'",
            "alter table entire_instances add last_scene_breakdown_description text null comment '现场故障描述'",
            "alter table entire_instances add last_line_unique_code varchar(50) default '' not null comment '最后上道线别'",
            "alter table entire_instances add lock_type varchar(50) default '' not null comment '锁类型'",
            "alter table entire_instances add lock_description varchar(150) default '' not null comment '锁描述'",
            "alter table categories add is_show bool default 1 not null comment '是否显示'",
            "alter table categories drop column workshop_types",
            "alter table work_areas add is_show bool default true not null comment '是否显示'",
            "alter table entire_instances alter column status set default 'FIXING'",
            "alter table entire_instances modify status enum('BUY_IN', 'INSTALLING', 'INSTALLED', 'FIXING', 'FIXED', 'RETURN_FACTORY', 'FACTORY_RETURN', 'SCRAP', 'TRANSFER_OUT', 'TRANSFER_IN', 'UNINSTALLED', 'FRMLOSS', 'SEND_REPAIR', 'REPAIRING', 'UNINSTALLED_BREAKDOWN') default 'FIXING' not null comment '设备状态：BUY_IN：新购、INSTALLING：备品、INSTALLED：上道使用、FIXING：待修、FIXED：成品、RETURN_FACTORY：返厂、FACTORY_RETURN：返厂返回、SCRAP：报废、TRANSFER_OUT：出所在途、TRANSFER_IN：入所在途、UNINSTALLED：下道、FRMLOSS：报损、SEND_REPAIR：送修、REPAIRING：检修中'",
            // "update entire_instance_logs set url = '' where type = 2 and url != ''",
            // "update entire_instance_logs set url = '' where type = 4 and url != ''",
        ],
        "2.6.10" => [
            "create table if not exists materials
(
    id                          bigint auto_increment
        primary key,
    created_at                  datetime                                                    not null,
    updated_at                  datetime                                                    not null,
    deleted_at                  datetime                                                    null,
    identity_code               varchar(64)                                default ''       not null,
    asset_code                  varchar(64)                                default ''       not null comment '物资编码',
    fixed_asset_code            varchar(64)                                default ''       not null comment '固资编码',
    material_type_identity_code varchar(64)                                default ''       not null comment '材料类型编号',
    workshop_unique_code        varchar(50)                                default ''       not null comment '车间代码',
    station_unique_code         varchar(50)                                default ''       not null comment '车站代码',
    position_unique_code        varchar(50)                                default ''       not null comment '仓库位置代码',
    work_area_unique_code       varchar(50)                                default ''       not null comment '工区代码',
    source_type                 varchar(2)                                 default ''       not null comment '来源类型',
    source_name                 varchar(50)                                default ''       not null comment '来源名城',
    status                      enum ('TAGGED', 'STORED_IN', 'STORED_OUT') default 'TAGGED' not null comment '状态：
TAGGED：已赋码
STORED_IN：已入库
STORED_OUT：已出库',
    tmp_scan                    datetime                                                    null comment '出入库扫码标记',
    tmp_scan_processor_id       int                                        default 0        not null comment '出入库扫码操作人',
    operation_direction         enum ('', 'IN', 'OUT')                     default ''       not null comment '出入库操作方向',
    constraint materials_identity_code_uindex
        unique (identity_code)
)
    comment '材料'",
            "create table if not exists material_types
(
    id            int auto_increment
        primary key,
    created_at    datetime               not null,
    updated_at    datetime               not null,
    identity_code varchar(64) default '' not null comment '材料类型唯一编号',
    name          varchar(50) default '' not null comment '材料类型名称',
    unit          varchar(50) default '' not null comment '单位',
    creator_id    int         default 0  not null comment '创建人编号',
    constraint material_types_identity_code_uindex
        unique (identity_code),
    constraint material_types_name_uindex
        unique (name)
)
    comment '材料类型表'",
            "create table if not exists material_storehouse_orders
(
    id            bigint auto_increment
        primary key,
    created_at    datetime               not null,
    updated_at    datetime               not null,
    serial_number varchar(64) default '' not null comment '流水号',
    operator_id   int         default 0  not null comment '操作人',
    direction     enum ('IN', 'OUT')     null comment '出入库方向'
)
    comment '材料出入库单'",
            "create table if not exists material_storehouse_order_items
(
    id                                      bigint auto_increment
        primary key,
    created_at                              datetime               not null,
    updated_at                              datetime               not null,
    material_storehouse_order_serial_number varchar(50) default '' not null comment '材料出入库单流水号',
    material_identity_code                  varchar(64) default '' not null comment '材料唯一编号',
    workshop_unique_code                    varchar(50) default '' not null comment '车间编号',
    station_unique_code                     varchar(50) default '' not null comment '车站编号',
    work_area_unique_code                   varchar(50) default '' not null comment '工区编号',
    position_unique_code                    varchar(50) default '' not null comment '仓库位置编号'
)
    comment '材料出入库详情'",
            "create table if not exists material_logs
(
    id                     bigint auto_increment
        primary key,
    created_at             datetime                                                        not null,
    updated_at             datetime                                                        not null,
    material_identity_code varchar(64)                                   default ''        not null comment '材料唯一编号',
    operator_id            int                                           default 0         not null comment '操作人编号',
    workshop_unique_code   varchar(50)                                   default ''        not null,
    station_unique_code    varchar(50)                                   default ''        not null,
    work_area_unique_code  varchar(50)                                   default ''        not null,
    position_unique_code   varchar(50)                                   default ''        not null,
    content                varchar(100)                                  default ''        not null,
    type                   enum ('', 'TAGGING', 'STORE_IN', 'STORE_OUT') default 'TAGGING' not null comment '类型：
TAGGING：赋码
STORE_IN：入库
STORE_OUT：出库'
)
    comment '材料操作日志'",
            "create table if not exists railroad_grade_crosses
(
    id int auto_increment,
    created_at datetime null,
    updated_at datetime null,
    unique_code varchar(50) not null,
    name varchar(50) not null,
    constraint railroad_grade_crosses_pk
        primary key (id)
) comment '道口'",
            "create unique index railroad_grade_crosses_name_uindex on railroad_grade_crosses (name)",
            "create unique index railroad_grade_crosses_unique_code_uindex on railroad_grade_crosses (unique_code)",
            "create table if not exists centres
(
    id int auto_increment,
    created_at datetime null,
    updated_at datetime null,
    unique_code varchar(50) not null,
    name varchar(50) not null,
    constraint centres_pk
        primary key (id)
)
    comment '中心'",
            "create unique index centres_name_uindex on centres (name)",
            "create unique index centres_unique_code_uindex on centres (unique_code)",
            "alter table centres add scene_workshop_unique_code varchar(50) default '' not null comment '所属现场车间代码'",
            "alter table entire_instances add railroad_grade_cross_unique_code varchar(50) default '' not null comment '上道位置-道口'",
            "alter table entire_instances add centre_unique_code varchar(50) default '' not null comment '上道位置-中心'",
            "create table if not exists pivot_line_railroad_grade_crosses
(
    id int auto_increment,
    created_at datetime null,
    updated_at datetime null,
    line_id int default 0 not null comment '线别id',
    railroad_grade_cross_id int default 0 not null comment '道口id',
    constraint pivot_line_railroad_grade_crosses_pk
        primary key (id)
)
    comment '线别与道口对应关系'",
            "create table if not exists pivot_line_centres
(
    id int auto_increment,
    created_at datetime null,
    updated_at datetime null,
    line_id int default 0 not null comment '线别id',
    centre_id int default 0 not null comment '中心id',
    constraint pivot_line_centres_pk
        primary key (id)
)
    comment '线别与中心代码'",
            "create table if not exists bind_serial_numbers
(
    id                 int auto_increment
        primary key,
    created_at         datetime               null,
    updated_at         datetime               null,
    identity_code      varchar(50) default '' not null,
    serial_number      varchar(50) default '' null,
    category_name      varchar(50) default '' not null,
    entire_model_name  varchar(50) default '' not null,
    sub_model_name     varchar(50) default '' not null,
    processor_nickname varchar(50) default '' not null
)
    comment '唯一编号绑定所编号'",
            "alter table entire_instance_excel_tagging_reports add upload_create_device_excel_error_filename varchar(100) default '' not null",
            "alter table entire_instance_excel_tagging_reports add correct_count int default 0 not null comment '成功数量'",
            "alter table entire_instance_excel_tagging_reports add fail_count int default 0 not null comment '失败数量'",
            "alter table install_shelves add type enum('CABINET') default 'CABINET' not null comment '架类型:CABINET机柜'",
            "alter table install_shelves add line_unique_code varchar(50) default '' not null comment '所属线别'",
            "alter table install_shelves add image_path varchar(100) default '' not null comment '图片路径'",
            "alter table install_shelves add sort tinyint unsigned default 0 not null comment '排序'",
            "alter table install_shelves add vr_image_path varchar(50) default '' not null comment 'vr图片'",
            "alter table install_shelves add var_lon varchar(100) default '' not null comment 'vr精度'",
            "alter table install_shelves add vr_lat varchar(100) default '' not null comment 'vr纬度'",
            "alter table entire_instances add maintain_work_area_unique_code varchar(50) default '' not null comment '出所工区代码'",
            "alter table entire_instances add last_scene_breakdown_description text null comment '现场故障描述'",
            "alter table entire_instances add last_line_unique_code varchar(50) default '' not null comment '最后上道线别'",
            "alter table entire_instances add lock_type varchar(50) default '' not null comment '锁类型'",
            "alter table entire_instances add lock_description varchar(150) default '' not null comment '锁描述'",
            "alter table categories add is_show bool default 1 not null comment '是否显示'",
            "alter table categories drop column workshop_types",
            "alter table work_areas add is_show bool default true not null comment '是否显示'",
            "alter table entire_instances alter column status set default 'FIXING'",
            "alter table entire_instances modify status enum('BUY_IN', 'INSTALLING', 'INSTALLED', 'FIXING', 'FIXED', 'RETURN_FACTORY', 'FACTORY_RETURN', 'SCRAP', 'TRANSFER_OUT', 'TRANSFER_IN', 'UNINSTALLED', 'FRMLOSS', 'SEND_REPAIR', 'REPAIRING', 'UNINSTALLED_BREAKDOWN') default 'FIXING' not null comment '设备状态：BUY_IN：新购、INSTALLING：备品、INSTALLED：上道使用、FIXING：待修、FIXED：成品、RETURN_FACTORY：返厂、FACTORY_RETURN：返厂返回、SCRAP：报废、TRANSFER_OUT：出所在途、TRANSFER_IN：入所在途、UNINSTALLED：下道、FRMLOSS：报损、SEND_REPAIR：送修、REPAIRING：检修中'",
            "alter table repair_base_breakdown_order_temp_entire_instances add workshop_unique_code varchar(50) default '' not null comment '车间'",
            "alter table repair_base_breakdown_order_temp_entire_instances add station_unique_code varchar(50) default '' not null comment '车间'",
            "alter table repair_base_breakdown_order_temp_entire_instances add line_unique_code varchar(50) default '' not null comment '线别'",
            "alter table repair_base_breakdown_order_temp_entire_instances add maintain_location_code varchar(50) default '' not null comment '室内组合位置'",
            "alter table repair_base_breakdown_order_temp_entire_instances add crossroad_number varchar(50) default '' not null comment '道岔号'",
            "alter table repair_base_breakdown_order_temp_entire_instances add open_direction varchar(50) default '' not null comment '开向'",
            "alter table repair_base_breakdown_order_temp_entire_instances add warehouse_in_breakdown_note text null comment '入所故障备注'",
            "alter table repair_base_breakdown_order_entire_instances add line_unique_code varchar(50) default '' not null comment '所属线别'",
            "alter table repair_base_breakdown_order_entire_instances add warehouse_in_breakdown_note text null comment '入所故障备注'",
            "alter table breakdown_logs modify `explain` text not null comment '描述'",
            "alter table repair_base_breakdown_order_entire_instances add breakdown_type_ids varchar(100) default '' not null comment '故障类型编号组'",
            "alter table repair_base_breakdown_orders add work_area_unique_code varchar(50) default '' not null comment '所属工区'",
            "alter table repair_base_breakdown_order_entire_instances add fix_duty_officer varchar(50) default '' not null comment '返修责任者'",
            "alter table repair_base_breakdown_order_temp_entire_instances add fix_duty_officer varchar(50) default '' not null comment '返修责任者'",
            "alter table repair_base_breakdown_order_temp_entire_instances add last_fixed_at datetime null comment '上一次检修时间'",
            "alter table repair_base_breakdown_order_temp_entire_instances add last_fixer_name varchar(50) default '' not null comment '上一次检修人'",
            "alter table repair_base_breakdown_order_temp_entire_instances add last_checked_at datetime null comment '上一次验收时间'",
            "alter table repair_base_breakdown_order_temp_entire_instances add last_checker_name varchar(50) default '' not null comment '上一次验收人'",
            "alter table repair_base_breakdown_order_entire_instances add last_fixed_at datetime null comment '上一次检修时间'",
            "alter table repair_base_breakdown_order_entire_instances add last_fixer_name varchar(50) default '' not null comment '上一次检修人'",
            "alter table repair_base_breakdown_order_entire_instances add last_checked_at datetime null comment '上一次验收时间'",
            "alter table repair_base_breakdown_order_entire_instances add last_checker_name varchar(50) default '' not null comment '上一次验收人'",
            "update rbac_menus set title = '故障修管理' where id = 131",
            "alter table warehouse_materials add position_name varchar(50) default '' not null comment '入库位置名称'",
            "alter table entire_instances add deleter_id int null comment '删除操作人'",
            "alter table entire_instance_excel_tagging_reports add is_rollback bool default 0 not null comment '是否回退'",
            "alter table entire_instance_excel_tagging_reports add rollback_processor_id int default 0 not null comment '回退操作人'",
            "alter table entire_instance_excel_tagging_reports add rollback_processed_at datetime null comment '回退执行时间'",
            "alter table tmp_materials modify state enum('IN_WAREHOUSE', 'OUT_WAREHOUSE', 'SCRAP', 'FRMLOSS', 'SEND_REPAIR') default 'IN_WAREHOUSE' not null comment ' - 入库：IN
_WAREHOUSE
 - 出库（确定出库）：OUT_WAREHOUSE
 - 报废（点击报废）：SCRAP
 - 报损（点击报损）：FRMLOSS
 - 送修：SEND_REPAIR';",
            "alter table warehouses modify direction enum('IN_WAREHOUSE', 'OUT_WAREHOUSE', 'SCRAP', 'FRMLOSS') default 'IN_WAREHOUSE' not null comment '入库（绑定位置）：IN_WAREHOUSE
出库（确定出库）：OUT_WAREHOUSE
报废（选择报废）：SCRAP
报损（选择报损）：FRMLOSS '",
            "create table if not exists pivot_breakdown_order_entire_instance_and_breakdown_types
(
    id                                 bigint unsigned auto_increment
        primary key,
    created_at                         datetime      null,
    updated_at                         datetime      null,
    breakdown_type_id                  int default 0 not null,
    breakdown_order_entire_instance_id int default 0 not null
)
    comment '故障入所与故障类型'",
            "create table if not exists paragraph_center_measurements
(
    id                       bigint unsigned auto_increment
        primary key,
    created_at               datetime                null,
    updated_at               datetime                null,
    account_id               int unsigned default 0  not null,
    serial_number            varchar(50)  not null comment '流水号',
    name                     varchar(50)  default '' not null comment '名称',
    type                     tinyint      default 0  not null comment '类型：1除尘、2检修',
    category_unique_code     char(3)      default '' not null comment '器材种类编码',
    entire_model_unique_code char(5)      default '' not null comment '器材类型编码',
    sub_model_unique_code    char(7)      default '' not null comment '器材型号编码',
    business_type            tinyint      default 1  not null comment '业务类型：1问卷型；2步骤型'
)
    comment '检修模板（段中心）'",
            "create index overhaul_templates_account_id_index on paragraph_center_measurements (account_id)",
            "create table paragraph_center_measurement_steps
(
    uuid                            char(36)         default '' not null,
    created_at                      datetime                    null,
    updated_at                      datetime                    null,
    paragraph_center_measurement_sn varchar(50)                 not null comment '检修模板流水号',
    sort                            tinyint unsigned default 1  not null comment '排序',
    data                            longtext                    null,
    constraint paragraph_center_measurement_steps_uuid_uindex
        unique (uuid)
)
    comment '检修模板步骤表'",
            "create index overhaul_template_steps_overhaul_template_id_index on paragraph_center_measurement_steps (paragraph_center_measurement_sn)",
            "alter table paragraph_center_measurement_steps add primary key (uuid)",
            "update rbac_menus set deleted_at=NOW() where id=67",
            "alter table accounts add page_size varchar(50) default 100 not null comment '搜索页面页容量'",
            "create table if not exists scrap_temp_entire_instances
(
    id                            bigint unsigned auto_increment
        primary key,
    created_at                    datetime               null,
    updated_at                    datetime               null,
    entire_instance_identity_code varchar(50) default '' not null comment '器材唯一编号',
    warehouse_sn                  varchar(50) default '' not null comment '报废单流水号',
    processor_id                  int         default 0  not null comment '操作人'
)
    comment '待报废器材表'",
            "alter table scrap_temp_entire_instances drop column warehouse_sn;",
            "update rbac_menus set action_as='ScrapOrder:index', uri='/entire/scrapOrder' where id = 111",
            "alter table send_repairs add sign_image longtext null comment '签字图片'",
            "alter table warehouse_report_entire_instances add maintain_workshop_name varchar(50) default '' not null comment '车间名称'",
        ],
        "2.6.10-1" => [
            "alter table entire_instances add maintain_section_name varchar(50) default '' not null comment '区段名称'",
            "alter table entire_instances add last_maintain_section_name varchar(50) default '' not null comment '上次区间上道名称'",
            "alter table entire_instances add maintain_send_or_receive varchar(50) default '' not null comment '送/受端'",
            "alter table entire_instances add last_maintain_send_or_receive varchar(50) default '' not null comment '上一次送/受端'",
            "alter table entire_instance_use_reports add maintain_section_name varchar(50) default '' not null comment '区间名称'",
            "alter table entire_instance_use_reports add maintain_send_or_receive varchar(50) default '' not null comment '送/受端'",
            "alter table entire_instances modify id int(11) unsigned auto_increment",
            "alter table entire_instance_locks modify id int(11) unsigned auto_increment",
            "alter table entire_instances add installed_at datetime null comment '上道时间' after last_installed_time",
            "alter table entire_instances modify last_installed_time int null comment '上次安装时间'",
            "alter table warehouse_reports add next_operation text null comment '下一步操作(JSON：[operation_type: redirect, url: /xxx/xxx, button_name: 故障修出所, button_class: btn btn-default btn-flat,])'",
            "INSERT INTO `rbac_menus` VALUES (148, '2022-05-17 18:35:23', '2022-05-17 18:35:23', NULL, '故障修入所统计', 77, 4, 'pie-chart', '/report/breakdown', NULL, 'web.report.breakdown')",
            "INSERT INTO `pivot_role_menus` VALUES (364, '2022-05-17 18:35:23', '2022-05-17 18:35:23', 1, 148)",
            "alter table entire_instances add maintain_signal_post_main_or_indicator_code char(6) default '' not null comment '信号机主体或表示器'",
            "alter table entire_instances add last_maintain_signal_post_main_or_indicator_code char(6) default '' not null comment '上一次信号机主体或表示器'",
            "alter table entire_instances add maintain_signal_post_main_light_position_code char(2) default '' not null comment '信号机主体灯位'",
            "alter table entire_instances add last_maintain_signal_post_main_light_position_code char(2) default '' not null comment '上一次信号机主体灯位'",
            "alter table entire_instances add maintain_signal_post_indicator_light_position_code char(2) default '' not null comment '表示器灯位'",
            "alter table entire_instances add last_maintain_signal_post_indicator_light_position_code char(2) default '' not null comment '上一次表示器灯位'",
            "alter table warehouse_report_entire_instances add maintain_section_name varchar(50) default '' not null comment '区间名称'",
            "alter table warehouse_report_entire_instances add maintain_send_or_receive varchar(50) default '' not null comment '送/受端'",
            "alter table warehouse_report_entire_instances add maintain_signal_post_main_or_indicator_code char(6) default '' not null comment '信号机主体或表示器代码'",
            "alter table warehouse_report_entire_instances add maintain_signal_post_main_light_position_code char(2) default '' not null comment '信号机主体灯位代码'",
            "alter table warehouse_report_entire_instances add maintain_signal_post_indicator_light_position_code char(2) default '' not null comment '表示器灯位代码'",
            "alter table entire_instance_use_reports add maintain_signal_post_main_or_indicator_code char(6) default '' not null comment '信号机主体或表示器代码'",
            "alter table entire_instance_use_reports add maintain_signal_post_main_light_position_code char(2) default '' not null comment '信号机主体灯位'",
            "alter table entire_instance_use_reports add maintain_signal_post_indicator_light_position_code char(2) default '' not null comment '表示器灯位'",
            "alter table storehouses add workshop_unique_code varchar(50) default '' not null comment '车间代码'",
            "alter table source_names add unique_code varchar(50) default '' not null comment '来源名称编号'",
            "alter table storehouses add paragraph_unique_code char(4) default '' not null comment '段代码'",
            "alter table storehouses add work_area_unique_code varchar(10) default '' not null comment '工区代码'",
            "alter table areas add paragraph_unique_code char(4) default '' not null comment '段代码'",
            "alter table platoons add paragraph_unique_code char(4) default '' not null comment '段代码'",
            "alter table shelves add paragraph_unique_code char(4) default '' not null comment '段代码'",
            "alter table tiers add paragraph_unique_code char(4) default '' not null comment '段代码'",
            "alter table positions add paragraph_unique_code char(4) default '' not null comment '段代码'",
            "create table detector_users
(
	id bigint unsigned auto_increment,
	created_at datetime not null,
	updated_at datetime not null,
	name varchar(100) default '' not null comment '检测台厂家名称',
	secret_key varchar(100) default '' not null,
	constraint detector_users_pk
		primary key (id)
)
comment '检测台厂家'",
            "create unique index detector_users_name_uindex on detector_users (name)",
            "create unique index detector_users_secret_key_uindex on detector_users (secret_key)",
            "alter table detector_users add access_key varchar(50) default '' not null",
            "update rbac_menus set deleted_at = now() where title='质量报告'",
        ],
        "2_6_10-B048" => [
            "update storehouses set workshop_unique_code = 'B048C23' where true",
        ],
        "2_6_10_B049" => [
            "update storehouses set workshop_unique_code = 'B049C16' where true",
        ],
        "2.6.10-B050" => [
            "update storehouses set workshop_unique_code = 'B050C18' where true",
        ],
        "2.6.10-B051" => [
            "update storehouses set workshop_unique_code = 'B051C18' where true",
        ],
        "2.6.10-B052" => [
            "update storehouses set workshop_unique_code = 'B052C11' where true",
        ],
        "2.6.10-B053" => [
            "update storehouses set workshop_unique_code = 'B053C12' where true",
        ],
        "2.6.10-B074" => [
            "update storehouses set workshop_unique_code = 'B074C01' where true",
        ],
        "2.6.10-2" => [
            "create table tmp_entire_instance_collections
(
    id                            int auto_increment
        primary key,
    created_at                    datetime                  null,
    updated_at                    datetime                  null,
    station_install_user_id       int          default 0    not null comment '第三方用户id',
    category_name                 varchar(50)               not null comment '种类名称',
    entire_model_name             varchar(50)               not null comment '类型名称',
    sub_model_name                varchar(50)               not null comment '型号名称',
    ex_factory_at                 datetime                  null comment '出厂日期',
    factory_number                varchar(100) default ''   null comment '厂编号',
    service_life                  double(5, 2) default 0.00 not null comment '使用年限，0.0没有年限',
    cycle_fix_at                  datetime                  null comment '周期修时间',
    cycle_fix_year                double(5, 2) default 0.00 not null comment '周期修年限',
    last_installed_at             datetime                  null comment '最后上道时间',
    factory_name                  varchar(100) default ''   null comment '供应商名称',
    workshop_unique_code          varchar(7)   default ''   not null comment '车间编码',
    station_unique_code           varchar(6)   default ''   not null comment '车站编码',
    version_number                varchar(100)              null comment '版本号',
    state_unique_code             varchar(50)  default ''   not null,
    equipment_category_name       varchar(50)  default ''   not null comment '设备种类名称',
    equipment_entire_model_name   varchar(50)  default ''   not null comment '设备类型名称',
    equipment_sub_model_name      varchar(50)  default ''   not null comment '设备型号名称',
    entire_instance_identity_code varchar(50)  default ''   not null comment '设备编码',
    install_location_unique_code  varchar(50)  default ''   not null comment '上道位置编码'
)
    comment '临时设备采集表'",
            "alter table maintains modify type enum('WORKSHOP', 'SCENE_WORKSHOP', 'STATION', 'ELECTRON', 'VEHICLE', 'HUMP') default 'WORKSHOP' not null comment '类型：WORKSHOP检修车间、SCENE_WORKSHOP现场车间、STATION车站、ELECTRON电子车间、VEHICLE车在车间、HUMP驼峰车间'",
            "alter table entire_models add custom_fix_cycle bool default false null comment '是否可自定义该型号下器材周期修年限'",
            "update entire_models set custom_fix_cycle = true where unique_code in ('Q010201','Q010801','Q010802','Q010506')",
            "drop index maintains_name_uindex on maintains",
            "drop index maintains_unique_code_uindex on maintains",
            "alter table distance drop column from_maintain_station_unique_code",
            "alter table distance drop column to_maintain_station_unique_code",
            "create table equipment_cabinets
(
    id                            int auto_increment
        primary key,
    created_at                    datetime                                                 not null,
    updated_at                    datetime                                                 not null,
    name                          varchar(50)                         default ''           not null,
    unique_code                   varchar(50)                         default ''           not null,
    entire_instance_identity_code varchar(50)                         default ''           not null,
    room_type                     enum ('MECHANICAL', 'POWER_SUPPLY') default 'MECHANICAL' not null comment '室类型
mechanical：机械室
power_supply：电源室',
    maintain_station_unique_code  varchar(50)                         default ''           not null comment '车站代码'
)
    comment '机柜'",
            "alter table repair_base_breakdown_order_entire_instances add last_installed_at datetime null comment '上道日期'",
            "alter table repair_base_breakdown_order_temp_entire_instances add last_installed_at datetime null comment '上道日期'",
            "alter table entire_instances add in_at datetime null comment '入所时间'",
            "alter table repair_base_breakdown_order_temp_entire_instances change last_fixed_at fixed_at datetime null comment '上一次检修时间'",
            "alter table repair_base_breakdown_order_temp_entire_instances change last_fixer_name fixer_name varchar(50) default '' not null comment '上一次检修人'",
            "alter table repair_base_breakdown_order_temp_entire_instances change last_checked_at checked_at datetime null comment '上一次验收时间'",
            "alter table repair_base_breakdown_order_temp_entire_instances change last_checker_name checker_name varchar(50) default '' not null comment '上一次验收人'",
            "alter table repair_base_breakdown_order_temp_entire_instances change last_installed_at installed_at datetime null comment '上道日期'",
            "alter table repair_base_breakdown_order_entire_instances change last_fixed_at fixed_at datetime null comment '上一次检修时间'",
            "alter table repair_base_breakdown_order_entire_instances change last_fixer_name fixer_name varchar(50) default '' not null comment '上一次检修人'",
            "alter table repair_base_breakdown_order_entire_instances change last_checked_at checked_at datetime null comment '上一次验收时间'",
            "alter table repair_base_breakdown_order_entire_instances change last_checker_name checker_name varchar(50) default '' not null comment '上一次验收人'",
            "alter table repair_base_breakdown_order_entire_instances change last_installed_at installed_at datetime null comment '上道日期'",
            "alter table entire_instances add last_fixer_name varchar(50) default '' not null comment '上一次检修人'",
            "alter table entire_instances add last_fixed_at datetime null comment '上一次检修时间'",
            "alter table entire_instances add last_checker_name varchar(50) default '' not null comment '上一次验收人'",
            "alter table entire_instances add last_checked_at datetime null comment '上一次验收时间'",
            "alter table lines_maintains add station_name varchar(50) default '' not null comment '车站名称'",
            "alter table lines_maintains add station_unique_code varchar(50) default '' not null comment '车站代码'",
            "alter table lines_maintains add line_name varchar(50) default '' not null comment '线别名称'",
            "alter table lines_maintains add line_unique_code varchar(50) default '' not null comment '线别代码'",
            "create index maintains_name_index on maintains (name)",
            "create index maintains_unique_code_index on maintains (unique_code)",
        ],
        "2.6.10-3" => [
            "create index print_new_location_and_old_entire_instances_station_name_index on print_new_location_and_old_entire_instances (old_maintain_station_name)",
            "create index print_new_location_and_old_entire_instances_workshop_name_index on print_new_location_and_old_entire_instances (old_maintain_workshop_name)",
            "create index temp_station_eis_maintain_station_name_index on temp_station_eis (maintain_station_name)",
            "create index repair_base_plan_out_cycle_fix_entire_instances_sn_index on repair_base_plan_out_cycle_fix_entire_instances (station_name)",
            "create index repair_base_plan_out_cycle_fix_entire_instances_suc_index on repair_base_plan_out_cycle_fix_entire_instances (station_unique_code)",
            "create index breakdown_logs_maintain_station_name_index on breakdown_logs (maintain_station_name)",
            "create index station_install_location_records_station_name_index on station_install_location_records (maintain_station_name)",
            "create index station_install_location_records_station_unique_code_index on station_install_location_records (maintain_station_unique_code)",
            "create index warehouse_report_entire_instances_line_unique_code_index on warehouse_report_entire_instances (line_unique_code)",
            "create index warehouse_report_entire_instances_maintain_station_name_index on warehouse_report_entire_instances (maintain_station_name)",
            "create index warehouse_report_entire_instances_maintain_workshop_name_index on warehouse_report_entire_instances (maintain_workshop_name)",
            "create index warehouse_in_batch_reports_maintain_station_name_index on warehouse_in_batch_reports (maintain_station_name)",
            "create index v250_workshop_in_entire_instances_maintain_station_name_index on v250_workshop_in_entire_instances (maintain_station_name)",
            "create index v250_workshop_out_entire_instances_maintain_station_name_index on v250_workshop_out_entire_instances (maintain_station_name)",
            "create index repair_base_plan_out_cycle_fix_bills_station_name_index on repair_base_plan_out_cycle_fix_bills (station_name)",
            "create index repair_base_plan_out_cycle_fix_bills_station_unique_code_index on repair_base_plan_out_cycle_fix_bills (station_unique_code)",
            "create index warehouse_storage_batch_reports_maintain_station_name_index on warehouse_storage_batch_reports (maintain_station_name)",
            "create index warehouse_reports_maintain_station_unique_code_index on warehouse_reports (maintain_station_unique_code)",
            "create index warehouse_reports_station_name_index on warehouse_reports (station_name)",
            "create index station_locations_maintain_station_name_index on station_locations (maintain_station_name)",
            "create index station_locations_maintain_station_unique_code_index on station_locations (maintain_station_unique_code)",
            "create index temp_station_position_maintain_station_name_index on temp_station_position (maintain_station_name)",
            "create index temp_station_position_maintain_station_unique_code_index on temp_station_position (maintain_station_unique_code)",
            "create index temp_station_position_scene_workshop_name_index on temp_station_position (scene_workshop_name)",
            "create index temp_station_position_scene_workshop_unique_code_index on temp_station_position (scene_workshop_unique_code)",
            "create index fix_workflows_maintain_station_name_index on fix_workflows (maintain_station_name)",
            "create index entire_instances_last_line_unique_code_index on entire_instances (last_line_unique_code)",
            "create index entire_instances_last_maintain_station_name_index on entire_instances (last_maintain_station_name)",
            "create index entire_instances_last_maintain_workshop_name_index on entire_instances (last_maintain_workshop_name)",
            "create index entire_instances_line_unique_code_index on entire_instances (line_unique_code)",
            "create index entire_instances_maintain_workshop_name_index on entire_instances (maintain_workshop_name)",
            "create index collect_device_order_entire_instances_station_name_index on collect_device_order_entire_instances (maintain_station_name)",
            "create index collect_device_order_entire_instances_station_unique_code_index on collect_device_order_entire_instances (maintain_station_unique_code)",
            "create index collect_device_order_entire_instances_workshop_name_index on collect_device_order_entire_instances (maintain_workshop_name)",
            "create index collect_device_order_entire_instances_workshop_unique_code_index on collect_device_order_entire_instances (maintain_workshop_unique_code)",
            "create index repair_base_breakdown_order_entire_instances_luc_index on repair_base_breakdown_order_entire_instances (line_unique_code)",
            "create index repair_base_breakdown_order_entire_instances_sn_index on repair_base_breakdown_order_entire_instances (maintain_station_name)",
            "create index repair_base_breakdown_order_entire_instances_scn_index on repair_base_breakdown_order_entire_instances (scene_workshop_name)",
            "create index entire_instances_bind_station_name_index on entire_instances (bind_station_name)",
        ],
        "2.6.10-4" => [
            "alter table install_rooms add name varchar(50) default '' not null comment '机房名称'",
            "alter table install_shelves change var_lon vr_lon varchar(100) default '' not null comment 'vr精度'",
            "alter table install_tiers add sort int default 0 not null comment '排序'",
            "alter table install_positions add sort int default 0 not null comment '排序'",
            "alter table `lines` add is_show bool default 1 not null comment '是否显示'",
        ],
        "2.6.11" => [
            "create table app_upgrades
(
    id              bigint unsigned auto_increment
        primary key,
    created_at      datetime               not null,
    updated_at      datetime               null,
    unique_code     varchar(64) default '' not null comment '代码',
    version         varchar(64) default '' not null comment '版本号',
    target          varchar(64) default '' not null comment '更新目标站段',
    description     longtext               null comment '更新说明',
    operating_steps longtext               null comment '更新步骤说明',
    upgrade_reports longtext               null comment '更新记录'
)
    comment '程序更新部署记录'",
            "create table pivot_app_upgrade_and_accessories
(
    id             bigint unsigned auto_increment
        primary key,
    created_at     datetime        null,
    updated_at     datetime        null,
    app_upgrade_id bigint unsigned null,
    file_id        bigint unsigned null
)
    comment '文件更新计划与附件表'",
        ],
        "2.6.11-1" => [
            "DROP TABLE IF EXISTS files",
            "create table files
(
    id                     bigint unsigned auto_increment
        primary key,
    created_at             datetime                   null,
    updated_at             datetime                   null,
    identity_code          varchar(64)     default '' not null comment '文件编号',
    filename               varchar(64)     default '' not null comment '文件存储名',
    original_filename      varchar(64)     default '' not null comment '原始文件名',
    original_extension     varchar(64)     default '' not null comment '原始文件扩展名',
    filesystem_config_name varchar(64)     default '' not null comment '文件配置名',
    type                   varchar(64)     default '' not null comment '文件类型',
    size                   bigint unsigned default 0  not null comment '文件尺寸'
)",
        ],
        "2.6.11-2" => [
            "alter table areas modify type enum('FIXED', 'FIXING', 'SCRAP', 'EMERGENCY') default 'FIXED' not null comment '仓库类型：成品FIXED，待修品FIXING，报废 SCRAP'",
        ],
        "combo-kinds" => [
            "alter table entire_instances add new_category_unique_code varchar(50) default '' not null",
            "alter table entire_instances add new_entire_model_unique_code varchar(50) default '' not null",
            "alter table entire_instances add new_sub_model_unique_code varchar(50) default '' not null",
            "alter table entire_instances add old_category_unique_code varchar(50) default '' not null comment '旧种类代码'",
            "alter table entire_instances add old_entire_model_unique_code varchar(50) default '' not null comment '旧类型代码'",
            "alter table entire_instances add old_sub_model_unique_code varchar(50) default '' not null comment '旧型号代码'",
            "alter table entire_instances add old_category_name varchar(50) default '' not null comment '旧种类名称'",
            "alter table entire_instances add old_entire_model_name varchar(50) default '' not null comment '旧类型名称'",
            "alter table entire_instances add old_sub_model_name varchar(50) default '' not null comment '旧型号名称'",
            "update categories set name = '其他类' where unique_code = 'Q13'",
            "update entire_instances set category_name = '其他类' where category_unique_code = 'Q13'",
            "update categories set name = '转辙机（旧码）' where unique_code = 'S03'",
            "update entire_instances set category_name = '转辙机（旧码）' where category_unique_code = 'S03'",
            "update categories set name = '转换锁闭器（旧码）' where unique_code = 'S07'",
            "update entire_instances set category_name = '转换锁闭器（旧码）' where category_unique_code = 'S07'",
            "update entire_instances as ei set model_unique_code = '', model_name = (select name from entire_models as em where em.unique_code = ei.entire_model_unique_code limit 1) where ei.category_unique_code like 'S%'",
            "alter table entire_instances add delete_description varchar(256) default '' not null comment '删除原因'",
            "DROP TABLE IF EXISTS new_categories",
            "create table new_categories
(
    id          int auto_increment
        primary key,
    unique_code varchar(50) default '' not null,
    name        varchar(50) default '' not null,
    created_at  datetime               null,
    updated_at  datetime               null,
    deleted_at  datetime               null,
    nickname    varchar(50) default '' not null comment '别名',
    is_show     tinyint(1)  default 1  not null comment '是否显示',
    constraint new_categories_unique_code_uindex unique (unique_code)
)",
            "DROP TABLE IF EXISTS new_entire_models",
            "create table new_entire_models
(
    id                   int auto_increment
        primary key,
    unique_code          varchar(50)                                          not null,
    name                 varchar(50)                           default ''     not null,
    category_unique_code varchar(50)                           default ''     not null comment '所属种类',
    parent_unique_code   varchar(50)                           default ''     not null comment '父级代码',
    is_sub_model         tinyint(1)                            default 0      not null comment '是否是型号',
    life_year            int                                   default 15     not null comment '使用寿命',
    fix_cycle_unit       enum ('YEAR', 'MONTH', 'WEEK', 'DAY') default 'YEAR' not null comment '周期修时长单位',
    fix_cycle_value      int                                   default 0      not null comment '周期修时长',
    created_at           datetime                                             null,
    updated_at           datetime                                             null,
    deleted_at           datetime                                             null,
    nickname             varchar(50)                           default ''     not null comment '别名',
    custom_fix_cycle     tinyint(1)                            default 0      null comment '是否可自定义该型号下器材周期修年限',
    constraint new_entire_models_unique_code_uindex unique (unique_code)
)",
            "create index new_entire_models_category_unique_code_index on new_entire_models (category_unique_code)",
            "create index new_entire_models_parent_unique_code_index on new_entire_models (parent_unique_code)",
        ],
        "upgrade-platoon"=>[
            "alter table platoons modify type enum('', 'FIXED', 'FIXING', 'MATERIAL', 'EMERGENCY', 'SCRAP') default '' not null comment '排类型：FIXED：成品；FIXING：待修；MATERIAL：材料；EMERGENCY：应急备品；SCRAP：废品'",
        ],
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    final public function __construct()
    {
        $this->__currentWorkshopUniqueCode = env("CURRENT_WORKSHOP_UNIQUE_CODE");
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    final public function handle(): void
    {
        $version = $this->argument("version");

        if (@$this->__sql[$version]) {
            collect($this->__sql[$version])->each(function ($v, $k) {
                if ($v) $this->__statement($k, $v);
            });
            $this->info("数据库升级{$version}：执行完毕");
        }
    }

    private function __statement(string $comment, string $sql)
    {
        try {
            $conn_code = $this->argument("conn_code");
            DB::connection($conn_code)->statement($sql);
            $this->info("成功执行：$comment");
        } catch (Exception $e) {
            $this->comment($e->getMessage());
        }
    }
}
