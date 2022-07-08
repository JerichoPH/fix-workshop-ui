@extends('Layout.login')
@section('title')
    登录
@endsection
@section('style')
    <style>
        #bodyLogin {
            background: url('/images/bg-1.jpg');
            filter: "progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod='scale')";
            -moz-background-size: 100% 100%;
            background-size: 100% 100%;
            overflow-y: hidden
        }
    </style>
@endsection
@section('content')
    <div class="login-box" style="width: 420px;">
        <div class="login-logo" style="font-size: 20px; color: #fff;">{{ env("APP_NAME") }}</div>
        <div class="login-box-body">
            <p class="login-box-msg">登录</p>
            @include('Layout.alert')
            <form id="frmLogin">
                <input type="hidden" name="target" value="{{ request('target','/') ?? '/' }}">
                {{ csrf_field() }}
                <div class="form-group has-feedback">
                    <input name="username" id="txtUsername" type="text" class="form-control" placeholder="账号" value="{{ old('account') }}" required autofocus autocomplete>
                    <span class="form-control-feedback fa fa-envelope"></span>
                </div>
                <div class="form-group has-feedback">
                    <input name="password" id="txtPassword" type="password" class="form-control" placeholder="密码" required value="">
                    <span class="fa fa-lock form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-8">
                    </div>
                    <div class="col-xs-4">
                        <button type="button" class="btn btn-primary btn-block btn-flat" onclick="fnLogin()">&emsp;登&emsp;录&emsp;</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section("script")
    <script>
        let $frmLogin = $("#frmLogin");

        /**
         * 登录
         */
        function fnLogin() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmLogin.serializeArray();

            $.ajax({
                url: `{{ route("web.Authorization:PostLogin") }}`,
                type: 'post',
                data,
                async: true,
                success: function (res) {
                    console.log(`{{ route("web.Authorization:PostLogin") }} success:`, res);
                    layer.close(loading);

                    location.href = "/";
                },
                error: function (err) {
                    console.log(`{{ route("web.Authorization:PostLogin") }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                }
            });
        }
    </script>
@endsection
