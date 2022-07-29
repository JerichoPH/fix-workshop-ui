@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            角色管理
            <small>列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li class="active">角色-列表</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">角色-列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="{{ route('web.RbacRole:Create', []) }}" class="btn btn-success"><i class="fa fa-plus"></i></a>
                </div>
            </div>
            <div class="box-body">
                <table class="table table-hover table-striped table-condensed" id="tblRbacRole">
                    <thead>
                    <tr>
                        <th>新建时间</th>
                        <th>编号</th>
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
        let tblRbacRole = null;

        /**
         * 填充角色表
         */
        function fnFillTblRbacRole() {
            if (document.getElementById('tblRbacRole')) {
                tblRbacRole = $('#tblRbacRole').DataTable({
                    ajax: {
                        url: `{{ route("web.RbacRole:Index") }}`,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.RbacRole:Index") }} success:`, res);
                            let {rbac_roles: rbacRoles,} = res['data'];
                            let render = [];
                            if (rbacRoles.length > 0) {
                                $.each(rbacRoles, (key, rbacRole) => {
                                    let createdAt = rbacRole["created_at"] ? moment(rbacRole["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uuid = rbacRole["uuid"];
                                    let name = rbacRole["name"];
                                    let divBtnGroup = '';
                                    divBtnGroup += `<td class="">`;
                                    divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                    divBtnGroup += `<a href="{{ url("rbacRole") }}/${uuid}/bind" class="btn btn-primary"><i class="fa fa-link">&nbsp;</i></a>`;
                                    divBtnGroup += `<a href="{{ url("rbacRole") }}/${uuid}/edit" class="btn btn-warning"><i class="fa fa-edit"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDelete('${uuid}')"><i class="fa fa-trash"></i></a>`;
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
        }

        $(function () {
            if ($select2.length > 0) $('.select2').select2();

            fnFillTblRbacRole();  // 填充角色表
        });

        /**
         * 删除
         * @param {string} uuid
         */
        function fnDelete(uuid = "") {
            if (uuid && confirm("删除不可恢复，是否确定？")) {
                let loading = layer.msg('处理中……', {time: 0,});
                $.ajax({
                    url: `{{ url("rbacRole") }}/${uuid}`,
                    type: 'delete',
                    data: {},
                    async: true,
                    success: res => {
                        console.log(`{{ url("rbacRole") }}/${uuid} success:`, res);
                        layer.close(loading);
                        layer.msg(res['msg'], {time: 1000,}, function () {
                            tblRbacRole.ajax.reload();
                        });
                    },
                    error: err => {
                        console.log(`{{ url("rbacRole") }}/${uuid} fail:`, err);
                        layer.close(loading);
                        layer.msg(err["responseJSON"], {time: 1500,}, () => {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                        });
                    },
                });
            }
        }
    </script>
@endsection