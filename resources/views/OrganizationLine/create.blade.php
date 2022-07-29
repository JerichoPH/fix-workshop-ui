@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            线别管理
            <small>新建</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li><a href="{{ route('web.OrganizationLine:Index') }}"><i class="fa fa-users">&nbsp;</i>线别-列表</a></li>
            <li class="active">线别-新建</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="row">
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">新建线别</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right"></div>
                        <hr>
                    </div>
                    <form class="form-horizontal" id="frmStore">
                        <div class="box-body">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">代码*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="unique_code" type="text" class="form-control" placeholder="唯一、必填" required value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">名称*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input name="name" type="text" class="form-control" placeholder="唯一、必填" required value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-danger">是否启用*：</label>
                                <div class="col-sm-10 col-md-9">
                                    <input type="radio" name="be_enable" id="rdoBeEnableYes" value="1" checked>
                                    <label for="rdoBeEnableYes">是</label>
                                    <input type="radio" name="be_enable" id="rdoBeEnableNo" value="0">
                                    <label for="rdoBeEnableNo">否</label>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a href="{{ route('web.OrganizationLine:Index') }}" class="btn btn-default btn-sm pull-left"><i class="fa fa-arrow-left">&nbsp;</i>返回</a>
                            <a onclick="fnStore()" class="btn btn-success btn-sm pull-right"><i class="fa fa-check">&nbsp;</i>新建</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">路局绑定</h3>
                        <!--右侧最小化按钮-->
                        <div class="btn-group btn-group-sm pull-right">
                            <a href="javascript:" class="btn btn-primary"><i class="fa fa-link">&nbsp;</i>绑定路局</a>
                        </div>
                        <hr>
                    </div>
                    <div class="box-body">
                        <table class="table table-condensed table-hover" id="tblOrganizationRailway">

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let $frmStore = $('#frmStore');
        let $chkBackToIndex = $('#chkBackToIndex');

        $(function () {
            if ($select2.length > 0) $select2.select2();
        });

        /**
         * 新建
         */
        function fnStore() {
            let loading = layer.msg("处理中……", {time: 0,});
            let data = $frmStore.serializeArray();

            $.ajax({
                url: '{{ route('web.OrganizationLine:Store') }}',
                type: 'post',
                data,
                success: function (res) {
                    console.log(`{{ route('web.OrganizationLine:Store') }} success:`, res);
                    layer.close(loading);
                    layer.msg(res.msg, {time: 1000,}, function () {
                        location.reload();
                    });
                },
                error: function (err) {
                    console.log(`{{ route('web.OrganizationLine:Store') }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                        if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                    });
                }
            });
        }
    </script>
@endsection