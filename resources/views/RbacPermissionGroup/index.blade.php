@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            权限分组管理
            <small>列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li class="active">权限分组-列表</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">权限分组-列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="{{ route('web.RbacPermissionGroup:Create', ['page' => request('page', 1), ]) }}" class="btn btn-flat btn-success"><i class="fa fa-plus"></i></a>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-condensed" id="tblRbacPermissionGroup">
                        <thead>
                        <tr>
                            <th>创建时间</th>
                            <th>编号</th>
                            <th>名称</th>
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
        let tblRbacPermissionGroup = null;
        if (document.getElementById('tblRbacPermissionGroup')) {
            tblRbacPermissionGroup = $('#tblRbacPermissionGroup').DataTable({
                ajax: {
                    url: `{{ route("web.RbacPermissionGroup:Index") }}`,
                    dataSrc: function (res) {
                        console.log(`{{ route("web.RbacPermissionGroup:Index") }} success:`, res);
                        let {rbac_permission_groups: rbacPermissionGroups,} = res['data'];
                        let render = [];
                        if (rbacPermissionGroups.length > 0) {
                            $.each(rbacPermissionGroups, (key, rbacPermissionGroup) => {
                                let createdAt = rbacPermissionGroup["created_at"] ? moment(rbacPermissionGroup["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                let uuid = rbacPermissionGroup["uuid"];
                                let name = rbacPermissionGroup["name"];
                                let divBtnGroup = '';
                                divBtnGroup += `<td class="">`;
                                divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                divBtnGroup += `<a href="{{ url("rbacPermissionGroup") }}/${uuid}" class="btn btn-warning btn-flat"><i class="fa fa-edit"></i></a>`;
                                divBtnGroup += `<a href="javascript:" class="btn btn-danger btn-flat" onclick="fnDelete('${uuid}')"><i class="fa fa-trash"></i></a>`;
                                divBtnGroup += `</div>`;
                                divBtnGroup += `</td>`;

                                render.push([
                                    createdAt,
                                    uuid,
                                    name,
                                    divBtnGroup,
                                ]);
                            });
                        }
                        return render;
                    },
                },
                // columnDefs: [{
                //     orderable: false,
                //     targets: 0,  // 清除第一列排序
                // }],
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

        $(function () {
            if ($select2.length > 0) $('.select2').select2();
        });
    </script>
@endsection