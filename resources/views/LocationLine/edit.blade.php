@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            线别管理
            <small>编辑</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.LocationLine:index') }}"><i class="fa fa-users">&nbsp;</i>线别-列表</a></li>
            <li class="active">线别-编辑</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <form class="form-horizontal" id="frmUpdate">
                <div class="col-md-5">
                    <div class="box box-solid">
                        <div class="box-header">
                            <h3 class="box-title">编辑线别</h3>
                            <!--右侧最小化按钮-->
                            <div class="box-tools pull-right"></div>
                            <hr>
                        </div>

                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">代码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="unique_code" id="txtUniqueCode" type="text" class="form-control" placeholder="唯一、必填" required value="" disabled autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">名称*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="name" id="txtName" type="text" class="form-control" placeholder="唯一、必填" required value="" autocomplete="off">
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
                        </div>
                        <div class="box-footer">
                            <a href="{{ route('web.LocationLine:index') }}" class="btn btn-default pull-left btn-sm"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <div class="pull-right">
                                <input type="checkbox" id="chkReturn">
                                <label for="chkReturn">保存后返回列表</label>
                                &emsp;
                                <a onclick="fnUpdate()" class="btn btn-warning pull-right btn-sm"><i class="fa fa-check">&nbsp;</i>保存</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $frmUpdate = $('#frmUpdate');
        let $frmBindOrganizationRailways = $("#frmBindOrganizationRailways");
        let $txtUniqueCode = $("#txtUniqueCode");
        let $txtName = $("#txtName");
        let $rdoBeEnableYes = $("#rdoBeEnableYes");
        let $rdoBeEnableNo = $("#rdoBeEnableNo");
        let $chkReturn = $('#chkReturn');
        let locationLine = null;

        /**
         * 初始化数据
         */
        function fnInit() {
            $.ajax({
                url: `{{ route("web.LocationLine:Show", ["uuid" => $uuid, ]) }}`,
                type: 'get',
                data: {},
                async: true,
                success: res => {
                    console.log(`{{ route("web.LocationLine:Show", ["uuid" => $uuid, ]) }} success:`, res);

                    locationLine = res["content"]["location_line"];

                    let {unique_code: uniqueCode, name, be_enable: beEnable,} = locationLine;

                    $txtUniqueCode.val(uniqueCode);
                    $txtName.val(name);
                    if (beEnable) {
                        $rdoBeEnableYes.prop("checked", "checked");
                    } else {
                        $rdoBeEnableNo.prop("checked", "checked");
                    }
                    $txtName.focus();
                },
                error: err => {
                    console.log(`{{ route("web.LocationLine:Show", ["uuid" => $uuid, ]) }} fail:`, err);
                    layer.msg(err["responseJSON"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = `{{ route("web.Authorization:getLogin") }}`;
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
            data.push({name: "unique_code", value: locationLine["unique_code"]});

            $.ajax({
                url: `{{ route('web.LocationLine:Update', ["uuid" => $uuid , ]) }}`,
                type: 'put',
                data,
                success (res) {
                    console.log(`{{ route('web.LocationLine:Update', ["uuid" => $uuid, ]) }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 500,},function(){
                        if($chkReturn.is(':checked')){
                            location.href = '{{ route('web.LocationLine:index') }}';
                        }else{
                            fnInit();
                        }
                    });
                },
                error (err) {
                    console.log(`{{ route('web.LocationLine:Update', ["uuid" => $uuid, ]) }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                    });
                }
            });
        }
    </script>
@endsection