@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            权限管理
            <small>编辑</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="javascript:" onclick="fnToIndex()"><i class="fa fa-users">&nbsp;</i>权限-列表</a></li>
            <li class="active">权限-编辑</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">编辑权限</h3>
                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right"></div>
                        <hr>
                    </div>
                    <form class="form-horizontal" id="frmUpdate">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">所属权限分组*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="rbac_permission_group_uuid" id="selRbacPermissionGroup" class="form-control select2" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">名称*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="name" id="txtName" type="text" class="form-control" placeholder="必填" required value="" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">URI*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="uri" id="txtUri" type="text" class="form-control" placeholder="必填，唯一" required value="" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">访问方法*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <select name="method" id="selMethod" class="select2 form-control" style="width: 100%;"></select>
                                </div>
                            </div>

                        </div>
                        <div class="box-footer">
                            <a href="javascript:" class="btn btn-default pull-left btn-sm" onclick="fnToIndex()"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a onclick="fnUpdate()" class="btn btn-warning pull-right btn-sm"><i class="fa fa-check">&nbsp;</i>保存</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $frmUpdate = $('#frmUpdate');
        let $selRbacPermissionGroup = $("#selRbacPermissionGroup");
        let $txtName = $("#txtName");
        let $txtUri = $("#txtUri");
        let $selMethod = $("#selMethod");
        let rbacPermission = null;

        /**
         * 初始化数据
         */
        function fnInit() {
            $.ajax({
                url: `{{ route("web.RbacPermission:show", ["uuid" => $uuid]) }}`,
                type: 'get',
                data: {},
                async: false,
                success: res => {
                    console.log(`{{ route("web.RbacPermission:show", ["uuid" => $uuid]) }} success:`, res);

                    rbacPermission = res["content"]["rbac_permission"];

                    fnFillSelRbacPermissionGroup(rbacPermission["rbac_permission_group_uuid"]);  // 填充权限分组下拉列表
                    $txtName.val(rbacPermission["name"]);
                    $txtUri.val(rbacPermission["uri"]);
                    fnFillSelMethod(rbacPermission["method"]);  // 填充访问方法下拉列表
                },
                error: err => {
                    console.log(`{{ route("web.RbacPermission:show", ["uuid" => $uuid]) }} fail:`, err);
                    layer.msg(err["responseJSON"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = `{{ route("web.Authorization:getLogin") }}`;
                    });
                },
            });
        }

        /**
         * 填充权限分组下拉列表
         */
        function fnFillSelRbacPermissionGroup(uuid = "") {
            $.ajax({
                url: `{{ route("web.RbacPermissionGroup:index") }}`,
                type: 'get',
                data: {},
                async: true,
                success: res => {
                    console.log(`{{ route("web.RbacPermissionGroup:index") }} success:`, res);

                    let {rbac_permission_groups: rbacPermissionGroups,} = res["content"];

                    if (rbacPermissionGroups.length > 0) {
                        $selRbacPermissionGroup.empty();
                        $selRbacPermissionGroup.append(`<option value="" disabled selected>无</option>`);
                        rbacPermissionGroups.map(function (rbacPermissionGroup) {
                            $selRbacPermissionGroup.append(`<option value="${rbacPermissionGroup["uuid"]}" ${uuid === rbacPermissionGroup["uuid"] ? "selected" : ""}>${rbacPermissionGroup["name"]}</option>`);
                        });
                    }
                },
                error: err => {
                    console.log(`{{ route("web.RbacPermissionGroup:index") }} fail:`, err);
                    layer.msg(err["responseJSON"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = `{{ route("web.Authorization:getLogin") }}`;
                    });
                },
            });
        }

        /**
         * 填充访问方法下拉菜单
         */
        function fnFillSelMethod(currentMethod = "") {
            let methods = ["GET", "POST", "PUT", "DELETE"];

            $selMethod.empty();
            $selMethod.append(`<option value="" disabled selected>无</option>`);

            methods.map(function (method) {
                $selMethod.append(`<option value="${method}" ${currentMethod === method ? "selected" : ""}>${method}</option>`);
            });
        }

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            fnInit();  // 初始化数据
        });

        /**
         * 保存
         */
        function fnUpdate() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmUpdate.serializeArray();

            $.ajax({
                url: `{{ route('web.RbacPermission:Update', ['uuid' => $uuid, ]) }}`,
                type: 'put',
                data,
                success: function (res) {
                    console.log(`{{ route('web.RbacPermission:Update', ['uuid' => $uuid, ]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,});
                },
                error: function (err) {
                    console.log(`{{ route('web.RbacPermission:Update', ['uuid' => $uuid, ]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                }
            });
        }

        function fnToIndex() {
            let queries = $.param({rbac_permission_group_uuid: $selRbacPermissionGroup.val()});
            location.href = `{{ route("web.RbacPermission:index") }}?${queries}`;
        }
    </script>
@endsection