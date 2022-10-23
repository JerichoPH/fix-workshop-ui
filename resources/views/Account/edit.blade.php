@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            用户管理
            <small>编辑</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.Account:index', []) }}"><i class="fa fa-users">&nbsp;</i>用户-列表</a></li>
            <li class="active">用户-编辑</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">编辑用户</h3>
                        <!--右侧最小化按钮-->
                        <div class="pull-right btn-group btn-group-sm"></div>
                        <hr>
                    </div>
                    <form class="form-horizontal" id="frmUpdate">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">账号*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="username" id="txtUsername" type="text" class="form-control" placeholder="必填，唯一" required value="" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">昵称*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="nickname" id="txtNickname" type="text" class="form-control" placeholder="必填，唯一" required value="" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">所属路局：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select name="organization_railway_uuid" id="selOrganizationRailway" class="select2 form-control" onchange="fnFillOrganizationParagraph(this.value)" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">所属站段：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select name="organization_paragraph_uuid" id="selOrganizationParagraph" class="select2 form-control" onchange="fnFillOrganizationWorkshop(this.value)" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">所属车间：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select name="organization_workshop_uuid" id="selOrganizationWorkshop" class="select2 form-control" onchange="fnFillOrganizationWorkArea(this.value)" style="width: 100%;"></select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">所属工区：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select name="organization_work_area_uuid" id="selOrganizationWorkArea" class="select2 form-control" style="width: 100%;"></select>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ route('web.Account:index', []) }}" class="btn btn-default pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a onclick="fnUpdate()" class="btn btn-warning pull-right btn-sm"><i class="fa fa-check">&nbsp;</i>保存</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">编辑密码</h3>
                        <!--右侧最小化按钮-->
                        <div class="pull-right btn-group btn-group-sm"></div>
                        <hr>
                    </div>
                    <br>
                    <form class="form-horizontal" id="frmUpdatePassword">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">旧密码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="old_password" id="txtOldPassword" type="password" class="form-control" placeholder="必填" required value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">新密码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="new_password" id="txtNewPassword" type="password" class="form-control" placeholder="必填" required value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">确认密码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="password_confirmation" id="txtPasswordConfirmation" type="password" class="form-control" placeholder="必填" required value="">
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a onclick="fnUpdatePassword()" class="btn btn-danger pull-right btn-sm"><i class="fa fa-lock">&nbsp;</i>修改密码</a>
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
        let $frmUpdatePassword = $("#frmUpdatePassword");
        let $txtUsername = $("#txtUsername");
        let $txtNickname = $("#txtNickname");
        let $selOrganizationRailway = $("#selOrganizationRailway");
        let $selOrganizationParagraph = $("#selOrganizationParagraph");
        let $selOrganizationWorkshop = $("#selOrganizationWorkshop");
        let $selOrganizationWorkArea = $("#selOrganizationWorkArea");
        let account = null;

        /**
         * 初始化数据
         */
        function fnInit() {
            $.ajax({
                url: `{{ route("web.Account:show", ["uuid" => $uuid]) }}`,
                type: 'get',
                data: {},
                async: false,
                beforeSend() {
                },
                success(res) {
                    console.log(`{{ route("web.Account:show", ["uuid" => $uuid]) }} success:`, res);

                    account = res["content"]["account"];

                    $txtUsername.val(account["username"]);
                    $txtNickname.val(account["nickname"]);
                },
                error(err) {
                    console.log(`{{ route("web.Account:show", ["uuid" => $uuid]) }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                },
                complete() {
                },
            });
        }

        /**
         * 加载路局下拉列表
         */
        function fnFillOrganizationRailway() {
            $.ajax({
                url: `{{ route("web.OrganizationRailway:index") }}`,
                type: 'get',
                data: {be_enable: 1,},
                async: true,
                beforeSend() {
                    $selOrganizationRailway.prop("disabled", "disabled");
                },
                success(res) {
                    console.log(`{{ route("web.OrganizationRailway:index") }} success:`, res);

                    let {organization_railways: organizationRailways} = res["content"];
                    $selOrganizationRailway.empty();
                    $selOrganizationRailway.append(`<option value="">未选择</option>`);
                    if (organizationRailways.length > 0) {
                        organizationRailways.map(organizationRailway => {
                            $selOrganizationRailway.append(`<option value="${organizationRailway["uuid"]}" ${organizationRailway["uuid"] === account["organization_railway_uuid"] ? "selected" : ""}>${organizationRailway["name"]}</option>`);
                        });
                    }
                },
                error(err) {
                    console.log(`{{ route("web.OrganizationRailway:index") }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                },
                complete() {
                    $selOrganizationRailway.removeAttr("disabled");
                },
            });
        }

        /**
         * 加载站段下拉列表
         * @param {string} organizationRailwayUUID
         */
        function fnFillOrganizationParagraph(organizationRailwayUUID = "") {
            $selOrganizationParagraph.empty();
            $selOrganizationParagraph.append(`<option value="">未选择</option>`);

            if (organizationRailwayUUID) {
                $.ajax({
                    url: `{{ route("web.OrganizationParagraph:index") }}`,
                    type: 'get',
                    data: {be_enable: 1, organization_railway_uuid: organizationRailwayUUID,},
                    async: true,
                    beforeSend() {
                        $selOrganizationParagraph.prop("disabled", "disabled");
                    },
                    success(res) {
                        console.log(`{{ route("web.OrganizationParagraph:index") }} success:`, res);

                        let {organization_paragraphs: organizationParagraphs,} = res["content"];
                        if (organizationParagraphs.length > 0) {
                            organizationParagraphs.map(function (organizationParagraph) {
                                $selOrganizationParagraph.append(`<option value="${organizationParagraph["uuid"]}" ${organizationParagraph["uuid"] === account["organization_paragraph_uuid"] ? "selected" : ""}>${organizationParagraph["name"]}</option>`);
                            });
                        }
                    },
                    error(err) {
                        console.log(`{{ route("web.OrganizationParagraph:index") }} fail:`, err);
                        layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                        });
                    },
                    complete() {
                        $selOrganizationParagraph.removeAttr("disabled");
                    }
                });
            }
        }

        /**
         * 加载车间下拉列表
         * @param {string} organizationParagraphUUID
         */
        function fnFillOrganizationWorkshop(organizationParagraphUUID = "") {
            $selOrganizationWorkshop.empty();
            $selOrganizationWorkshop.append(`<option value="">未选择</option>`);

            if (organizationParagraphUUID) {
                $.ajax({
                    url: `{{ route("web.OrganizationWorkshop:index") }}`,
                    type: 'get',
                    data: {
                        be_enable: 1,
                        organization_paragraph_uuid: organizationParagraphUUID,
                        organization_workshop_type_unique_code: ["FIX-WORKSHOP",],
                    },
                    async: true,
                    beforeSend() {
                        $selOrganizationWorkshop.prop("disabled", "disabled");
                    },
                    success(res) {
                        console.log(`{{ route("web.OrganizationWorkshop:index") }} success:`, res);

                        let {organization_workshops: organizationWorkshops,} = res["content"];
                        if (organizationWorkshops.length > 0) {
                            organizationWorkshops.map(function (organizationWorkshop) {
                                $selOrganizationWorkshop.append(`<option value="${organizationWorkshop["uuid"]}" ${organizationWorkshop["uuid"] === account["organization_workshop_uuid"] ? "selected" : ""}>${organizationWorkshop["name"]}</option>`);
                            });
                        }
                    },
                    error(err) {
                        console.log(`{{ route("web.OrganizationWorkshop:index") }} fail:`, err);
                        layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                        });
                    },
                    complete() {
                        $selOrganizationWorkshop.removeAttr("disabled");
                    },
                });
            }
        }

        /**
         * 加载工区下拉列表
         * @param {string} organizationWorkshopUUID
         */
        function fnFillOrganizationWorkArea(organizationWorkshopUUID = "") {
            $selOrganizationWorkArea.empty();
            $selOrganizationWorkArea.append(`<option value="">未选择</option>`);

            if (organizationWorkshopUUID) {
                $.ajax({
                    url: `{{ route("web.OrganizationWorkArea:index") }}`,
                    type: 'get',
                    data: {be_enable: 1, organization_workshop_uuid: organizationWorkshopUUID,},
                    async: true,
                    beforeSend() {
                        $selOrganizationWorkArea.prop("disabled", "disabled");
                    },
                    success(res) {
                        console.log(`{{ route("web.OrganizationWorkArea:index") }} success:`, res);

                        let {organization_work_areas: organizationWorkAreas,} = res["content"];
                        if (organizationWorkAreas.length > 0) {
                            organizationWorkAreas.map(function (organizationWorkArea) {
                                $selOrganizationWorkArea.append(`<option value="${organizationWorkArea["uuid"]}" ${organizationWorkArea["uuid"] === account["organization_work_area_uuid"] ? "selected" : ""}>${organizationWorkArea["name"]}</option>`);
                            });
                        }
                    },
                    error(err) {
                        console.log(`{{ route("web.OrganizationWorkArea:index") }} fail:`, err);
                        layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                        });
                    },
                    complete() {
                        $selOrganizationWorkArea.removeAttr("disabled");
                    },
                });
            }
        }

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            fnInit();  // 初始化数据
            fnFillOrganizationRailway();
            fnFillOrganizationParagraph(account['organization_railway_uuid']);
            fnFillOrganizationWorkshop(account['organization_paragraph_uuid']);
            fnFillOrganizationWorkArea(account['organization_workshop_uuid']);
        });

        /**
         * 保存
         */
        function fnUpdate() {
            let loading = layer.msg('处理中……', {time: 0,});
            let data = $frmUpdate.serializeArray();

            $.ajax({
                url: `{{ route("web.Account:update", ["uuid" => $uuid]) }}`,
                type: 'put',
                data,
                async: false,
                beforeSend() {
                },
                success(res) {
                    console.log(`{{ route("web.Account:update", ["uuid" => $uuid]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res['msg'], {time: 1000,});
                },
                error(err) {
                    console.log(`{{ route("web.Account:update", ["uuid" => $uuid]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                },
                complete() {
                },
            });
        }

        // 修改密码
        function fnUpdatePassword() {
            let data = $frmUpdatePassword.serializeArray();
            let loading = layer.msg('处理中……', {time: 0,});
            $.ajax({
                url: `{{ route("web.Account:UpdatePassword", ["uuid" => $uuid]) }}`,
                type: 'put',
                data,
                async: false,
                beforeSend() {
                },
                success(res) {
                    console.log(`{{ route("web.Account:UpdatePassword", ["uuid" => $uuid]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res['msg'], {time: 1000,}, function () {
                        location.reload();
                    });
                },
                error(err) {
                    console.log(`{{ route("web.Account:UpdatePassword", ["uuid" => $uuid]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                },
                complete() {
                },
            });
        }

    </script>
@endsection