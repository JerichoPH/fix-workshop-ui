@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            路局管理
            <small>编辑</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.OrganizationRailway:index') }}"><i class="fa fa-users">&nbsp;</i>路局-列表</a></li>
            <li class="active">路局-编辑</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <form class="form-horizontal" id="frmUpdate">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">编辑路局</h3>
                            <!--右侧最小化按钮-->
                            <div class="box-tools pull-right"></div>
                            <hr>
                        </div>

                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">代码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="unique_code" id="txtUniqueCode" type="text" class="form-control" placeholder="唯一、必填" required value="" disabled autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">名称*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="name" id="txtName" type="text" class="form-control" placeholder="唯一、必填" required value="" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger text-danger">别名*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="short_name" id="txtShortName" type="text" class="form-control" placeholder="唯一、必填" required value="" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger text-danger">是否启用*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input type="radio" name="be_enable" id="rdoBeEnableYes" value="1" checked>
                                    <label for="rdoBeEnableYes">是</label>
                                    &emsp;
                                    <input type="radio" name="be_enable" id="rdoBeEnableNo" value="0">
                                    <label for="rdoBeEnableYes">否</label>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ route('web.OrganizationRailway:index') }}" class="btn btn-default pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a onclick="fnUpdate()" class="btn btn-warning pull-right btn-sm"><i class="fa fa-check">&nbsp;</i>保存</a>
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
        let $frmBindLocationLines = $("#frmBindLocationLines");
        let $txtUniqueCode = $("#txtUniqueCode");
        let $txtName = $("#txtName");
        let $txtShortName = $("#txtShortName");
        let $rdoBeEnableYes = $("#rdoBeEnableYes");
        let $rdoBeEnableNo = $("#rdoBeEnableNo");
        let organizationRailway = null;
        let boundLocationLineUuids = [];

        /**
         * 选择是否可用状态
         */
        function fnRdoBeEnable(checked = null) {
            if (checked) {
                if (checked) {
                    $rdoBeEnableYes.attr('checked', checked);
                    $rdoBeEnableYes.attr('checked', !checked);
                } else {
                    $rdoBeEnableYes.attr('checked', !checked);
                    $rdoBeEnableYes.attr('checked', checked);
                }
            }
        }

        /**
         * 初始化数据
         */
        function fnInit() {
            $.ajax({
                url: `{{ route("web.OrganizationRailway:show", ["uuid" => $uuid, ]) }}`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ route("web.OrganizationRailway:show", ["uuid" => $uuid, ]) }} success:`, res);
                    organizationRailway = res["content"]["organization_railway"];

                    let {unique_code: uniqueCode, name, short_name: shortName, be_enable: beEnable, location_lines: locationLines,} = organizationRailway;

                    $txtUniqueCode.val(uniqueCode);
                    $txtName.val(name);
                    $txtShortName.val(shortName);
                    fnRdoBeEnable(beEnable);
                    // 已经绑定的线别
                    if (locationLines.length > 0) {
                        locationLines.map(function (locationLine) {
                            boundLocationLineUuids.push(locationLine["uuid"]);
                        });
                    }

                    $txtName.focus();
                },
                error: function (err) {
                    console.log(`{{ route("web.OrganizationRailway:show", ["uuid" => $uuid, ]) }} fail:`, err);
                    layer.msg(err["responseJSON"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = `{{ route("web.Authorization:getLogin") }}`;
                    });
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
                                    let divBtnGroup = '';
                                    divBtnGroup += `<td class="">`;
                                    divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-warning" onclick="('${uuid}')"><i class="fa fa-edit"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDelete('${uuid}')"><i class="fa fa-trash"></i></a>`;
                                    divBtnGroup += `</div>`;
                                    divBtnGroup += `</td>`;

                                    render.push([
                                        `<input type="checkbox" class="location-line-uuid" name="location_line_uuids[]" value="${uuid}" ${boundLocationLineUUIDs.indexOf(uuid) > -1 ? "checked" : ""} onchange="$('#chkAllLocationLine').prop('checked', $('.location-line-uuid').length === $('.location-line-uuid:checked').length)">`,
                                        createdAt,
                                        uniqueCode,
                                        name,
                                        divBtnGroup,
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
                        targets: 0,  // 清除第一列排序
                    }],
                    paging: true,  // 分页器
                    lengthChange: true,
                    searching: false,  // 搜索框
                    ordering: true,  // 列排序
                    info: true,
                    autoWidth: false,  // 自动宽度
                    order: [[1, 'desc']],  // 排序依据
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
            }
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            fnInit();  // 初始化数据
        });

        /**
         * 保存
         */
        function fnUpdate() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmUpdate.serializeArray();
            data.push({name: "unique_code", value: $txtUniqueCode.val()});

            $.ajax({
                url: `{{ route('web.OrganizationRailway:Update', ["uuid" => $uuid , ]) }}`,
                type: 'put',
                data,
                success: function (res) {
                    console.log(`{{ route('web.OrganizationRailway:Update', ["uuid" => $uuid, ]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,}, function () {
                        $txtName.focus();
                    });
                },
                error: function (err) {
                    console.log(`{{ route('web.OrganizationRailway:Update', ["uuid" => $uuid, ]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                }
            });
        }
    </script>
@endsection