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
            <li><a href="{{ route('web.Account:Index', []) }}"><i class="fa fa-users">&nbsp;</i>用户-列表</a></li>
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
                        <div class="box-tools pull-right"></div>
                        <hr>
                    </div>
                    <form class="form-horizontal" id="frmUpdate">
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
                        </div>
                        <div class="box-footer">
                            <a href="{{ route('web.Account:Index', []) }}" class="btn btn-default pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
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
                        <div class="box-tools pull-right"></div>
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

        /**
         * 初始化数据
         */
        function fnInit() {
            $.ajax({
                url: `{{ route("web.Account:Show", ["uuid" => $uuid]) }}`,
                type: 'get',
                data: {},
                async: false,
                success: res => {
                    console.log(`{{ route("web.Account:Show", ["uuid" => $uuid]) }} success:`, res);

                    let account = res["data"]["account"];

                    $txtUsername.val(account["username"]);
                    $txtNickname.val(account["nickname"]);
                },
                error: err => {
                    console.log(`{{ route("web.Account:Show", ["uuid" => $uuid]) }} fail:`, err);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
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
            let data = $frmUpdate.serializeArray();
            let loading = layer.msg('处理中……', {time: 0,});
            $.ajax({
                url: `{{ route("web.Account:Update", ["uuid" => $uuid]) }}`,
                type: 'put',
                data,
                async: true,
                success: res => {
                    console.log(`{{ route("web.Account:Update", ["uuid" => $uuid]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res['msg'], {time: 1000,}, function () {

                    });
                },
                error: err => {
                    console.log(`{{ route("web.Account:Update", ["uuid" => $uuid]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
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
                async: true,
                success: res => {
                    console.log(`{{ route("web.Account:UpdatePassword", ["uuid" => $uuid]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res['msg'], {time: 1000,}, function () {
                        location.reload();
                    });
                },
                error: err => {
                    console.log(`{{ route("web.Account:UpdatePassword", ["uuid" => $uuid]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                },
            });
        }

    </script>
@endsection