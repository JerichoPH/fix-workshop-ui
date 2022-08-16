@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            车间管理
            <small>列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li class="active">车间-列表</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">车间-列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="{{ route('web.OrganizationWorkshop:Create') }}" class="btn btn-success"><i class="fa fa-plus"></i></a>
                </div>
                <hr>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-condensed" id="tblOrganizationWorkshop">
                        <thead>
                        <tr>
                            <th>创建时间</th>
                            <th>代码</th>
                            <th>名称</th>
                            <th>所属站段</th>
                            <th>车间类型</th>
                            <th>是否启用</th>
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
        let tblOrganizationWorkshop = null;

        /**
         * 加载车间表格
         */
        function fnFillTblOrganizationWorkshop() {
            if (document.getElementById('tblOrganizationWorkshop')) {
                tblOrganizationWorkshop = $('#tblOrganizationWorkshop').DataTable({
                    ajax: {
                        url: `{{ route("web.OrganizationWorkshop:Index") }}?{!! http_build_query(request()->all()) !!}`,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.OrganizationWorkshop:Index") }}?{!! http_build_query(request()->all()) !!} success:`, res);
                            let {organization_workshops: organizationWorkshops,} = res["data"];
                            let render = [];
                            if (organizationWorkshops.length > 0) {
                                $.each(organizationWorkshops, (_, organizationWorkshop) => {
                                    let uuid = organizationWorkshop["uuid"];
                                    let createdAt = organizationWorkshop["created_at"] ? moment(organizationWorkshop["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uniqueCode = organizationWorkshop["unique_code"] ? organizationWorkshop["unique_code"] : "";
                                    let name = organizationWorkshop["name"] ? organizationWorkshop["name"] : "";
                                    let organizationRailwayName = organizationWorkshop["organization_railway"] ? organizationWorkshop["organization_railway"]["name"] : "";
                                    let organizationWorkshopTypeName = organizationWorkshop["organization_workshop_type"] ? organizationWorkshop["organization_workshop_type"]["name"] : "";
                                    let beEnable = organizationWorkshop["be_enable"] ? "是" : "否";
                                    let divBtnGroup = '';
                                    divBtnGroup += `<td class="">`;
                                    divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                    divBtnGroup += `<a href="{{ route("web.OrganizationWorkshop:Index") }}/${uuid}/edit" class="btn btn-warning"><i class="fa fa-edit"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDelete('${uuid}')"><i class="fa fa-trash"></i></a>`;
                                    divBtnGroup += `</div>`;
                                    divBtnGroup += `</td>`;

                                    render.push([
                                        createdAt,
                                        uniqueCode,
                                        name,
                                        organizationRailwayName,
                                        organizationWorkshopTypeName,
                                        beEnable,
                                        divBtnGroup,
                                    ]);
                                });
                            }
                            return render;
                        },
                        error: function (err) {
                            console.log(`{{ route("web.OrganizationWorkshop:Index") }}?{!! http_build_query(request()->all()) !!} fail:`, err);
                            layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                                if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                            });
                        }
                    },
                    columnDefs: [{
                        orderable: false,
                        targets: 5,  // 清除第一列排序
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

            fnFillTblOrganizationWorkshop(); // 加载车间表格
        });

        /**
         * 删除
         * @param id 编号
         */
        function fnDelete(id) {
            if (confirm('删除不能恢复，是否确认'))
                $.ajax({
                    url: `{{ url('organizationWorkshop') }}/${id}`,
                    type: 'delete',
                    data: {id: id},
                    success: function (res) {
                        console.log(`{{ url('organizationWorkshop')}}/${id} success:`, res);
                        location.reload();
                    },
                    error: function (err) {
                        console.log(`{{ url('organizationWorkshop')}}/${id} fail:`, err);
                        layer.close(loading);
                        layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                        });
                    }
                });
        }
    </script>
@endsection