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
                    <a href="{{ route('web.LocationRailroadGradeCross:Create') }}" class="btn btn-success"><i class="fa fa-plus"></i></a>
                </div>
                <hr>
            </div>
            <div class="box-body">
                <table class="table table-hover table-striped table-condensed" id="tblLocationRailroadGradeCross">
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
        let tblLocationRailroadGradeCross = null;

        /**
         * 加载道口表格
         */
        function fnFillTblLocationRailroadGradeCross() {
            if (document.getElementById('tblLocationRailroadGradeCross')) {
                tblLocationRailroadGradeCross = $('#tblLocationRailroadGradeCross').DataTable({
                    ajax: {
                        url: `{{ route("web.LocationRailroadGradeCross:Index") }}?{!! http_build_query(request()->all()) !!}`,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.LocationRailroadGradeCross:Index") }}?{!! http_build_query(request()->all()) !!} success:`, res);
                            let {location_railroad_grade_crosses: locationRailroadGradeCrosses,} = res["content"];
                            let render = [];
                            if (locationRailroadGradeCrosses.length > 0) {
                                $.each(locationRailroadGradeCrosses, (_, locationRailroadGradeCross) => {
                                    let uuid = locationRailroadGradeCross["uuid"];
                                    let createdAt = locationRailroadGradeCross["created_at"] ? moment(locationRailroadGradeCross["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uniqueCode = locationRailroadGradeCross["unique_code"] ? locationRailroadGradeCross["unique_code"] : "";
                                    let name = locationRailroadGradeCross["name"] ? locationRailroadGradeCross["name"] : "";
                                    let beEnable = locationRailroadGradeCross["be_enable"] ? locationRailroadGradeCross["be_enable"] : "";
                                    let organizationWorkshopName = locationRailroadGradeCross["organization_workshop"] ? locationRailroadGradeCross["organization_workshop"]["name"] : "";
                                    let organizationWorkAreaName = locationRailroadGradeCross["organization_work_area"] ? locationRailroadGradeCross["organization_work_area"]["name"] : "";
                                    let locationLines = locationRailroadGradeCross["location_lines"] ? locationRailroadGradeCross["location_lines"] : [];
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
                                    divBtnGroup += `<a href="{{ route("web.LocationRailroadGradeCross:Index") }}/${uuid}/edit" class="btn btn-warning"><i class="fa fa-edit"></i></a>`;
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
                            console.log(`{{ route("web.LocationRailroadGradeCross:Index") }}?{!! http_build_query(request()->all()) !!} fail:`, err);
                            layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                                if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
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

                tblLocationRailroadGradeCross.on('draw.dt order.dt search.dt', function () {
                    tblLocationRailroadGradeCross.column(0, {search: 'applied', order: 'applied'}).nodes().each(function (cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }).draw();
            }
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();

            fnFillTblLocationRailroadGradeCross();  // 加载区间列表
        });

        /**
         * 删除
         * @param id 编号
         */
        function fnDelete(id) {
            if (confirm('删除不能恢复，是否确认'))
                $.ajax({
                    url: `{{ url('locationRailroadGradeCross') }}/${id}`,
                    type: 'delete',
                    data: {id: id},
                    success: function (res) {
                        console.log(`{{ url('locationRailroadGradeCross')}}/${id} success:`, res);

                        tblLocationRailroadGradeCross.ajax.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('locationRailroadGradeCross')}}/${id} fail:`, err);
                        layer.close(loading);
                        layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                        });
                    }
                });
        }
    </script>
@endsection