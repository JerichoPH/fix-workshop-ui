@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            角色绑定管理
            <small>列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li class="active">角色绑定-列表</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">角色绑定-用户</h3>
                        <!--右侧最小化按钮-->
                        <div class="pull-right btn-group btn-group-sm">
                            <a href="javascript:" class="btn btn-primary" onclick="fnBindAccounts()"><i class="fa fa-link">&nbsp;</i>绑定用户</a>
                        </div>
                        <hr>
                    </div>
                    <div class="box-body">
                        <table class="table table-hover table-striped table-condensed" id="tblAccount">
                            <thead>
                            <tr>
                                <th><input type="checkbox" id="chkAllAccount"></th>
                                <th>注册时间</th>
                                <th>账号</th>
                                <th>昵称</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <h3 class="box-title">角色绑定-权限</h3>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="input-group">
                                    <div class="input-group-addon">权限分组</div>
                                    <select name="rbac_permission_group_uuid" id="selRbacPermissionGroup" class="select2 form-control" style="width: 100%;" onchange="fnFillTblPermission(this.value)"></select>
                                    <div class="input-group-btn">
                                        <a href="javascript:" class="btn btn-primary" onclick="fnBindPermissions()"><i class="fa fa-link">&nbsp;</i>绑定权限</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <table class="table table-hover table-striped table-condensed" id="tblPermission">
                            <thead>
                            <tr>
                                <th><input type="checkbox" id="chkAllRbacPermission"></th>
                                <th>创建时间</th>
                                <th>名称</th>
                                <th>URI</th>
                                <th>方法</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $selRbacPermissionGroup = $("#selRbacPermissionGroup");
        let tblAccount = null;
        let tblPermission = null;
        let rbacRole = null;
        let boundAccountUUIDs = [];
        let boundRbacPermissionUUIDs = [];

        /**
         * 初始化数据
         */
        function fnInit() {
            $.ajax({
                url: `{{ route("web.RbacRole:Show", ["uuid" => $uuid]) }}`,
                type: 'get',
                data: {},
                async: false,
                success: res => {
                    console.log(`{{ route("web.RbacRole:Show", ["uuid" => $uuid]) }} success:`, res);

                    rbacRole = res["content"]["rbac_role"];

                    // 解析已经绑定的用户uuid
                    if (rbacRole["accounts"].length > 0) {
                        rbacRole["accounts"].map(function (account) {
                            boundAccountUUIDs.push(account["uuid"]);
                        });
                    }
                    // 解析已经绑定的权限uuid
                    if (rbacRole["rbac_permissions"].length > 0) {
                        rbacRole["rbac_permissions"].map(function (rbacPermission) {
                            boundRbacPermissionUUIDs.push(rbacPermission["uuid"]);
                        });
                    }
                },
                error: err => {
                    console.log(`{{ route("web.RbacRole:Show", ["uuid" => $uuid]) }} fail:`, err);
                    layer.msg(err["responseJSON"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = `{{ route("web.Authorization:GetLogin") }}`;
                    });
                },
            });
        }

        /**
         * 填充用户表
         */
        function fnFillTblAccount() {
            if (document.getElementById('tblAccount')) {
                return tblAccount = $('#tblAccount').DataTable({
                    ajax: {
                        url: `{{ route("web.Account:Index") }}`,
                        async: false,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.Account:Index") }} success:`, res);
                            let {accounts,} = res["content"];
                            let render = [];
                            if (accounts.length > 0) {
                                $.each(accounts, (key, account) => {
                                    let uuid = account["uuid"];
                                    let createdAt = account["created_at"] ? moment(account["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let username = account["username"];
                                    let nickname = account["nickname"];

                                    render.push([
                                        `<input type="checkbox" class="account-uuid" name="account_uuids[]" value="${uuid}" ${boundAccountUUIDs.indexOf(uuid) > -1 ? "checked" : ""} onchange="$('#chkAllAccount').prop('checked', $('.account-uuid').length === $('.account-uuid:checked').length)">`,
                                        createdAt,
                                        username,
                                        nickname,
                                    ]);
                                });
                            }
                            return render;
                        },
                        error: function (err) {
                            console.log(`{{ route("web.Account:Index") }} fail:`, err);
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
                    iDisplayLength: 200,  // 默认分页数
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
         * 填充权限分组下拉列表
         */
        function fnFillSelRbacPermissionGroup() {
            $.ajax({
                url: `{{ route("web.RbacPermissionGroup:Index") }}`,
                type: 'get',
                data: {},
                async: false,
                success: res => {
                    console.log(`{{ route("web.RbacPermissionGroup:Index") }} success:`, res);

                    let {rbac_permission_groups: rbacPermissionGroups,} = res["content"];

                    if (rbacPermissionGroups.length > 0) {
                        $selRbacPermissionGroup.empty();
                        $selRbacPermissionGroup.append(`<option value="">无</option>`);
                        rbacPermissionGroups.map(function (rbacPermissionGroup) {
                            $selRbacPermissionGroup.append(`<option value="${rbacPermissionGroup["uuid"]}">${rbacPermissionGroup["name"]}</option>`);
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

        /**
         * 填充权限表
         */
        function fnFillTblPermission(rbacPermissionGroupUUID = "") {
            if (tblPermission == null) {
                tblPermission = $('#tblPermission').DataTable({
                    ajax: {
                        url: `{{ route("web.RbacPermission:Index") }}?rbac_permission_group_uuid=${rbacPermissionGroupUUID}`,
                        async: false,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.RbacPermission:Index") }}?rbac_permission_group_uuid=${rbacPermissionGroupUUID} success:`, res);
                            let {rbac_permissions: rbacPermissions,} = res["content"];
                            let render = [];
                            if (rbacPermissions.length > 0) {
                                $.each(rbacPermissions, (key, rbacPermission) => {
                                    let uuid = rbacPermission["uuid"];
                                    let createdAt = rbacPermission["created_at"] ? moment(rbacPermission["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let name = rbacPermission["name"];
                                    let uri = rbacPermission["uri"];
                                    let method = rbacPermission["method"];

                                    render.push([
                                        `<input type="checkbox" class="rbac-permission-uuid" name="rbac_permission_uuids[]" value="${uuid}"  ${boundRbacPermissionUUIDs.indexOf(uuid) > -1 ? "checked" : ""} onchange="$('#chkAllRbacPermission').prop('checked', $('.rbac-permission-uuid').length === $('.rbac-permission-uuid:checked').length)">`,
                                        createdAt,
                                        name,
                                        uri,
                                        method,
                                    ]);
                                });
                            }
                            return render;
                        },
                        error: function (err) {
                            console.log(`{{ route("web.RbacPermission:Index") }}?rbac_permission_group_uuid=${rbacPermissionGroupUUID} fail:`, err);
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
                    order: [[1, 'asc']],  // 排序依据
                    iDisplayLength: 200,  // 默认分页数
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

            if (rbacPermissionGroupUUID) {
                tblPermission.ajax.url(`{{ route("web.RbacPermission:Index") }}?rbac_permission_group_uuid=${rbacPermissionGroupUUID}`);
                tblPermission.ajax.reload();
            }
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            fnInit();  // 初始化数据
            fnFillTblAccount();  // 填充用户列表
            fnFillSelRbacPermissionGroup();  // 填充权限分组下拉列表

            fnCheckAll("chkAllAccount", "account-uuid");  // 用户全选
            fnCheckAll("chkAllRbacPermission", "rbac-permission-uuid");  // 权限全选
        });

        /**
         * 绑定用户
         */
        function fnBindAccounts() {
            let accountUUIDs = [];
            $(`.account-uuid:checked`).each(function (_, datum) {
                accountUUIDs.push(datum.value);
            });

            if (accountUUIDs.length > 0) {
                let loading = layer.msg('处理中……', {time: 0,});
                $.ajax({
                    url: `{{ route("web.RbacRole:PutBindAccounts", ["uuid" => $uuid]) }}`,
                    type: 'put',
                    data: {account_uuids: accountUUIDs,},
                    async: true,
                    success: res => {
                        console.log(`{{ route("web.RbacRole:PutBindAccounts", ["uuid" => $uuid]) }} success:`, res);
                        layer.close(loading);
                        layer.msg(res['msg'], {time: 1000,}, function () {
                        });
                    },
                    error: err => {
                        console.log(`{{ route("web.RbacRole:PutBindAccounts", ["uuid" => $uuid]) }} fail:`, err);
                        layer.close(loading);
                        layer.msg(err["responseJSON"], {time: 1500,}, () => {
                            if (err.status === 401) location.href = `{{ route("web.Authorization:GetLogin") }}`;
                        });
                    },
                });
            }

        }

        /**
         * 绑定权限
         */
        function fnBindPermissions() {
            let rbacPermissionUUIDs = [];
            $(`.rbac-permission-uuid:checked`).each(function (_, datum) {
                rbacPermissionUUIDs.push(datum.value);
            });

            if (rbacPermissionUUIDs.length > 0) {
                let loading = layer.msg('处理中……', {time: 0,});
                $.ajax({
                    url: `{{ route("web.RbacRole:PutBindPermissions", ["uuid" => $uuid]) }}`,
                    type: 'put',
                    data: {rbac_permission_uuids: rbacPermissionUUIDs,},
                    async: true,
                    success: res => {
                        console.log(`{{ route("web.RbacRole:PutBindPermissions", ["uuid" => $uuid]) }} success:`, res);
                        layer.close(loading);
                        layer.msg(res['msg'], {time: 1000,}, function () {
                        });
                    },
                    error: err => {
                        console.log(`{{ route("web.RbacRole:PutBindPermissions", ["uuid" => $uuid]) }} fail:`, err);
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