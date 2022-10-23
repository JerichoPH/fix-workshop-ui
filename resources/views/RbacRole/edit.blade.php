@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            角色管理
            <small>编辑</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.RbacRole:index', []) }}"><i class="fa fa-users">&nbsp;</i>角色-列表</a></li>
            <li class="active">角色-编辑</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">编辑角色</h3>
                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right"></div>
                        <hr>
                    </div>
                    <form class="form-horizontal" id="frmUpdate">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">名称*：</label>
                                <div class="col-sm-10 col-md-8">
                                    <input name="name" id="txtName" type="text" class="form-control" placeholder="名称" required value="" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ route('web.RbacRole:index', []) }}" class="btn btn-default pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
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
        let $txtName = $("#txtName");
        let rbacRole = null;

        /**
         * 初始化
         */
        function fnInit() {
            $.ajax({
                url: `{{ route("web.RbacRole:show", ["uuid" => $uuid]) }}`,
                type: 'get',
                data: {},
                async: false,
                success: res => {
                    console.log(`{{ route("web.RbacRole:show", ["uuid" => $uuid]) }} success:`, res);
                    rbacRole = res["content"]["rbac_role"];
                    $txtName.val(rbacRole["name"]);
                },
                error: err => {
                    console.log(`{{ route("web.RbacRole:show", ["uuid" => $uuid]) }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                },
            });
        }

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            fnInit();
        });

        /**
         * 保存
         */
        function fnUpdate() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmUpdate.serializeArray();

            $.ajax({
                url: `{{ route('web.RbacRole:update', ['uuid' => $uuid,]) }}`,
                type: 'put',
                data,
                success: function (res) {
                    console.log(`{{ route('web.RbacRole:update', ['uuid' => $uuid,]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,}, function () {
                        location.reload();
                    });
                },
                error: function (err) {
                    console.log(`{{ route('web.RbacRole:update', ['uuid' => $uuid,]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                }
            });
        }
    </script>
@endsection