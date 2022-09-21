@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            权限管理
            <small>新建</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.RbacPermission:Index', ["rbac_permission_group_uuid" => request("rbac_permission_group_uuid") ]) }}"><i class="fa fa-users">&nbsp;</i>权限-列表</a></li>
            <li class="active">权限-新建</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">新建权限</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right"></div>
                        <hr>
                    </div>
                    <form class="form-horizontal" id="frmStore">
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
                            <a href="{{ route('web.RbacPermission:Index', ["rbac_permission_group_uuid" => request("rbac_permission_group_uuid") ]) }}" class="btn btn-default btn-sm pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a onclick="fnStore()" class="btn btn-success btn-sm pull-right"><i class="fa fa-check">&nbsp;</i>新建</a>
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
        let $frmStore = $('#frmStore');
        let $selRbacPermissionGroup = $("#selRbacPermissionGroup");
        let $txtName = $("#txtName");
        let $txtUri = $("#txtUri");
        let $selMethod = $("#selMethod");

        /**
         * 填充权限分组下拉菜单
         */
        function fnFillSelRbacPermissionGroup() {
            $.ajax({
                url: `{{ route("web.RbacPermissionGroup:Index") }}`,
                type: 'get',
                data: {},
                async: true,
                success: res => {
                    console.log(`{{ route("web.RbacPermissionGroup:Index") }} success:`, res);

                    let {rbac_permission_groups: rbacPermissionGroups,} = res["content"];

                    if (rbacPermissionGroups.length > 0) {
                        $selRbacPermissionGroup.empty();
                        $selRbacPermissionGroup.append(`<option value="" disabled selected>无</option>`);
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

        /**
         * 填充访问方法下拉菜单
         */
        function fnFillSelMethod() {
            let methods = ["GET", "POST", "PUT", "DELETE"];

            $selMethod.empty();
            $selMethod.append(`<option value="" disabled selected>无</option>`);

            methods.map(function (method) {
                $selMethod.append(`<option value="${method}">${method}</option>`);
            });
        }

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            fnFillSelRbacPermissionGroup();  // 填充权限分组下拉菜单
            fnFillSelMethod();  // 填充访问方法下拉菜单
        });

        /**
         * 新建
         */
        function fnStore() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmStore.serializeArray();

            $.ajax({
                url: '{{ route('web.RbacPermission:Store') }}',
                type: 'post',
                data,
                success: function (res) {
                    console.log(`{{ route('web.RbacPermission:Store') }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,}, function () {
                        location.reload();
                    });
                },
                error: function (err) {
                    console.log(`{{ route('web.RbacPermission:Store') }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                }
            });
        }
    </script>
@endsection