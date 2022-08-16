@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            工区管理
            <small>新建</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.OrganizationWorkArea:Index') }}"><i class="fa fa-users">&nbsp;</i>工区-列表</a></li>
            <li class="active">工区-新建</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">新建工区</h3>
                        <!--右侧最小化按钮-->
                        <div class="pull-right btn-group btn-group-sm"></div>
                        <hr>
                    </div>
                    <form class="form-horizontal" id="frmStore">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">代码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="unique_code" id="txtUniqueCode" type="text" class="form-control" placeholder="唯一，必填" required value="" autocomplete="off">
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
                                    <input type="radio" name="be_enable" id="rdoBeEnableYes" value="1">
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
                                    >
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">工区类型*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select
                                            name="organization_work_area_type_uuid"
                                            id="selOrganizationWorkAreaType"
                                            class="form-control select2"
                                            style="width: 100%;"
                                    >
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ route('web.OrganizationWorkArea:Index') }}" class="btn btn-default btn-sm pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
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
        let $selOrganizationWorkshop = $("#selOrganizationWorkshop");
        let $selOrganizationWorkAreaType = $("#selOrganizationWorkAreaType");

        /**
         * 加载车间下拉列表
         */
        function fnFillSelOrganizationWorkshop() {
            $.ajax({
                url: `{{ route("web.OrganizationWorkshop:Index") }}`,
                type: 'get',
                data: {be_enable: 1,},
                async: true,
                success: res => {
                    console.log(`{{ route("web.OrganizationWorkshop:Index") }} success:`, res);

                    let {organization_workshops: organizationWorkshops,} = res["data"];

                    $selOrganizationWorkshop.empty();
                    $selOrganizationWorkshop.append(`<option value="" disabled selected>未选择</option>`);

                    if (organizationWorkshops.length > 0) {
                        organizationWorkshops.map(function (organizationWorkshop) {
                            $selOrganizationWorkshop.append(`<option value="${organizationWorkshop["uuid"]}">${organizationWorkshop["name"]}</option>`);
                        });
                    }
                },
                error: err => {
                    console.log(`{{ route("web.OrganizationWorkshop:Index") }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                },
            });
        }

        /**
         * 加载工区类型下拉列表
         */
        function fnFillSelOrganizationWorkAreaType() {
            $.ajax({
                url: `{{ route("web.OrganizationWorkAreaType:Index") }}`,
                type: 'get',
                data: {},
                async: true,
                success: res => {
                    console.log(`{{ route("web.OrganizationWorkAreaType:Index") }} success:`, res);

                    let {organization_work_area_types: organizationWorkAreaTypes,} = res["data"];

                    $selOrganizationWorkAreaType.empty();
                    $selOrganizationWorkAreaType.append(`<option value="" disabled selected>未选择</option>`);

                    if (organizationWorkAreaTypes.length > 0) {
                        organizationWorkAreaTypes.map(function (organizationWorkAreaType) {
                            $selOrganizationWorkAreaType.append(`<option value="${organizationWorkAreaType["uuid"]}">${organizationWorkAreaType["name"]}</option>`);
                        });
                    }
                },
                error: err => {
                    console.log(`{{ route("web.OrganizationWorkAreaType:Index") }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                },
            });
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            fnFillSelOrganizationWorkshop();  // 加载车间下拉列表
            fnFillSelOrganizationWorkAreaType();  // 加载工区类型下拉列表
        });

        /**
         * 新建
         */
        function fnStore() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmStore.serializeArray();

            $.ajax({
                url: '{{ route('web.OrganizationWorkArea:Store') }}',
                type: 'post',
                data,
                success: function (res) {
                    console.log(`{{ route('web.OrganizationWorkArea:Store') }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,}, function () {
                        location.reload();
                    });
                },
                error: function (err) {
                    console.log(`{{ route('web.OrganizationWorkArea:Store') }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                }
            });
        }
    </script>
@endsection