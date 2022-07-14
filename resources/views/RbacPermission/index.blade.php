@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            权限管理
            <small>列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li class="active">权限-列表</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <form id="frmSearch">
                <div class="box-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h3 class="box-title">权限-列表</h3>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <div class="input-group-addon">权限分组</div>
                                <select name="rbac_permission_group_uuid" id="selRbacPermissionGroup" class="select2 form-control" style="width: 100%;"></select>
                                <div class="input-group-btn">
                                    <a href="javascript:" class="btn btn-default" onclick="fnSearch()"><i class="fa fa-search"></i></a>
                                    <a href="javascript:" class="btn btn-success" onclick="fnToCreate()"><i class="fa fa-plus"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="box-body">
                <table class="table table-hover table-striped table-condensed" id="tblRbacPermission">
                    <thead>
                    <tr>
                        <th>新建时间</th>
                        <th>唯一编号</th>
                        <th>分组</th>
                        <th>名称</th>
                        <th>路由</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let tblRbacPermission = null;
        let $selRbacPermissionGroup = $("#selRbacPermissionGroup");
        let $frmSearch = $("#frmSearch");

        /**
         * 填充权限表
         */
        function fnFillTblPermission() {
            if (document.getElementById('tblRbacPermission')) {
                tblRbacPermission = $('#tblRbacPermission').DataTable({
                    ajax: {
                        url: `{{ route("web.RbacPermission:Index") }}?{!! http_build_query(request()->all()) !!}`,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.RbacPermission:Index") }}?{!! http_build_query(request()->all()) !!} success:`, res);
                            let {rbac_permissions: rbacPermissions,} = res['data'];
                            let render = [];
                            if (rbacPermissions.length > 0) {
                                $.each(rbacPermissions, (key, rbacPermission) => {
                                    let uuid = rbacPermission["uuid"];
                                    let createdAt = rbacPermission["created_at"] ? moment(rbacPermission["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let rbacPermissionGroupName = rbacPermission["rbac_permission_group"] ? rbacPermission["rbac_permission_group"]["name"] : "";
                                    let name = rbacPermission["name"];
                                    let uri = rbacPermission["uri"];
                                    let method = rbacPermission["method"];
                                    let divBtnGroup = '';
                                    divBtnGroup += `<td class="">`;
                                    divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                    divBtnGroup += `<a href="{{ url("rbacPermission") }}/${uuid}/edit" class="btn btn-warning"><i class="fa fa-edit"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDelete('${rbacPermission['uuid']}')"><i class="fa fa-trash"></i></a>`;
                                    divBtnGroup += `</div>`;
                                    divBtnGroup += `</td>`;

                                    render.push([
                                        createdAt,
                                        uuid,
                                        rbacPermissionGroupName,
                                        name,
                                        `${method} ${uri}`,
                                        divBtnGroup,
                                    ]);
                                });
                            }
                            return render;
                        },
                    },
                    // columnDefs: [{
                    //     orderable: false,
                    //     targets: 0,  // 清除第一列排序
                    // }],
                    paging: true,  // 分页器
                    lengthChange: true,
                    searching: true,  // 搜索框
                    ordering: true,  // 列排序
                    info: true,
                    autoWidth: true,  // 自动宽度
                    order: [[0, 'desc']],  // 排序依据
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

        /**
         * 填充权限分组列表
         */
        function fnFillSelRbacPermissionGroup() {
            $.ajax({
                url: `{{ route("web.RbacPermissionGroup:Index") }}`,
                type: 'get',
                data: {},
                async: true,
                success: res => {
                    console.log(`{{ route("web.RbacPermissionGroup:Index") }} success:`, res);

                    let {rbac_permission_groups: rbacPermissionGroups,} = res["data"];

                    if (rbacPermissionGroups.length > 0) {
                        $selRbacPermissionGroup.empty();
                        $selRbacPermissionGroup.append(`<option value="">全部</option>`);
                        rbacPermissionGroups.map(function (rbacPermissionGroup) {
                            $selRbacPermissionGroup.append(`<option value="${rbacPermissionGroup["uuid"]}" ${"{{ request("rbac_permission_group_uuid") }}" === rbacPermissionGroup["uuid"] ? "selected" : ""}>${rbacPermissionGroup["name"]}</option>`);
                        });
                    }
                },
                error: err => {
                    console.log(`{{ route("web.RbacPermissionGroup:Index") }} fail:`, err);
                    layer.msg(err["responseJSON"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = `{{ route("web.Authorization:GetLogin") }}`;
                    });
                },
            });
        }

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            fnFillTblPermission();  // 填充权限表
            fnFillSelRbacPermissionGroup();  // 填充权限分组列表
        });

        /**
         * 搜索
         */
        function fnSearch() {
            let queries = $.param($frmSearch.serializeArray());

            tblRbacPermission.ajax.url(`{{ route("web.RbacPermission:Index") }}?${queries}`);
            tblRbacPermission.ajax.reload();
        }

        /**
         * 跳转到新建页面
         */
        function fnToCreate() {
            location.href = `{{ route('web.RbacPermission:Create') }}?${$.param($frmSearch.serializeArray())}`;
        }

        /**
         * 删除
         * @param uuid
         */
        function fnDelete(uuid = "") {
            if (uuid && confirm("删除不可恢复，是否确认？")) {
                let loading = layer.msg('处理中……', {time: 0,});
                $.ajax({
                    url: `{{ url("rbacPermission") }}/${uuid}`,
                    type: 'delete',
                    data: {},
                    async: true,
                    success: res => {
                        console.log(`{{ url("rbacPermission") }}/${uuid} success:`, res);
                        layer.close(loading);
                        layer.msg(res['msg'], {time: 1000,}, function () {
                            tblRbacPermission.ajax.reload();
                        });
                    },
                    error: err => {
                        console.log(`{{ url("rbacPermission") }}/${uuid} fail:`, err);
                        layer.close(loading);
                        layer.msg(err["responseJSON"], {time: 1500,}, () => {
                            if (err.status === 401) location.href = `{{ route("web.Authorization:GetLogin") }}`;
                        });
                    },
                });
            }
        }
    </script>
@endsection