@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            道口管理
            <small>新建</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.LocationRailroadGradeCross:Index') }}"><i class="fa fa-users">&nbsp;</i>道口-列表</a></li>
            <li class="active">道口-新建</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <form class="form-horizontal" id="frmStore">
            <div class="row">
                <div class="col-md-5">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">新建道口</h3>
                            <!--右侧最小化按钮-->
                            <div class="pull-right btn-group btn-group-sm"></div>
                            <hr>
                        </div>
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">代码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="unique_code" id="txtUniqueCode" type="text" class="form-control" placeholder="唯一，必填 5位：I0001" required value="" autocomplete="off">
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
                                    <input type="radio" name="be_enable" id="rdoBeEnableYes" value="1" checked>
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
                                            onchange="fnFillSelWorkArea(this.value)"
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
                            <a href="{{ route('web.LocationRailroadGradeCross:Index') }}" class="btn btn-default btn-sm pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a onclick="fnStore()" class="btn btn-success btn-sm pull-right"><i class="fa fa-check">&nbsp;</i>新建</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">绑定线别</h3>
                            <!--右侧最小化按钮-->
                            <div class="btn-group btn-group-sm pull-right"></div>
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
                </div>
            </div>
        </form>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $frmStore = $('#frmStore');
        let $selOrganizationWorkshop = $("#selOrganizationWorkshop");
        let $selOrganizationWorkArea = $("#selOrganizationWorkArea");
        let tblLocationLine = null;
        let boundLocationLineUuids = [];

        /**
         * 加载线别表格
         */
        function fnFillTblLocationLine() {
            if (document.getElementById('tblLocationLine')) {
                tblLocationLine = $('#tblLocationLine').DataTable({
                    ajax: {
                        url: `{{ route("web.LocationLine:Index") }}?{!! http_build_query(request()->all()) !!}`,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.LocationLine:Index") }}?{!! http_build_query(request()->all()) !!} success:`, res);
                            let {location_lines: locationLines,} = res["content"];
                            let render = [];
                            if (locationLines.length > 0) {
                                $.each(locationLines, (_, locationLine) => {
                                    let uuid = locationLine["uuid"];
                                    let createdAt = locationLine["created_at"] ? moment(locationLine["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uniqueCode = locationLine["unique_code"] ? locationLine["unique_code"] : "";
                                    let name = locationLine["name"] ? locationLine["name"] : "";
                                    let shortName = locationLine["short_name"] ? locationLine["short_name"] : "";
                                    let divBtnGroup = '';
                                    divBtnGroup += `<td class="">`;
                                    divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-warning" onclick="('${uuid}')"><i class="fa fa-edit"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDelete('${uuid}')"><i class="fa fa-trash"></i></a>`;
                                    divBtnGroup += `</div>`;
                                    divBtnGroup += `</td>`;

                                    render.push([
                                        null,
                                        `<input type="checkbox" class="location-line-uuid" name="location_line_uuids[]" value="${uuid}" ${boundLocationLineUuids.indexOf(uuid) > -1 ? "checked" : ""} onchange="$('#chkAllLocationLine').prop('checked', $('.location-line-uuid').length === $('.location-line-uuid:checked').length)">`,
                                        createdAt,
                                        uniqueCode,
                                        name,
                                        shortName,
                                        divBtnGroup,
                                    ]);
                                });
                            }
                            return render;
                        },
                        error: function (err) {
                            console.log(`{{ route("web.LocationLine:Index") }}?{!! http_build_query(request()->all()) !!} fail:`, err);
                            layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                                if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                            });
                        }
                    },
                    columnDefs: [{
                        orderable: false,
                        targets: [0, 1,],  // 清除第一列排序
                    }],
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
         * 填充车间下拉列表
         */
        function fnFillSelWorkshop() {
            $.ajax({
                url: `{{ route("web.OrganizationWorkshop:Index") }}`,
                type: 'get',
                data: {be_enable: 1,},
                async: true,
                beforeSend: function () {
                    $selOrganizationWorkshop.attr('disabled', 'disabled');
                    $selOrganizationWorkshop.empty();
                    $selOrganizationWorkshop.append(`<option value="" disabled selected>无</option>`);
                },
                success: function (res) {
                    console.log(`{{ route("web.OrganizationWorkshop:Index") }} success:`, res);

                    let {organization_workshops: organizationWorkshops,} = res["content"];

                    if (organizationWorkshops.length > 0) {
                        organizationWorkshops.map(function (organizationWorkshop) {
                            $selOrganizationWorkshop.append(`<option value="${organizationWorkshop["uuid"]}">${organizationWorkshop["name"]}</option>`);
                        });
                    }
                },
                error: function (err) {
                    console.log(`{{ route("web.OrganizationWorkshop:Index") }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                },
                complete: function () {
                    $selOrganizationWorkshop.removeAttr('disabled');
                },
            });
        }

        /**
         * 填充工区下拉列表
         * @param {string} organizationWorkshopUuid
         */
        function fnFillSelWorkArea(organizationWorkshopUuid = "") {
            let data = {be_enable: 1,};
            if (organizationWorkshopUuid) data["organization_workshop_uuid"] = organizationWorkshopUuid;

            $.ajax({
                url: `{{ route("web.OrganizationWorkArea:Index") }}`,
                type: 'get',
                data,
                async: true,
                beforeSend: function () {
                    $selOrganizationWorkArea.attr("disabled", "disabled");
                    $selOrganizationWorkArea.empty();
                    $selOrganizationWorkArea.append(`<option value="">无</option>`);
                },
                success: function (res) {
                    console.log(`{{ route("web.OrganizationWorkArea:Index") }} success:`, res);

                    let {organization_work_areas: organizationWorkAreas,} = res["content"];

                    if (organizationWorkAreas.length > 0) {
                        organizationWorkAreas.map(function (organizationWorkArea) {
                            $selOrganizationWorkArea.append(`<option value="${organizationWorkArea["uuid"]}">${organizationWorkArea["name"]}</option>`);
                        });
                    }
                },
                error: function (err) {
                    console.log(`{{ route("web.OrganizationWorkArea:Index") }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                },
                complete: function () {
                    $selOrganizationWorkArea.removeAttr("disabled");
                },
            });
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            fnFillTblLocationLine(); // 加载线别下拉列表
            fnFillSelWorkshop();  // 加载车间下拉列表
            fnFillSelWorkArea();  // 加载工区下拉列表

            fnCheckAll("chkAllLocationLine", "location-line-uuid");  // 全选线别
        });

        /**
         * 新建
         */
        function fnStore() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmStore.serializeArray();
            console.log(data);

            $.ajax({
                url: '{{ route('web.LocationRailroadGradeCross:Store') }}',
                type: 'post',
                data,
                success: function (res) {
                    console.log(`{{ route('web.LocationRailroadGradeCross:Store') }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,}, function () {
                        location.reload();
                    });
                },
                error: function (err) {
                    console.log(`{{ route('web.LocationRailroadGradeCross:Store') }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                }
            });
        }
    </script>
@endsection