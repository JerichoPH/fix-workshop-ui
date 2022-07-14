@extends('Layout.index')
@section('content')
    {{-- 面包屑 --}}
    <section class="content-header">
        <h1>
            菜单管理
            <small>编辑</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="javascript:" onclick="fnToIndex()"><i class="fa fa-users">&nbsp;</i>菜单管理</a></li>
            <li class="active">编辑</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">编辑菜单</h3>
                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right"></div>
                    </div>
                    <br>
                    <form class="form-horizontal" id="frmUpdate">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">名称：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="name" id="txtName" type="text" class="form-control" placeholder="必填，和URL组合唯一" value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">URL：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="url" id="txtUrl" type="text" class="form-control" placeholder="选填，和URL组合唯一" value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">路由名称：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="uri_name" id="txtUriName" type="text" class="form-control" placeholder="选填" value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">图标：</label>
                                <div class="col-sm-10 col-md-9">
                                    <div class="input-group">
                                        <input name="icon" id="txtIcon" type="text" class="form-control" placeholder="选填" value="">
                                        <div class="input-group-addon"><i id="iIcon"></i></div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">所属父级：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select name="parent_uuid" id="selParentMenu" class="select2 form-control" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">所属角色：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select name="rbac_role_uuids[]" id="selSelRbacRoles" class="form-control select2" style="width: 100%;"></select>
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
        let $frmUpdate = $("#frmUpdate");
        let $txtName = $("#txtName");
        let $txtUrl = $("#txtUrl");
        let $txtUriName = $("#txtUriName");
        let $txtIcon = $("#txtIcon");
        let $selParentMenu = $("#selParentMenu");
        let $selSelRbacRoles = $("#selSelRbacRoles");
        let $iIcon = $("#iIcon");
        let menu = null;
        let rbacRoleUUIDs = [];

        /**
         * 初始化数据
         */
        function fnInit() {
            $.ajax({
                url: `{{ route("web.Menu:Show", ["uuid" => $uuid]) }}`,
                type: 'get',
                data: {},
                async: false,
                success: function (res) {
                    console.log(`{{ route("web.Menu:Show", ["uuid" => $uuid]) }} success:`, res);

                    menu = res["data"]["menu"];
                    $txtName.val(menu["name"]);
                    $txtUrl.val(menu["url"])
                    $txtUriName.val(menu["uri_name"]);
                    $txtIcon.val(menu["icon"]);
                    $iIcon
                    fnFillSelParentMenu(menu["parent_uuid"]);  // 填充父级菜单下拉列表
                    fnFillSelRbacRoles();  // 填充角色下拉列表
                },
                error: function (err) {
                    console.log(`{{ route("web.Menu:Show", ["uuid" => $uuid]) }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 填充父级菜单下拉列表
         */
        function fnFillSelParentMenu(uuid = "") {
            $.ajax({
                url: `{{ route("web.Menu:Index") }}`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ route("web.Menu:Index") }} success:`, res);

                    let {menus,} = res["data"];
                    $selParentMenu.empty();
                    $selParentMenu.append(`<option value="">顶级</option>`);
                    menu["rbac_roles"].map(function (rbacRole) {
                        rbacRoleUUIDs.push(rbacRole["uuid"]);
                    });

                    if (menus.length > 0) {
                        menus.map(function (menu) {
                            $selParentMenu.append(`<option value="${menu["uuid"]}" ${uuid === menu["uuid"] ? "selected" : ""}>${menu["name"]}</option>`);
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
                    $selSelRbacRoles.empty();
                    if (rbacRoles.length > 0) {
                        rbacRoles.map(function (rbacRole) {
                            $selSelRbacRoles.append(`<option value="${rbacRole["uuid"]}" ${rbacRoleUUIDs.indexOf(rbacRole["uuid"]) > -1 ? "selected" : ""}>${rbacRole["name"]}</option>`);
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

            fnInit();  // 初始化数据
        });

        /**
         * 保存
         */
        function fnUpdate() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmUpdate.serializeArray();

            $.ajax({
                url: `{{ route("web.Menu:Update", ["uuid" => $uuid]) }}`,
                type: "put",
                data,
                success: function (res) {
                    console.log('success:', res);

                    layer.close(loading);
                    layer.msg(res["msg"], {time: 1000,}, function () {
                    });
                },
                error: function (err) {
                    console.log('fail:', err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, function () {
                        if (err["status"] === 401) location.href = "{{ route("web.Authorization:GetLogin") }}";
                    });
                }
            });
        }

        /**
         * 返回列表
         */
        function fnToIndex() {
            let queries = $.param({parent_uuid: $selParentMenu.val()});
            location.href = `{{ route("web.Menu:Index") }}?${queries}`;
        }

        /**
         * 修改图标
         * @param className
         */
        function fnChangeIcon(className = "") {
            $iIcon.removeAttr("class");
            $iIcon.addClass(className);
        }
    </script>
@endsection