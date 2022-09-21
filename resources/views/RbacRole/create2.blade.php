@extends('LayUI.index')
@section('content')
    <div class="layui-fluid">
        <div class="layui-row">
            <div class="layui-col-md6">
                <div class="layui-card">
                    <div class="layui-card-header"><h2>新建角色</h2></div>
                    <div class="layui-card-body">
                        <form class="layui-form">
                            <div class="layui-form-item">
                                <label class="layui-form-label">名称</label>
                                <div class="layui-input-block">
                                    <input type="text" name="name" required lay-verify="required" placeholder="必填，唯一" autocomplete="off" class="layui-input">
                                </div>
                                <div class="layui-form-item">
                                    <button type="submit" id="btnStore" class="layui-btn layui-btn-sm"><i class="fa fa-check">&nbsp;</i>新建</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        layui.use(function () {
            let {jquery: $, layer, form,} = layui;

        });
    </script>
@endsection