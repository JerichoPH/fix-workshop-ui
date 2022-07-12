@extends('Layout.index')
@section('content')
    {{-- 面包屑 --}}
    <section class="content-header">
        <h1>
            菜单管理
            <small>新建</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route("web.Menu:Index") }}?page={{ request('page',1) }}"><i class="fa fa-users">&nbsp;</i>菜单管理</a></li>
            <li class="active">新建</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">新建菜单</h3>
                        {{--右侧最小化按钮--}}
                        <div class="btn-group btn-group-sm pull-right"></div>
                    </div>
                    <br>
                    <form class="form-horizontal" id="frmStore">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">名称*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="name" id="txtName" type="text" class="form-control" placeholder="必填，和URL组合唯一" required value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">URL：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="url" id="txtUrl" type="text" class="form-control" placeholder="选填，和名称组合唯一" required value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">路由名称：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="uri_name" id="txtUriName" type="text" class="form-control" placeholder="选填" required value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">所属父级：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select name="parent_uuid" id="selParent" class="form-control select2" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">所属角色：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select name="rbac_role_uuids[]" id="selRbacRoles" class="form-control select2" style="width: 100%;" multiple></select>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ route("web.Menu:Index") }}?page={{ request('page', 1) }}" class="btn btn-default btn-sm pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
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
        let $frmStore = $("#frmStore");
        let $txtName = $("#txtName");
        let $txtUrl = $("#txtUrl");
        let $txtUriName = $("#txtUriName");
        let $selParent = $("#selParent");
        let $selRbacRoles = $("#selRbacRoles");

        /**
         * 填充父级菜单下拉列表
         */
        function fnFillSelParent() {
            $.ajax({
                url: `{{ route("web.Menu:Index") }}`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ route("web.Menu:Index") }} success:`, res);

                    let {menus,} = res["data"];

                    if (menus.length > 0) {
                        $selParent.empty();
                        $selParent.append(`<option value="">顶级</option>`);
                        menus.map(function (menu) {
                            $selParent.append(`<option value="${menu["parent_uuid"]}">${menu["name"]}</option>`);
                        });
                    }
                },
                error: function (err) {
                    console.log(`{{ route("web.Menu:Index") }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 填充角色下拉列表
         */
        function fnFillSelRbacRoles() {
            $.ajax({
                url: `{{ route("web.RbacRole:Index") }}`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ route("web.RbacRole:Index") }} success:`, res);

                    let {rbac_roles: rbacRoles,} = res["data"];

                    if (rbacRoles.length > 0) {
                        $selRbacRoles.empty();
                        rbacRoles.map(function (rbacRole) {
                            $selRbacRoles.append(`<option value="${rbacRole["uuid"]}">${rbacRole["name"]}</option>`);
                        });
                    }
                },
                error: function (err) {
                    console.log(`{{ route("web.RbacRole:Index") }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            fnFillSelParent();  // 填充父级菜单下拉列表
            fnFillSelRbacRoles();  // 填充角色下拉列表
        });

        /**
         * 新建
         */
        function fnStore() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmStore.serializeArray();

            $.ajax({
                url: `{{ route("web.Menu:Store") }}`,
                type: "post",
                data,
                success: function (res) {
                    console.log(`{{ route("web.Menu:Store") }} success:`, res);
                    layer.close(loading);
                    layer.msg(res["msg"], {time: 1000,}, function () {
                        // location.reload();
                    });
                },
                error: function (err) {
                    console.log(`{{ route("web.Menu:Store") }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"],{time:1500,},function(){
                        if(err["status"] === 401) location.href = "{{ route("web.Authorization:GetLogin") }}";
                    });
                }
            });
        }
    </script>
@endsection