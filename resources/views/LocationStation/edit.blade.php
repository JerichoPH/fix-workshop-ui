@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            站场管理
            <small>编辑</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.LocationStation:index') }}"><i class="fa fa-users">&nbsp;</i>站场-列表</a></li>
            <li class="active">站场-编辑</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-5">
                <form class="form-horizontal" id="frmUpdate">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">编辑站场</h3>
                            <!--右侧最小化按钮-->
                            <div class="pull-right btn-group btn-group-sm"></div>
                            <hr>
                        </div>
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">代码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="unique_code" id="txtUniqueCode" type="text" class="form-control" placeholder="唯一，必填" required value="" autocomplete="off" disabled>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">名称*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="name" id="txtName" type="text" class="form-control" placeholder="唯一，必填" required value="" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">是否启用*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input type="radio" name="be_enable" id="rdoBeEnableYes" value="1">
                                    <label for="rdoBeEnableYes">是</label>
                                    &emsp;
                                    <input type="radio" name="be_enable" id="rdoBeEnableNo" value="0">
                                    <label for="rdoBeEnableNo">否</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">所属车间*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select
                                            name="organization_workshop_uuid"
                                            id="selOrganizationWorkshop"
                                            class="form-control select2"
                                            style="width: 100%;"
                                            onchange="fnFillSelOrganizationWorkArea(this.value)"
                                    >
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">所属工区：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select
                                            name="organization_work_area_uuid"
                                            id="selOrganizationWorkArea"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ route('web.LocationStation:index') }}" class="btn btn-default pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a onclick="fnUpdate()" class="btn btn-warning pull-right btn-sm"><i class="fa fa-check">&nbsp;</i>保存</a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-7">
                <form id="frmBindLocationLines">
                    <div class="box box-solid">
                        <div class="box-header">
                            <div class="row">
                                <div class="col-md-4">
                                    <h3 class="box-title">绑定线别</h3>
                                </div>
                                <div class="col-md-8">
                                    <div class="btn-group btn-group-sm pull-right">
                                        <a href="javascript:" class="btn btn-primary" onclick="fnBindLocationLines()"><i class="fa fa-link">&nbsp;</i>绑定线别</a>
                                    </div>
                                </div>
                            </div>
                            <hr>
                        </div>
                        <div class="box-body">
                            <table class="table table-hover table-condensed" id="tblLocationLine">
                                <thead>
                                <tr>
                                    <th>行号</th>
                                    <th><input type="checkbox" id="chkAllLocationLine"></th>
                                    <th>新建时间</th>
                                    <th>代码</th>
                                    <th>名称</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $frmUpdate = $('#frmUpdate');
        let $txtUniqueCode = $("#txtUniqueCode");
        let $txtName = $("#txtName");
        let $rdoBeEnableYes = $("#rdoBeEnableYes");
        let $rdoBeEnableNo = $("#rdoBeEnableNo");
        let $selOrganizationWorkshop = $("#selOrganizationWorkshop");
        let $selOrganizationWorkArea = $("#selOrganizationWorkArea");
        let locationStation = null;
        let tblLocationLine = null;
        let boundLocationLineUUIDs = [];
        let $frmBindLocationLines = $("#frmBindLocationLines");

        /**
         * 加载初始化
         */
        function fnInit() {
            $.ajax({
                url: `{{ route("web.LocationStation:Show", ["uuid" => $uuid,]) }}`,
                type: 'get',
                data: {},
                async: true,
                beforeSend: function () {
                },
                success: function (res) {
                    console.log(`{{ route("web.LocationStation:Show", ["uuid" => $uuid,]) }} success:`, res);

                    locationStation = res["content"]["location_station"];

                    let {unique_code: uniqueCode, name, be_enable: beEnable, location_lines: locationLines,} = locationStation;

                    $txtUniqueCode.val(uniqueCode);
                    $txtName.val(name);
                    if (beEnable) {
                        $rdoBeEnableYes.attr("checked", "checked");
                    } else {
                        $rdoBeEnableNo.attr("checked", "checked");
                    }
                    fnFillSelOrganizationWorkshop(locationStation["organization_workshop"]["uuid"]);
                    fnFillSelOrganizationWorkArea(locationStation["organization_workshop"]["uuid"], locationStation["organization_work_area"]["uuid"]);
                    // 已经绑定的线别
                    if (locationLines.length > 0) {
                        locationLines.map(function (locationLine) {
                            boundLocationLineUUIDs.push(locationLine["uuid"]);
                        });
                    }
                },
                error: function (err) {
                    console.log(`{{ route("web.LocationStation:Show", ["uuid" => $uuid,]) }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                },
                complete: function () {
                },
            });
        }

        /**
         * 加载线别表格
         */
        function fnFillTblLocationLine() {
            if (document.getElementById('tblLocationLine')) {
                tblLocationLine = $('#tblLocationLine').DataTable({
                    ajax: {
                        url: `{{ route("web.LocationLine:index") }}?{!! http_build_query(request()->all()) !!}`,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.LocationLine:index") }}?{!! http_build_query(request()->all()) !!} success:`, res);
                            let {location_lines: locationLines,} = res["content"];
                            let render = [];
                            if (locationLines.length > 0) {
                                $.each(locationLines, (_, locationLine) => {
                                    let uuid = locationLine["uuid"];
                                    let createdAt = locationLine["created_at"] ? moment(locationLine["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uniqueCode = locationLine["unique_code"] ? locationLine["unique_code"] : "";
                                    let name = locationLine["name"] ? locationLine["name"] : "";
                                    let shortName = locationLine["short_name"] ? locationLine["short_name"] : "";

                                    render.push([
                                        null,
                                        `<input type="checkbox" class="location-line-uuid" name="location_line_uuids[]" value="${uuid}" ${boundLocationLineUUIDs.indexOf(uuid) > -1 ? "checked" : ""} onchange="$('#chkAllLocationLine').prop('checked', $('.location-line-uuid').length === $('.location-line-uuid:checked').length)">`,
                                        createdAt,
                                        uniqueCode,
                                        name,
                                        shortName,
                                    ]);
                                });
                            }
                            return render;
                        },
                        error: function (err) {
                            console.log(`{{ route("web.LocationLine:index") }}?{!! http_build_query(request()->all()) !!} fail:`, err);
                            layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                                if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                            });
                        }
                    },
                    columnDefs: [{
                        orderable: false,
                        targets: [0, 1,],  // 清除第一列排序
                    }],
                    processing: true,
                    paging: true,  // 分页器
                    lengthChange: true,
                    searching: false,  // 搜索框
                    ordering: true,  // 列排序
                    info: true,
                    autoWidth: false,  // 自动宽度
                    order: [[2, 'desc']],  // 排序依据
                    iDisplayLength: 50,  // 默认分页数
                    aLengthMenu: [50, 100, 200],  // 分页下拉框选项
                    language: {
                        sInfoFiltered: "从_MAX_中过滤",
                        sProcessing: "数据加载中...",
                        info: "第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                        sLengthMenu: "每页显示_MENU_条记录",
                        zeroRecords: "没有符合条件的记录",
                        infoEmpty: " ",
                        emptyTable: "没有符合条件的记录",
                        search: "筛选：",
                        paginate: {sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"}
                    }
                });

                tblLocationLine.on('draw.dt order.dt search.dt', function () {
                    tblLocationLine.column(0, {search: 'applied', order: 'applied'}).nodes().each(function (cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }).draw();
            }
        }

        /**
         * 加载车间下拉列表
         * @param {string} organizationWorkshopUuid
         */
        function fnFillSelOrganizationWorkshop(organizationWorkshopUuid = "") {
            $.ajax({
                url: `{{ route("web.OrganizationWorkshop:index") }}`,
                type: 'get',
                data: {be_enable: 1, organization_workshop_type_unique_code: "SCENE-WORKSHOP",},
                async: true,
                beforeSend: function () {
                    $selOrganizationWorkshop.attr('disabled', 'disabled');
                },
                success: function (res) {
                    console.log(`{{ route("web.OrganizationWorkshop:index") }} success:`, res);

                    let {organization_workshops: organizationWorkshops,} = res["content"];

                    $selOrganizationWorkshop.empty();
                    $selOrganizationWorkshop.append(`<option value="" disabled selected>未选择</option>`);

                    if (organizationWorkshops.length > 0) {
                        organizationWorkshops.map(function (organizationWorkshop) {
                            $selOrganizationWorkshop.append(`<option value="${organizationWorkshop["uuid"]}" ${organizationWorkshopUuid === organizationWorkshop["uuid"] ? "selected" : ""}>${organizationWorkshop["name"]}</option>`);
                        });
                    }
                },
                error: function (err) {
                    console.log(`{{ route("web.OrganizationWorkshop:index") }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                },
                complete: function () {
                    $selOrganizationWorkshop.removeAttr('disabled');
                },
            });
        }

        /**
         * 加载工区下拉列表
         * @param {string} organizationWorkshopUuid
         * @param {string} organizationWorkAreaUuid
         */
        function fnFillSelOrganizationWorkArea(organizationWorkshopUuid = "", organizationWorkAreaUuid = "") {
            let data = {be_enable: 1, organization_work_area_type_unique_code: "SCENE-WORK-AREA",};
            if (organizationWorkshopUuid) data["organization_workshop_uuid"] = organizationWorkshopUuid;

            $.ajax({
                url: `{{ route("web.OrganizationWorkArea:index") }}`,
                type: 'get',
                data,
                async: true,
                success: res => {
                    console.log(`{{ route("web.OrganizationWorkArea:index") }} success:`, res);

                    let {organization_work_areas: organizationWorkAreas,} = res["content"];

                    $selOrganizationWorkArea.empty();
                    $selOrganizationWorkArea.append(`<option value="">未选择</option>`);

                    if (organizationWorkAreas.length > 0) {
                        organizationWorkAreas.map(function (organizationWorkArea) {
                            $selOrganizationWorkArea.append(`<option value="${organizationWorkArea["uuid"]}" ${organizationWorkAreaUuid === organizationWorkArea["uuid"] ? "selected" : ""}>${organizationWorkArea["name"]}</option>`);
                        });
                    }
                },
                error: err => {
                    console.log(`{{ route("web.OrganizationWorkArea:index") }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                },
            });
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            fnInit();  // 加载初始化
            fnFillTblLocationLine();  // 加载线别表格

            fnCheckAll("chkAllLocationLine", "location-line-uuid");  // 全选线别
        });

        /**
         * 保存
         */
        function fnUpdate() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmUpdate.serializeArray();
            data.push({name: "unique_code", value: $txtUniqueCode.val()});

            $.ajax({
                url: `{{ route('web.LocationStation:Update', ["uuid" => $uuid , ]) }}`,
                type: 'put',
                data,
                success: function (res) {
                    console.log(`{{ route('web.LocationStation:Update', ["uuid" => $uuid, ]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,});
                },
                error: function (err) {
                    console.log(`{{ route('web.LocationStation:Update', ["uuid" => $uuid, ]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                }
            });
        }

        /**
         * 站场绑定线别
         */
        function fnBindLocationLines() {
            let loading = layer.msg('处理中……', {time: 0,});
            let data = $frmBindLocationLines.serializeArray();

            $.ajax({
                url: `{{ route("web.LocationStation:putBindLocationLines", ["uuid" => $uuid,]) }}`,
                type: 'put',
                data,
                async: true,
                success: res => {
                    console.log(`{{ route("web.LocationStation:putBindLocationLines", ["uuid" => $uuid,]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res['msg'], {time: 1000,}, function () {

                    });
                },
                error: err => {
                    console.log(`{{ route("web.LocationStation:putBindLocationLines", ["uuid" => $uuid,]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                },
            });
        }
    </script>
@endsection