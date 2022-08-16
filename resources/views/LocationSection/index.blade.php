@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            区间管理
            <small>列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li class="active">区间-列表</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">区间-列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="{{ route('web.LocationSection:Create') }}" class="btn btn-success"><i class="fa fa-plus"></i></a>
                </div>
                <hr>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-condensed" id="tblLocationSection">
                        <thead>
                        <tr>
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
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let tblLocationSection = null;

        /**
         * 加载区间表格
         */
        function fnFillTblLocationSection() {
            if (document.getElementById('tblLocationSection')) {
                tblLocationSection = $('#tblLocationSection').DataTable({
                    ajax: {
                        url: `{{ route("web.LocationSection:Index") }}?{!! http_build_query(request()->all()) !!}`,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.LocationSection:Index") }}?{!! http_build_query(request()->all()) !!} success:`, res);
                            let {location_sections: locationSections,} = res["data"];
                            let render = [];
                            if (locationSections.length > 0) {
                                $.each(locationSections, (_, locationSection) => {
                                    let uuid = locationSection["uuid"];
                                    let createdAt = locationSection["created_at"] ? moment(locationSection["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uniqueCode = locationSection["unique_code"] ? locationSection["unique_code"] : "";
                                    let name = locationSection["name"] ? locationSection["name"] : "";
                                    let beEnable = locationSection["be_enable"] ? locationSection["be_enable"] : "";
                                    let organizationWorkshopName = locationSection["organization_workshop"] ? locationSection["organization_workshop"]["name"] : "";
                                    let organizationWorkAreaName = locationSection["organization_work_area"] ? locationSection["organization_work_area"]["name"] : "";
                                    let locationLines = locationSection["location_lines"] ? locationSection["location_lines"] : [];
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
                                    divBtnGroup += `<a href="{{ route("web.LocationSection:Index") }}/${uuid}/edit" class="btn btn-warning"><i class="fa fa-edit"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDelete('${uuid}')"><i class="fa fa-trash"></i></a>`;
                                    divBtnGroup += `</div>`;
                                    divBtnGroup += `</td>`;

                                    render.push([
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
                            console.log(`{{ route("web.LocationSection:Index") }}?{!! http_build_query(request()->all()) !!} fail:`, err);
                            layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                                if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                            });
                        }
                    },
                    columnDefs: [{
                        orderable: false,
                        targets: 7,  // 清除第一列排序
                    }],
                    paging: true,  // 分页器
                    lengthChange: true,
                    searching: true,  // 搜索框
                    ordering: true,  // 列排序
                    info: true,
                    autoWidth: true,  // 自动宽度
                    order: [[0, 'desc']],  // 排序依据
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
                        paginate: {sFirst: " 首页", sLast: "末页 ", sPrevious: " 上一页 ", sNext: " 下一页"}
                    }
                });
            }
        }

        $(function () {
            if ($select2.length > 0) $select2.select2();
        });

        /**
         * 删除
         * @param id 编号
         */
        function fnDelete(id) {
            if (confirm('删除不能恢复，是否确认'))
                $.ajax({
                    url: `{{ url('locationSection') }}/${id}`,
                    type: 'delete',
                    data: {id: id},
                    success: function (res) {
                        console.log(`{{ url('locationSection')}}/${id} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('locationSection')}}/${id} fail:`, err);
                        layer.close(loading);
                        layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                        });
                    }
                });
        }
    </script>
@endsection