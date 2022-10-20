@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            区间管理
            <small>新建</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.LocationCenter:index') }}"><i class="fa fa-users">&nbsp;</i>区间-列表</a></li>
            <li class="active">区间-新建</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <form class="form-horizontal" id="frmStore">
            <div class="row">
                <div class="col-md-6">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">新建区间</h3>
                            <!--右侧最小化按钮-->
                            <div class="pull-right btn-group btn-group-sm"></div>
                            <hr>
                        </div>
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">代码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="unique_code" id="txtUniqueCode" type="text" class="form-control" placeholder="唯一，必填 3位：F01" required value="" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">名称*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="name" id="txtName" type="text" class="form-control" placeholder="唯一，必填" required value="" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">是否启用*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input type="radio" name="be_enable" id="rdoBeEnableYes" value="1" checked>
                                    <label for="rdoBeEnableYes">是</label>
                                    &emsp;
                                    <input type="radio" name="be_enable" id="rdoBeEnableNo" value="0">
                                    <label for="rdoBeEnableNo">否</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">所属车间*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select
                                            name="organization_workshop_uuid"
                                            id="selOrganizationWorkshop"
                                            class="form-control select2"
                                            style="width: 100%;"
                                            onchange="fnFillSelOrganizationWorkArea(this.value)"
                                    >
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">所属工区：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select
                                            name="organization_work_area_uuid"
                                            id="selOrganizationWorkArea"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">所属线别：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select
                                            name="location_line_uuid"
                                            id="selLocationLine"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ route('web.LocationCenter:index') }}" class="btn btn-default btn-sm pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a onclick="fnStore()" class="btn btn-success btn-sm pull-right"><i class="fa fa-check">&nbsp;</i>新建</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">绑定线别</h3>
                            <!--右侧最小化按钮-->
                            <div class="btn-group btn-group-sm pull-right"></div>
                            <hr>
                        </div>
                        <div class="box-body">
                            <table class="table table-hover table-condensed" id="tblLocationLine">
                                <thead>
                                <tr>
                                    <th>行号</th>
                                    <th><input type="checkbox" id="chkAllLocationLine"></th>
                                    <th>新建时间</th>
                                    <th>代码</th>
                                    <th>名称</th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $frmStore = $('#frmStore');
        let $selOrganizationWorkshop = $("#selOrganizationWorkshop");
        let $selOrganizationWorkArea = $("#selOrganizationWorkArea");
        let $selLocationLine = $('#selLocationLine');

        /**
         * 加载车间下拉列表
         */
        function fnFillSelOrganizationWorkshop() {
            $.ajax({
                url: `{{ route("web.OrganizationWorkshop:index") }}`,
                type: 'get',
                data: {be_enable: 1, organization_workshop_type_unique_code: "SCENE-WORKSHOP",},
                async: true,
                beforeSend: function () {
                    $selOrganizationWorkshop.attr('disabled', 'disabled');
                },
                success: res => {
                    console.log(`{{ route("web.OrganizationWorkshop:index") }} success:`, res);

                    let {organization_workshops: organizationWorkshops,} = res["content"];

                    $selOrganizationWorkshop.empty();
                    $selOrganizationWorkshop.append(`<option value="" disabled selected>未选择</option>`);

                    if (organizationWorkshops.length > 0) {
                        organizationWorkshops.map(function (organizationWorkshop) {
                            $selOrganizationWorkshop.append(`<option value="${organizationWorkshop["uuid"]}">${organizationWorkshop["name"]}</option>`);
                        });
                    }
                },
                error: err => {
                    console.log(`{{ route("web.OrganizationWorkshop:index") }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                },
                complete: function () {
                    $selOrganizationWorkshop.removeAttr('disabled');
                },
            });
        }

        /**
         * 加载工区下拉列表
         * @param {string} organizationWorkshopUuid
         */
        function fnFillSelOrganizationWorkArea(organizationWorkshopUuid = '') {
            let data = {be_enable: 1, organization_work_area_type_unique_code: "SCENE-WORK-AREA",}
            if (organizationWorkshopUuid) data['organization_workshop_uuid'] = organizationWorkshopUuid

            $.ajax({
                url: `{{ route("web.OrganizationWorkArea:index") }}`,
                type: 'get',
                data,
                async: true,
                beforeSend: function () {
                    $selOrganizationWorkArea.attr('disabled', 'disabled');
                },
                success: function (res) {
                    console.log(`{{ route("web.OrganizationWorkArea:index") }} success:`, res);

                    let {organization_work_areas: organizationWorkAreas,} = res["content"];

                    $selOrganizationWorkArea.empty();
                    $selOrganizationWorkArea.append(`<option value="">未选择</option>`);

                    if (organizationWorkAreas.length > 0) {
                        organizationWorkAreas.map(function (organizationWorkArea) {
                            $selOrganizationWorkArea.append(`<option value="${organizationWorkArea["uuid"]}">${organizationWorkArea["name"]}</option>`);
                        });
                    }
                },
                error: function (err) {
                    console.log(`{{ route("web.OrganizationWorkArea:index") }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                },
                complete: function () {
                    $selOrganizationWorkArea.removeAttr('disabled');
                },
            });
        }

        /**
         * 加载线别下拉列表
         */
        function fnFillSelLocationLine() {
            $.ajax({
                url: `{{ route('web.LocationLine:index') }}`,
                type: 'get',
                data: {},
                async: true,
                beforeSend: function () {
                    $selLocationLine.empty();
                    $selLocationLine.append(`<option value="">无</option>`);
                    $selLocationLine.attr('disabled', true);
                },
                success: function (res) {
                    console.log(`{{ route('web.LocationLine:index') }} success:`, res);

                    let {location_lines: locationLines,} = res['data'];
                    if (locationLines.length > 0) {
                        locationLines.map(function (locationLine) {
                            $selLocationLine.append(`<option value="${locationLine['uuid']}">${locationLine['name']}</option>`);
                        });
                    }
                },
                error: function (err) {
                    console.log(`{{ route('web.LocationLine:index') }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                },
                complete: function () {
                    $selLocationLine.attr('disabled', false);
                },
            });
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            fnFillSelOrganizationWorkshop();  // 加载车间下拉列表
            fnFillSelOrganizationWorkArea();  // 加载工区下拉列表
            fnFillSelLocationLine();  // 加载线别下拉列表

            fnCheckAll("chkAllLocationLine", "location-line-uuid");  // 全选线别
        });

        /**
         * 新建
         */
        function fnStore() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmStore.serializeArray();

            $.ajax({
                url: '{{ route('web.LocationCenter:store') }}',
                type: 'post',
                data,
                success: function (res) {
                    console.log(`{{ route('web.LocationCenter:store') }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,}, function () {
                        location.reload();
                    });
                },
                error: function (err) {
                    console.log(`{{ route('web.LocationCenter:store') }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                }
            });
        }
    </script>
@endsection