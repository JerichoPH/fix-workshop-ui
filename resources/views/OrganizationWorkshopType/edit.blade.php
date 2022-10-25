@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            车间类型管理
            <small>编辑</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.OrganizationWorkshopType:index') }}"><i class="fa fa-users">&nbsp;</i>车间类型-列表</a></li>
            <li class="active">车间类型-编辑</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">编辑车间类型</h3>
                        <!--右侧最小化按钮-->
                        <div class="pull-right btn-group btn-group-sm"></div>
                        <hr>
                    </div>
                    <form class="form-horizontal" id="frmUpdate">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">名称*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="unique_code" id="txtUniqueCode" type="text" class="form-control" placeholder="唯一，必填" required value="" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">数字代码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="number_code" id="txtNumberCode" type="text" class="form-control" placeholder="唯一，必填" required value="" autocomplete="off">
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
                            <a href="{{ route('web.OrganizationWorkshopType:index') }}" class="btn btn-default pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a onclick="update()" class="btn btn-warning pull-right btn-sm"><i class="fa fa-check">&nbsp;</i>保存</a>
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
        let $txtNumberCode = $("#txtNumberCode");
        let $txtName = $("#txtName");
        let organizationWorkshopType = null;

        /**
         * 初始化数据
         */
        function init() {
            $.ajax({
                url: `{{ route("web.OrganizationWorkshopType:show", ["uuid" => $uuid]) }}`,
                type: 'get',
                data: {},
                async: true,
                success: res => {
                    console.log(`{{ route("web.OrganizationWorkshopType:show", ["uuid" => $uuid]) }} success:`, res);
                    organizationWorkshopType = res["content"]["organization_workshop_type"];

                    $txtUniqueCode.val(organizationWorkshopType["unique_code"]);
                    $txtNumberCode.val(organizationWorkshopType["number_code"]);
                    $txtName.val(organizationWorkshopType["name"]);
                },
                error: err => {
                    console.log(`{{ route("web.OrganizationWorkshopType:show", ["uuid" => $uuid]) }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                },
            });
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            init();  // 初始化数据
        });

        /**
         * 保存
         */
        function update() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmUpdate.serializeArray();

            $.ajax({
                url: `{{ route('web.OrganizationWorkshopType:update', ["uuid" => $uuid , ]) }}`,
                type: 'put',
                data,
                success: function (res) {
                    console.log(`{{ route('web.OrganizationWorkshopType:update', ["uuid" => $uuid, ]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,});
                },
                error: function (err) {
                    console.log(`{{ route('web.OrganizationWorkshopType:update', ["uuid" => $uuid, ]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                }
            });
        }
    </script>
@endsection