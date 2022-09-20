@extends('LayUI.index')
@section('content')
    <div class="layui-fluid">
        <div class="layui-row">
            <div class="layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-header"><h2>角色列表</h2></div>
                    <div class="layui-card-body">
                        <table id="tblRbacRole" lay-filter="tblRbacRole"></table>
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
                elem: '#tblRbacRole',
                skin: 'line',
                even: true,
                size: 'lg',
                height: 'full-150',
                url: '{{ route('web.RbacRole:Index') }}',
                where: {},
                page: true,
                parseData: function (res) {
                    return {
                        code: res['errorCode'],
                        msg: res['msg'],
                        count: res['data']['rbac_roles'].length,
                        data: res['data']['rbac_roles'],
                    };
                },
                cols: [[ //表头,
                    {type: 'number', title: '行号',},
                    {
                        field: 'created_at', title: '创建时间', sort: true, templet: function (datum) {
                            return `<div>${datum['created_at'] ? moment(datum['created_at']).format('YYYY-MM-DD HH:mm:ss') : ''}</div>`;
                        }
                    },
                    {field: 'name', title: '角色名称', sort: true,},
                    {
                        title: '', templet: function (datum) {
                            return `
<div class="layui-btn-group">
  <a href="{{ route("web.RbacRole:Index") }}/${datum["uuid"]}/edit" class="layui-btn layui-btn-warm layui-btn-sm">
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