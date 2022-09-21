@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            仓库位置管理
            <small>列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li class="active">仓库位置-列表</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">仓库位置-列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="{{ route('web.PositionDepotStorehouse:Create') }}" class="btn btn-success"><i class="fa fa-plus"></i></a>
                </div>
                <hr>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-2">
                        <h4>仓库</h4>
                        <div class="pull-right"><a href="javascript:" class="btn btn-success btn-sm"><i class="fa fa-plus"></i></a></div>
                        <table class="table table-hover table-striped table-condensed" id="tblPositionDepotStorehouse">
                            <thead>
                            <tr>
                                <th>行号</th>
                                <th>创建时间</th>
                                <th>名称</th>
                                <th>代码</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="col-md-2">

                    </div>
                    <div class="col-md-2">

                    </div>
                    <div class="col-md-2">

                    </div>
                    <div class="col-md-2">

                    </div>
                    <div class="col-md-2">

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let tblPositionDepotStorehouse = null;

        /**
         * 获取仓库表格
         */
        function fnFillPositionDepotStorehouse (){
            if (document.getElementById('tblPositionDepotStorehouse')) {
                tblPositionDepotStorehouse = $('#tblPositionDepotStorehouse').DataTable({
                    ajax: {
                        url: `{{ route("web.PositionDepotStorehouse:Index") }}?{!! http_build_query(request()->all()) !!}`,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.PositionDepotStorehouse:Index") }}?{!! http_build_query(request()->all()) !!} success:`, res);
                            let {position_depot_storehouses: positionDepotStorehouses,} = res["content"];
                            let render = [];
                            if (positionDepotStorehouses.length > 0) {
                                $.each(positionDepotStorehouses, (_, positionDepotStorehouse) => {
                                    let uuid = positionDepotStorehouse["uuid"];
                                    let createdAt = positionDepotStorehouse["created_at"] ? moment(positionDepotStorehouse["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uniqueCode = positionDepotStorehouse["unique_code"] ? positionDepotStorehouse["unique_code"] : "";
                                    let name = positionDepotStorehouse["name"] ? positionDepotStorehouse["name"] : "";
                                    let divBtnGroup = '';
                                    divBtnGroup += `<td class="">`;
                                    divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-warning" onclick=fnModalEditPositionDepotStorehouse('${uuid}')><i class="fa fa-edit"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDeletePositionDepotStorehouse('${uuid}')"><i class="fa fa-trash"></i></a>`;
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
                            console.log(`{{ route("web.PositionDepotStorehouse:Index") }}?{!! http_build_query(request()->all()) !!} fail:`, err);
                            layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                                if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                            });
                        }
                    },
                    columnDefs: [{
                        orderable: false,
                        targets: [0,],  // 清除第一列排序
                    }],
                    paging: true,  // 分页器
                    lengthChange: true,
                    searching: false,  // 搜索框
                    ordering: true,  // 列排序
                    info: true,
                    autoWidth: true,  // 自动宽度
                    order: [[1, 'desc']],  // 排序依据
                    iDisplayLength: 50,  // 默认分页数
                    aLengthMenu: [50, 100, 200],  // 分页下拉框选项
                    language: {
                        sInfoFiltered: "从_MAX_中过滤",
                        sProcessing: "正在加载中...",
                        info: "第 _START_ - _END_ 条记录，共 _TOTAL_ 条",
                        sLengthMenu: "每页显示_MENU_条记录",
                        zeroRecords: "没有符合条件的记录",
                        infoEmpty: " ",
                        emptyTable: "没有符合条件的记录",
                        search: "筛选：",
                        paginate: {sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"},
                    },
                });

                tblPositionDepotStorehouse.on('draw.dt order.dt search.dt', function () {
                    tblPositionDepotStorehouse.column(0, {search: 'applied', order: 'applied'}).nodes().each(function (cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }).draw();
            }
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            fnFillPositionDepotStorehouse();  // 获取仓库表格
        });

        /**
         * 删除
         * @param uuid 编号
         */
        function fnDeletePositionDeportStorehouse(uuid) {
            if (confirm('删除不能恢复，是否确认'))
                $.ajax({
                    url: `{{ url('positionDepotStorehouse') }}/${uuid}`,
                    type: 'delete',
                    data: {id: uuid},
                    success: function (res) {
                        console.log(`{{ url('positionDepotStorehouse')}}/${uuid} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('positionDepotStorehouse')}}/${uuid} fail:`, err);
                        layer.close(loading);
                        layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                        });
                    }
                });
        }
    </script>
@endsection