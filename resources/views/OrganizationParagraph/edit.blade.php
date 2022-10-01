@extends('Layout.index')
@section('content')
    {{-- 面包屑 --}}
    <section class="content-header">
        <h1>
            站段管理
            <small>编辑</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ url('web.OrganizationParagraph:index') }}"><i class="fa fa-users">&nbsp;</i>站段管理</a></li>
            <li class="active">编辑</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">编辑站段</h3>
                        {{--右侧最小化按钮--}}
                        <div class="box-tools pull-right"></div>
                    </div>
                    <br>
                    <form class="form-horizontal" id="frmUpdate">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">代码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="unique_code" id="txtUniqueCode" type="text" class="form-control" placeholder="必填，唯一" required disabled autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">名称*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="name" id="txtName" type="text" class="form-control" placeholder="名称" required autocomplete="off">
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
                            <div class="form-group">
                                <label class="col-sm-2 control-label">所属路局：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select
                                            name="organization_railway_uuid"
                                            id="selOrganizationRailway"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ route('web.OrganizationParagraph:index') }}" class="btn btn-default pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
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
        let $txtUniqueCode = $("#txtUniqueCode");
        let $txtName = $("#txtName");
        let $rdoBeEnableYes = $("#rdoBeEnableYes");
        let $rdoBeEnableNo = $("#rdoBeEnableNo");
        let $selOrganizationRailway = $("#selOrganizationRailway");
        let organizationParagraph = null;

        /**
         * 初始化数据
         */
        function fnInit() {
            $.ajax({
                url: `{{ route("web.OrganizationParagraph:Show", ["uuid" => $uuid,]) }}`,
                type: 'get',
                data: {},
                async: true,
                success: function (res) {
                    console.log(`{{ route("web.OrganizationParagraph:Show", ["uuid" => $uuid,]) }} success:`, res);

                    organizationParagraph = res["content"]["organization_paragraph"];

                    $txtUniqueCode.val(organizationParagraph["unique_code"]);
                    $txtName.val(organizationParagraph["name"]);
                    if (organizationParagraph["be_enable"]) {
                        $rdoBeEnableYes.prop("checked", "checked");
                    } else {
                        $rdoBeEnableNo.prop("checked", "checked");
                    }
                    fnFillSelOrganizationRailway(organizationParagraph["organization_railway_uuid"]);  // 初始化路局下拉菜单
                },
                error: function (err) {
                    console.log(`{{ route("web.OrganizationParagraph:Show", ["uuid" => $uuid,]) }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 初始化路局下拉菜单
         */
        function fnFillSelOrganizationRailway(organizationRailwayUUID = "") {
            $.ajax({
                url: `{{ route("web.OrganizationRailway:index") }}`,
                type: 'get',
                data: {be_enable: 1,},
                async: true,
                success: function (res) {
                    console.log(`{{ route("web.OrganizationRailway:index") }} success:`, res);

                    let {organization_railways: organizationRailways,} = res["content"];
                    $selOrganizationRailway.empty();
                    $selOrganizationRailway.append(`<option value="" disabled>未选择</option>`);
                    if (organizationRailways.length > 0) {
                        organizationRailways.map(function (organizationRailway) {
                            $selOrganizationRailway.append(`<option value="${organizationRailway["uuid"]}" ${organizationRailway["uuid"] === organizationRailwayUUID ? "selected" : ""}>${organizationRailway["name"]}</option>`);
                        });
                    }
                },
                error: function (err) {
                    console.log(`{{ route("web.OrganizationRailway:index") }} fail:`, err);
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
            data.push({name: "unique_code", value: organizationParagraph["unique_code"]});

            $.ajax({
                url: `{{ route('web.OrganizationParagraph:Update', ["uuid" => $uuid,]) }}`,
                type: "put",
                data,
                success: function (res) {
                    console.log(`{{ route('web.OrganizationParagraph:Update', ["uuid" => $uuid,]) }} success:`, res);

                    layer.close(loading);
                    layer.msg(res["msg"], {time: 1000,});
                },
                error: function (err) {
                    console.log(`{{ route('web.OrganizationParagraph:Update', ["uuid" => $uuid,]) }} fail:`, err);
                    layer.close(loading);
                    if (err["status"] === 406) {
                        layer.alert(err["responseJSON"]["msg"], {icon: 2,});
                    } else {
                        layer.msg(err["responseJSON"]["msg"], {time: 1500,}, function () {
                            if (err["status"] === 401) location.href = "{{ route("web.Authorization:getLogin") }}";
                        });
                    }
                }
            });
        }
    </script>
@endsection