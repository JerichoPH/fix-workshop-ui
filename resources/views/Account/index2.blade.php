@extends('LayUI.index')
@section('content')
    <div class="layui-fluid">
        <div class="layui-row">
            <div class="layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-header"><h2>用户列表</h2></div>
                    <div class="layui-card-body">
                        <table id="tblAccount" lay-filter="tblAccount"></table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        layui.use(function () {
            let {jquery: $, layer, form, table,} = layui;
            table.render({
                ...tableBaseOptions,
                elem: '#tblAccount',
                url: '{{ route('web.Account:index') }}',
                where: {__order__: 'created_at desc',},
                toolbar: `<div class="layui-btn-group"><a href="{{ route("web.Account:create") }}" class="layui-btn layui-btn-sm"><i class="fa fa-plus"></i></a></div>`,
                parseData: function (res) {
                    return tableBaseParseData(res, 'accounts');
                },
                cols: [[ //表头,
                    ...tableBaseColumns,
                    {
                        field: 'created_at', title: '创建时间', sort: true, templet: function (datum) {
                            return `<div>${datum['created_at'] ? moment(datum['created_at']).format('YYYY-MM-DD HH:mm:ss') : ''}</div>`;
                        }
                    },
                    {field: 'username', title: '账号', sort: true,},
                    {field: 'nickname', title: '昵称', sort: true,},
                    {
                        title: '', templet: function (datum) {
                            return `
<div class="layui-btn-group">
  <a href="{{ route("web.Account:index") }}/${datum["uuid"]}/edit" class="layui-btn layui-btn-warm layui-btn-sm">
    <i class="fa fa-edit"></i>
  </a>
  <button type="button" class="layui-btn layui-btn-danger layui-btn-sm" onclick="fnDelete(${datum["uuid"]})">
    <i class="fa fa-trash"></i>
  </button>
</div>
`;
                        }
                    },
                ]],
            });
        });
    </script>
@endsection