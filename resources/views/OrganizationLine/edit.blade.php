@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            线别管理
            <small>编辑</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.OrganizationLine:Index') }}"><i class="fa fa-users">&nbsp;</i>线别-列表</a></li>
            <li class="active">线别-编辑</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <form class="form-horizontal" id="frmUpdate">
                <div class="col-md-6">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">编辑线别</h3>
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
                                <label class="col-sm-2 control-label text-danger">是否启用*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input type="radio" name="be_enable" id="rdoBeEnableYes" value="1">
                                    <label for="rdoBeEnableYes">是</label>
                                    &emsp;
                                    <input type="radio" name="be_enable" id="rdoBeEnableNo" value="0">
                                    <label for="rdoBeEnableNo">否</label>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ route('web.OrganizationLine:Index') }}" class="btn btn-default pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a onclick="fnUpdate()" class="btn btn-warning pull-right btn-sm"><i class="fa fa-check">&nbsp;</i>保存</a>
                        </div>
                    </div>
                </div>
            </form>
            <form id="frmBindOrganizationRailway">
                <div class="col-md-6">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">绑定路局</h3>
                            <!--右侧最小化按钮-->
                            <div class="box-tools pull-right">
                                <a href="javascript:" class="btn btn-primary" onclick="fnBindOrganizationRailway()"><i class="fa fa-link">&nbsp;</i>绑定路局</a>
                            </div>
                            <hr>
                        </div>
                        <div class="box-body">
                            <table class="table table-hover table-condensed" id="tblOrganizationRailway">
                                <thead>
                                <tr>
                                    <th><input type="checkbox" id="chkAllOrganizationRailway"></th>
                                    <th>新建时间</th>
                                    <th>代码</th>
                                    <th>名称</th>
                                    <th>简称</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $frmUpdate = $('#frmUpdate');
        let $frmBindOrganizationRailway = $("#frmBindOrganizationRailway");
        let $txtUniqueCode = $("#txtUniqueCode");
        let $txtName = $("#txtName");
        let $rdoBeEnableYes = $("#rdoBeEnableYes");
        let $rdoBeEnableNo = $("#rdoBeEnableNo");
        let organizationLine = null;
        let tblOrganizationRailway = null;
        let boundOrganizationRailwayUUIDs = [];

        /**
         * 初始化数据
         */
        function fnInit() {
            $.ajax({
                url: `{{ route("web.OrganizationLine:Show", ["uuid" => $uuid, ]) }}`,
                type: 'get',
                data: {},
                async: true,
                success: res => {
                    console.log(`{{ route("web.OrganizationLine:Show", ["uuid" => $uuid, ]) }} success:`, res);

                    organizationLine = res["data"]["organization_line"];

                    let {unique_code: uniqueCode, name, be_enable: beEnable, organization_railways: organizationRailways,} = organizationLine;

                    $txtUniqueCode.val(uniqueCode);
                    $txtName.val(name);
                    if (beEnable) {
                        $rdoBeEnableYes.prop("checked", "checked");
                    } else {
                        $rdoBeEnableNo.prop("checked", "checked");
                    }

                    // 解析已经绑定的路局uuid
                    if (organizationRailways.length > 0) {
                        organizationRailways.map(function (organizationRailway) {
                            boundOrganizationRailwayUUIDs.push(organizationRailway["uuid"]);
                        });
                    }
                },
                error: err => {
                    console.log(`{{ route("web.OrganizationLine:Show", ["uuid" => $uuid, ]) }} fail:`, err);
                    layer.msg(err["responseJSON"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = `{{ route("web.Authorization:GetLogin") }}`;
                    });
                },
            });
        }

        /**
         * 加载路局表格
         */
        function fnFillTblOrganizationRailway() {
            if (document.getElementById('tblOrganizationRailway')) {
                tblOrganizationRailway = $('#tblOrganizationRailway').DataTable({
                    ajax: {
                        url: `{{ route("web.OrganizationRailway:Index") }}?{!! http_build_query(request()->all()) !!}`,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.OrganizationRailway:Index") }}?{!! http_build_query(request()->all()) !!} success:`, res);
                            let {organization_railways: organizationRailways,} = res["data"];
                            let render = [];
                            if (organizationRailways.length > 0) {
                                $.each(organizationRailways, (_, organizationRailway) => {
                                    let uuid = organizationRailway["uuid"];
                                    let createdAt = organizationRailway["created_at"] ? moment(organizationRailway["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uniqueCode = organizationRailway["unique_code"] ? organizationRailway["unique_code"] : "";
                                    let name = organizationRailway["name"] ? organizationRailway["name"] : "";
                                    let shortName = organizationRailway["short_name"] ? organizationRailway["short_name"] : "";
                                    let divBtnGroup = '';
                                    divBtnGroup += `<td class="">`;
                                    divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                    divBtnGroup += `<a href="{{ route("web.OrganizationLine:Index") }}/${uuid}/edit" class="btn btn-warning"><i class="fa fa-edit"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDelete('${uuid}')"><i class="fa fa-trash"></i></a>`;
                                    divBtnGroup += `</div>`;
                                    divBtnGroup += `</td>`;

                                    render.push([
                                        `<input type="checkbox" class="organization-railway-uuid" name="organization_railway_uuids[]" value="${uuid}" ${boundOrganizationRailwayUUIDs.indexOf(uuid) > -1 ? "checked" : ""} onchange="$('#chkAllOrganizationRailway').prop('checked', $('.organization-railway-uuid').length === $('.organization-railway-uuid:checked').length)">`,
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
                            console.log(`{{ route("web.OrganizationRailway:Index") }}?{!! http_build_query(request()->all()) !!} fail:`, err);
                            if (err["status"] === 406) {
                                layer.alert(err["responseJSON"]["msg"], {icon:2, });
                            }else{
                                layer.msg(err["responseJSON"]["msg"], {time: 1500,}, function () {
                                    if (err["status"] === 401) location.href = `{{ route("web.Authorization:GetLogin") }}`;
                                });
                            }
                        },
                    },
                    columnDefs: [{
                        orderable: false,
                        targets: 0,  // 清除第一列排序
                    }],
                    paging: true,  // 分页器
                    lengthChange: true,
                    searching: true,  // 搜索框
                    ordering: true,  // 列排序
                    info: true,
                    autoWidth: true,  // 自动宽度
                    order: [[1, 'desc']],  // 排序依据
                    iDisplayLength: 50,  // 默认分页数
                    aLengthMenu: [50, 100, 200],  // 分页下拉框选项
                    language: {
                        sInfoFiltered: "从_MAX_中过滤",
                        sProcessing: "正在加载中...",
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
            fnFillTblOrganizationRailway();  // 加载路局

            fnCheckAll("chkAllOrganizationRailway", "organization-railway-uuid");  // 全选路局
        });

        /**
         * 保存
         */
        function fnUpdate() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmUpdate.serializeArray();
            data.push({name: "unique_code", value: organizationLine["unique_code"]});

            $.ajax({
                url: `{{ route('web.OrganizationLine:Update', ["uuid" => $uuid , ]) }}`,
                type: 'put',
                data,
                success: function (res) {
                    console.log(`{{ route('web.OrganizationLine:Update', ["uuid" => $uuid, ]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,});
                },
                error: function (err) {
                    console.log(`{{ route('web.OrganizationLine:Update', ["uuid" => $uuid, ]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                }
            });
        }

        /**
         * 绑定路局
         */
        function fnBindOrganizationRailway() {
            let loading = layer.msg('处理中……', {time: 0,});
            let data = $frmBindOrganizationRailway.serializeArray();

            $.ajax({
                url: `{{ route("web.OrganizationLine:PutBindOrganizationRailways", ["uuid" => $uuid,]) }}`,
                type: 'put',
                data,
                async: true,
                success: res => {
                    console.log(`{{ route("web.OrganizationLine:PutBindOrganizationRailways", ["uuid" => $uuid,]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res['msg'], {time: 1000,});
                },
                error: err => {
                    console.log(`{{ route("web.OrganizationLine:PutBindOrganizationRailways", ["uuid" => $uuid,]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = `{{ route("web.Authorization:GetLogin") }}`;
                    });
                },
            });
        }
    </script>
@endsection