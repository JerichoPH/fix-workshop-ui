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
                        <button type="submit" class="btn btn-primary btn-block btn-flat">&emsp;登&emsp;录&emsp;</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section("script")
    <script>
        let $frmLogin = $("#frmLogin");

        function fnLogin(){
            let data = $frmLogin.serializeArray();

            $.ajax({
                url: `{{ route("api.v1.Authorization:PostLogin") }}`,
                type: 'post',
                data,
                async: true,
                success: function (res) {
                    console.log(`{{ route("api.v1.Authorization:PostLogin") }} success:`,res);


                },
                error: function (err) {
                    console.log(`{{ route("api.v1.Authorization:PostLogin") }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }
    </script>
@endsection
