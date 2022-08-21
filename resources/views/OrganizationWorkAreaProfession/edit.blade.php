@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            工区专业管理
            <small>编辑</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.OrganizationWorkAreaProfession:Index') }}"><i class="fa fa-users">&nbsp;</i>工区专业-列表</a></li>
            <li class="active">工区专业-编辑</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">编辑工区专业</h3>
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
                        </div>
                        <div class="box-footer">
                            <a href="{{ route('web.OrganizationWorkAreaProfession:Index') }}" class="btn btn-default pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
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
        let organizationWorkAreaProfession = null;

        /**
         * 初始化数据
         */
        function fnInit() {
            $.ajax({
                url: `{{ route("web.OrganizationWorkAreaProfession:Show", ["uuid" => $uuid,]) }}`,
                type: 'get',
                data: {},
                async: true,
                success: res => {
                    console.log(`{{ route("web.OrganizationWorkAreaProfession:Show", ["uuid" => $uuid,]) }} success:`, res);

                    organizationWorkAreaProfession = res["data"]["organization_work_area_profession"];

                    $txtUniqueCode.val(organizationWorkAreaProfession["unique_code"]);
                    $txtName.val(organizationWorkAreaProfession["name"]);
                },
                error: err => {
                    console.log(`{{ route("web.OrganizationWorkAreaProfession:Show", ["uuid" => $uuid,]) }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                },
            });
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            fnInit();  // 初始化数据
        });

        /**
         * 保存
         */
        function fnUpdate() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmUpdate.serializeArray();
            data.push({name: "unique_code", value: $txtUniqueCode.val()});

            $.ajax({
                url: `{{ route('web.OrganizationWorkAreaProfession:Update', ["uuid" => $uuid , ]) }}`,
                type: 'put',
                data,
                success: function (res) {
                    console.log(`{{ route('web.OrganizationWorkAreaProfession:Update', ["uuid" => $uuid, ]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,});
                },
                error: function (err) {
                    console.log(`{{ route('web.OrganizationWorkAreaProfession:Update', ["uuid" => $uuid, ]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                }
            });
        }
    </script>
@endsection