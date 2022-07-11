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
            <li><a href="{{ route("web.Menu:Index") }}?page={{ request('page',1) }}"><i class="fa fa-users">&nbsp;</i>菜单管理</a></li>
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
                                <label class="col-sm-2 control-label">所属角色：</label>
                                <div class="col-sm-10 col-md-9">
                                    <select name="organization_id" class="form-control select2" style="width: 100%;">
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ route("web.Menu:Index") }}?page={{ request('page', 1) }}" class="btn btn-default pull-left btn-flat btn-sm"><i class="fa fa-arrow-left btn-flat">&nbsp;</i>返回</a>
                            <a onclick="fnUpdate()" class="btn btn-warning pull-right btn-flat btn-sm"><i class="fa fa-check">&nbsp;</i>保存</a>
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
        $(function () {
            if ($select2.length > 0) $('.select2').select2();
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
    </script>
@endsection