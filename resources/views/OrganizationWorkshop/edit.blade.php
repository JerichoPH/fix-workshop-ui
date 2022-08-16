@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            车间管理
            <small>编辑</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.OrganizationWorkshop:Index') }}"><i class="fa fa-users">&nbsp;</i>车间-列表</a></li>
            <li class="active">车间-编辑</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">编辑车间</h3>
                        <!--右侧最小化按钮-->
                        <div class="pull-right btn-group btn-group-sm"></div>
                        <hr>
                    </div>
                    <form class="form-horizontal" id="frmUpdate">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">代码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="unique_code" id="txtUniqueCode" type="text" class="form-control" placeholder="唯一，必填" required value="" autocomplete="off" disabled>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">名称*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="name" id="txtName" type="text" class="form-control" placeholder="唯一，必填" required value="" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">所属站段*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select
                                            name="organization_paragraph_uuid"
                                            id="selOrganizationParagraph"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">车间类型*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select
                                            name="organization_workshop_type_uuid"
                                            id="selOrganizationWorkshopType"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >
                                    </select>
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
                            <a href="{{ route('web.OrganizationWorkshop:Index') }}" class="btn btn-default pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
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
        let $txtUniqueCode = $("#txtUniqueCode");
        let $txtName = $("#txtName");
        let $selOrganizationParagraph = $("#selOrganizationParagraph");
        let $selOrganizationWorkshopType = $("#selOrganizationWorkshopType");
        let $rdoBeEnableYes = $("#rdoBeEnableYes");
        let $rdoBeEnableNo = $("#rdoBeEnableNo");
        let organizationWorkshop = null;

        /**
         * 初始化数据
         */
        function fnFillInit() {
            $.ajax({
                url: `{{ route("web.OrganizationWorkshop:Show", ["uuid" => $uuid,]) }}`,
                type: 'get',
                data: {},
                async: true,
                success: res => {
                    console.log(`{{ route("web.OrganizationWorkshop:Show", ["uuid" => $uuid,]) }} success:`, res);

                    organizationWorkshop = res["data"]["organization_workshop"];
                    $txtUniqueCode.val(organizationWorkshop["unique_code"]);
                    $txtName.val(organizationWorkshop["name"]);
                    fnFillSelOrganizationParagraph(organizationWorkshop["organization_paragraph"]["uuid"]);
                    fnFillSelOrganizationWorkshopType(organizationWorkshop["organization_workshop_type"]["uuid"]);
                    if (organizationWorkshop["be_enable"]) {
                        $rdoBeEnableYes.prop("checked", "checked");
                    } else {
                        $rdoBeEnableNo.prop("checked", "checked");
                    }
                },
                error: err => {
                    console.log(`{{ route("web.OrganizationWorkshop:Show", ["uuid" => $uuid,]) }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                        });
                },
            });
        }

        /**
         * 加载路局下拉列表
         * @param {string} organizationParagraphUUID
         */
        function fnFillSelOrganizationParagraph(organizationParagraphUUID = "") {
            $.ajax({
                url: `{{ route("web.OrganizationParagraph:Index") }}`,
                type: 'get',
                data: {be_enable: 1,},
                async: true,
                success: res => {
                    console.log(`{{ route("web.OrganizationParagraph:Index") }} success:`, res);

                    let {organization_paragraphs: organizationParagraphs,} = res["data"];

                    $selOrganizationParagraph.empty();
                    $selOrganizationParagraph.append(`<option value="" disabled selected>未选择</option>`);

                    if (organizationParagraphs.length > 0) {
                        organizationParagraphs.map(function (organizationParagraph) {
                            $selOrganizationParagraph.append(`<option value="${organizationParagraph["uuid"]}" ${organizationParagraphUUID === organizationParagraph["uuid"] ? "selected" : ""}>${organizationParagraph["name"]}</option>`);
                        });
                    }
                },
                error: err => {
                    console.log(`{{ route("web.OrganizationRailway:Index") }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                        });
                },
            });
        }

        /**
         * 加载车间类型下拉列表
         * @param {string} organizationWorkshopTypeUUID
         */
        function fnFillSelOrganizationWorkshopType(organizationWorkshopTypeUUID = "") {
            $.ajax({
                url: `{{ route("web.OrganizationWorkshopType:Index") }}`,
                type: 'get',
                data: {},
                async: true,
                success: res => {
                    console.log(`{{ route("web.OrganizationWorkshopType:Index") }} success:`, res);

                    let {organization_workshop_types: organizationWorkshopTypes,} = res["data"];

                    $selOrganizationWorkshopType.empty();
                    $selOrganizationWorkshopType.append(`<option value="" disabled selected>未选择</option>`);

                    if (organizationWorkshopTypes.length > 0) {
                        organizationWorkshopTypes.map(function (organizationWorkshopType) {
                            $selOrganizationWorkshopType.append(`<option value="${organizationWorkshopType["uuid"]}" ${organizationWorkshopTypeUUID === organizationWorkshopType["uuid"] ? "selected" : ""}>${organizationWorkshopType["name"]}</option>`)
                        });
                    }
                },
                error: err => {
                    console.log(`{{ route("web.OrganizationWorkshopType:Index") }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                        });
                },
            });
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            fnFillInit(); // 初始化数据
        });

        /**
         * 保存
         */
        function fnUpdate() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmUpdate.serializeArray();

            $.ajax({
                url: `{{ route('web.OrganizationWorkshop:Update', ["uuid" => $uuid , ]) }}`,
                type: 'put',
                data,
                success: function (res) {
                    console.log(`{{ route('web.OrganizationWorkshop:Update', ["uuid" => $uuid, ]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,});
                },
                error: function (err) {
                    console.log(`{{ route('web.OrganizationWorkshop:Update', ["uuid" => $uuid, ]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                        });
                }
            });
        }
    </script>
@endsection