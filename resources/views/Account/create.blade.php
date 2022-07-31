@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            用户管理
            <small>新建</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.Account:Index', []) }}"><i class="fa fa-users">&nbsp;</i>用户-列表</a></li>
            <li class="active">用户-新建</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">新建用户</h3>
                        <!--右侧最小化按钮-->
                        <div class="box-tools pull-right"></div>
                        <hr>
                    </div>
                    <form class="form-horizontal" id="frmStore">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">账号*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="username" id="txtUsername" type="text" class="form-control" placeholder="必填，唯一" required value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">昵称*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="nickname" id="txtNickname" type="text" class="form-control" placeholder="必填，唯一" required value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">密码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="password" id="txtPassword" type="password" class="form-control" placeholder="必填" required value="">
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
                            <a href="{{ route('web.Account:Index', []) }}" class="btn btn-default pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a onclick="fnStore()" class="btn btn-success pull-right btn-sm"><i class="fa fa-check">&nbsp;</i>保存</a>
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
        let $txtUsername = $("#txtUsername");
        let $txtNickname = $("#txtNickname");

        $(function () {
            if ($select2.length > 0) $('.select2').select2();
        });

        /**
         * 保存
         */
        function fnStore() {
            let data = $frmStore.serializeArray();
            let loading = layer.msg('处理中……', {time: 0,});
            $.ajax({
                url: `{{ route("web.Account:Store") }}`,
                type: 'post',
                data,
                async: true,
                success: res => {
                    console.log(`{{ route("web.Account:Store") }} success:`, res);
                    layer.close(loading);
                    layer.msg(res['msg'], {time: 1000,}, function () {
                        location.reload();
                    });
                },
                error: err => {
                    console.log(`{{ route("web.Account:Store") }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                },
            });
        }
    </script>
@endsection