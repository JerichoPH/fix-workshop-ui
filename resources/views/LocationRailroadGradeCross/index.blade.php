@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            道口管理
            <small>列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li class="active">道口-列表</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">道口-列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="{{ route('web.locationRailroad:create') }}" class="btn btn-success"><i class="fa fa-plus"></i></a>
                </div>
                <hr>
            </div>
            <div class="box-body">
                <table class="table table-hover table-striped table-condensed" id="tbllocationRailroad">
                    <thead>
                    <tr>
                        <th>行号</th>
                        <th>新建时间</th>
                        <th>代码</th>
                        <th>名称</th>
                        <th>是否启用</th>
                        <th>所属车间</th>
                        <th>所属工区</th>
                        <th>所属线别</th>
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
        let tbllocationRailroad = null;

        /**
         * 加载道口表格
         */
        function fnFillTbllocationRailroad() {
            if (document.getElementById('tbllocationRailroad')) {
                tbllocationRailroad = $('#tbllocationRailroad').DataTable({
                    ajax: {
                        url: `{{ route("web.locationRailroad:index") }}?{!! http_build_query(request()->all()) !!}`,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.locationRailroad:index") }}?{!! http_build_query(request()->all()) !!} success:`, res);
                            let {location_railroades: locationRailroades,} = res["content"];
                            let render = [];
                            if (locationRailroades.length > 0) {
                                $.each(locationRailroades, (_, locationRailroad) => {
                                    let uuid = locationRailroad["uuid"];
                                    let createdAt = locationRailroad["created_at"] ? moment(locationRailroad["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uniqueCode = locationRailroad["unique_code"] ? locationRailroad["unique_code"] : "";
                                    let name = locationRailroad["name"] ? locationRailroad["name"] : "";
                                    let beEnable = locationRailroad["be_enable"] ? locationRailroad["be_enable"] : "";
                                    let organizationWorkshopName = locationRailroad["organization_workshop"] ? locationRailroad["organization_workshop"]["name"] : "";
                                    let organizationWorkAreaName = locationRailroad["organization_work_area"] ? locationRailroad["organization_work_area"]["name"] : "";
                                    let locationLines = locationRailroad["location_lines"] ? locationRailroad["location_lines"] : [];
                                    let locationLineNames = [];
                                    if (locationLines.length > 0) {
                                        locationLines.map(function (locationLine) {
                                            if (locationLine["name"]) {
                                                locationLineNames.push(locationLine["name"]);
                                            }
                                        });
                                    }
                                    let divBtnGroup = '';
                                    divBtnGroup += `<td class="">`;
                                    divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                    divBtnGroup += `<a href="{{ route("web.locationRailroad:index") }}/${uuid}/edit" class="btn btn-warning"><i class="fa fa-edit"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDelete('${uuid}')"><i class="fa fa-trash"></i></a>`;
                                    divBtnGroup += `</div>`;
                                    divBtnGroup += `</td>`;

                                    render.push([
                                        null,
                                        createdAt,
                                        uniqueCode,
                                        name,
                                        beEnable ? "是" : "否",
                                        organizationWorkshopName,
                                        organizationWorkAreaName,
                                        locationLineNames.join("、"),
                                        divBtnGroup,
                                    ]);
                                });
                            }
                            return render;
                        },
                        error: function (err) {
                            console.log(`{{ route("web.locationRailroad:index") }}?{!! http_build_query(request()->all()) !!} fail:`, err);
                            layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                                if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                            });
                        }
                    },
                    columnDefs: [{
                        orderable: false,
                        targets: [0, 7,],  // 清除第一列排序
                    }],
                    processing: true,
                    paging: true,  // 分页器
                    lengthChange: true,
                    searching: false,  // 搜索框
                    ordering: true,  // 列排序
                    info: true,
                    autoWidth: false,  // 自动宽度
                    order: [[1, 'desc']],  // 排序依据
                    iDisplayLength: 50,  // 默认分页数
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

                tbllocationRailroad.on('draw.dt order.dt search.dt', function () {
                    tbllocationRailroad.column(0, {search: 'applied', order: 'applied'}).nodes().each(function (cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }).draw();
            }
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            fnFillTbllocationRailroad();  // 加载区间列表
        });

        /**
         * 删除
         * @param id 编号
         */
        function fnDelete(id) {
            if (confirm('删除不能恢复，是否确认'))
                $.ajax({
                    url: `{{ url('locationRailroad') }}/${id}`,
                    type: 'delete',
                    data: {id: id},
                    success: function (res) {
                        console.log(`{{ url('locationRailroad')}}/${id} success:`, res);

                        tbllocationRailroad.ajax.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('locationRailroad')}}/${id} fail:`, err);
                        layer.close(loading);
                        layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:getLogin') }}';
                        });
                    }
                });
        }
    </script>
@endsection