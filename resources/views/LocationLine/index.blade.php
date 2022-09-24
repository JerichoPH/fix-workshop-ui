@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            线别管理
            <small>列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li class="active">线别-列表</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">线别-列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="{{ route('web.LocationLine:Create') }}" class="btn btn-success"><i class="fa fa-plus"></i></a>
                </div>
                <hr>
            </div>
            <div class="box-body">
                <table class="table table-hover table-striped table-condensed" id="tblLine">
                    <thead>
                    <tr>
                        <th>行号</th>
                        <th>新建时间</th>
                        <th>代码</th>
                        <th>名称</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let tblLine = null;
        if (document.getElementById('tblLine')) {
            tblLine = $('#tblLine').DataTable({
                ajax: {
                    url: `{{ route("web.LocationLine:Index") }}`,
                    dataSrc: function (res) {
                        console.log(`{{ route("web.LocationLine:Index") }} success:`, res);
                        let {location_lines: locationLines,} = res["content"];
                        let render = [];
                        if (locationLines.length > 0) {
                            $.each(locationLines, (_, locationLine) => {
                                let uuid = locationLine["uuid"];
                                let createdAt = locationLine["created_at"] ? moment(locationLine["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                let uniqueCode = locationLine["unique_code"];
                                let name = locationLine["name"];
                                let divBtnGroup = '';
                                divBtnGroup += `<td class="">`;
                                divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                divBtnGroup += `<a href="{{ route("web.LocationLine:Index") }}/${uuid}/edit" class="btn btn-warning"><i class="fa fa-edit"></i></a>`;
                                divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDelete('${uuid}')"><i class="fa fa-trash"></i></a>`;
                                divBtnGroup += `</div>`;
                                divBtnGroup += `</td>`;

                                render.push([
                                    null,
                                    createdAt,
                                    uniqueCode,
                                    name,
                                    divBtnGroup,
                                ]);
                            });
                        }
                        return render;
                    },
                    error: function (err) {
                        console.log(`{{ route("web.LocationLine:Index") }} fail:`, err);
                        if (err["status"] === 406) {
                            layer.alert(err["responseJSON"]["msg"], {icon: 2,});
                        } else {
                            layer.msg(err["responseJSON"]["msg"], {time: 1500,}, function () {
                                if (err["status"] === 401) location.href = `{{ route("web.Authorization:GetLogin") }}`;
                            });
                        }
                    },
                },
                columnDefs: [{
                    orderable: false,
                    targets: [0,4,],
                }],
                processing: true,
                paging: true,  // 分页器
                lengthChange: true,
                searching: false,  // 搜索框
                ordering: true,  // 列排序
                info: true,
                autoWidth: false,  // 自动宽度
                order: [[1, 'desc']],  // 排序依据
                iDisplayLength: 200,  // 默认分页数
                aLengthMenu: [50, 100, 200],  // 分页下拉框选项
                language: {
                    sInfoFiltered: "从_MAX_中过滤",
                    sProcessing: "数据加载中...",
                    info: "第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                    sLengthMenu: "每页显示_MENU_条记录",
                    zeroRecords: "没有符合条件的记录",
                    infoEmpty: " ",
                    emptyTable: "没有符合条件的记录",
                    search: "筛选：",
                    paginate: {sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"}
                }
            });
        }
        $(function () {
            if ($select2.length > 0) $('.select2').select2();
        });

        /**
         * 删除
         * @param id 编号
         */
        function fnDelete(id) {
            if (confirm('删除不能恢复，是否确认')) {
                let loading = layer.msg('处理中……', {time: 0,});
                $.ajax({
                    url: `{{ url('locationLine') }}/${id}`,
                    type: 'delete',
                    data: {id: id},
                    success: function (res) {
                        console.log(`{{ url('locationLine')}}/${id} success:`, res);
                        layer.close(loading);
                        layer.msg(res['msg'], {time: 500,}, function () {
                            tblLine.ajax.reload();
                        });
                    },
                    error: function (err) {
                        console.log(`{{ url('locationLine')}}/${id} fail:`, err);
                        layer.close(loading);
                        layer.msg(err["responseJSON"]["msg"], {time: 1500,}, () => {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                        });
                    }
                });
            }
        }
    </script>
@endsection