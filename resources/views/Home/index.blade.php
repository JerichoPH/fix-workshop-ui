{{--@dd("hello FixWorkshopUI")--}}
@extends('LayUI.index')
@section('content')
    <div class="layui-fluid">
        常规布局（以中型屏幕桌面为例）：
        <div class="layui-row">
            <div class="layui-col-md9">
                <fieldset class="layui-elem-field layui-field-title">
                    <legend>字段集区块 - 横线风格</legend>
                    <div class="layui-field-box">
                        你的内容 9/12
                        <form class="layui-form" action="">
                            <div class="layui-form-item">
                                <label class="layui-form-label">输入框</label>
                                <div class="layui-input-block">
                                    <input type="text" name="title" required lay-verify="required" placeholder="请输入标题" autocomplete="off" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                    <button type="button" id="btnTest" class="layui-btn" lay-submit lay-filter="btnTest">test</button>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">单选框</label>
                                <div class="layui-input-block">
                                    <input type="radio" name="sex" value="男" title="男">
                                    <input type="radio" name="sex" value="女" title="女" checked>
                                </div>
                            </div>
                        </form>
                        <form class="layui-form">
                            <div class="layui-form-item">
                                <label class="layui-form-label">密码框</label>
                                <div class="layui-input-inline">
                                    <input type="password" name="password" placeholder="请输入密码" autocomplete="off" class="layui-input">
                                </div>
                                <button class="layui-btn layui-btn-primary" lay-submit lay-filter="btn2"><i class="layui-icon layui-icon-ok">&nbsp;</i>aaa</button>
                            </div>
                        </form>
                        <table id="tblAccount" lay-filter="tblAccount"></table>
                    </div>
                </fieldset>
            </div>
            <div class="layui-col-md3">
                你的内容 3/12
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        layui.use(function () {
            let {jquery: $, layer, form, table,} = layui;
            form.on('submit(btnTest)', function (data) {
                console.log(data);
                return false;
            });

            form.on('submit(btn2)', function (data) {
                layer.msg(data.field.password);
                return false;
            });

            table.render({
                elem: '#tblAccount',
                // height: 312,
                url: '{{ route('web.Account:Index') }}', //数据接口
                where: {be_enable: 1,},
                page: true, //开启分页
                parseData: function (res) {
                    return {
                        code: res['errorCode'],
                        msg: res['msg'],
                        count: res['data']['accounts'].length,
                        data: res['data']['accounts'],
                    };
                },
                cols: [[ //表头,
                    {type: 'number', title: '行号',},
                    {field: 'id', title: 'ID', width: 80, sort: true, fixed: 'left'},
                    {field: 'username', title: '账号', width: 236},
                    // {
                    //     field: 'name', title: '名称', sort: true, fixed: 'left', templet: function (row) {
                    //         console.log('ok',row);
                    //         return `<span style="color: #FF0000;">${row['unique_code']}</span>`;
                    //     }
                    // },
                ]],
            });

        });
    </script>
@endsection