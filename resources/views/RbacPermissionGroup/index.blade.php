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
                    <a href="{{ route('web.RbacPermissionGroup:Create', []) }}" class="btn btn-success"><i class="fa fa-plus"></i></a>
                </div>
                <hr>
            </div>
            <div class="box-body">
                <table class="table table-hover table-striped table-condensed" id="tblRbacPermissionGroup">
                    <thead>
                    <tr>
                        <th>创建时间</th>
                        <th>编号</th>
                        <th>名称</th>
                        <th>相关权限数量</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="modal fade" id="modalStoreResourceRbacPermissions">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">批量添加资源权限</h4>
                    </div>
                    <div class="modal-body form-horizontal">
                        <form id="frmStoreResourceRbacPermissions">
                            <input type="hidden" name="rbac_permission_group_uuid" id="hdnRbacPermissionGroupUUID">
                            <div class="form-group">
                                <label class="col-sm-3 col-md-3 control-label">URI：</label>
                                <div class="col-sm-9 col-md-8">
                                    <input type="text" class="form-control" name="uri" id="txtUri_frmStoreResourceRbacPermissions" value="" autocomplete="off">
                                </div>
                            </div>

                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default pull-left btn-sm" data-dismiss="modal"><i class="fa fa-times">&nbsp;</i>关闭</button>
                        <button type="button" class="btn btn-success btn-sm" onclick="fnStoreResourceRbacPermissions()"><i class="fa fa-check">&nbsp;</i>确定</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        let $select2 = $('.select2');
        let tblRbacPermissionGroup = null;
        let $modalStoreResourceRbacPermissions = $("#modalStoreResourceRbacPermissions");
        let $frmStoreResourceRbacPermissions = $("#frmStoreResourceRbacPermissions");
        let $hdnRbacPermissionGroupUUID = $("#hdnRbacPermissionGroupUUID");
        let $txtUri_frmStoreResourceRbacPermissions = $("#txtUri_frmStoreResourceRbacPermissions");

        /**
         * 填充权限分组表
         */
        function fnFillTblRbacPermissionGroup() {
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
                                    console.log(rbacPermissionGroup);
                                    let createdAt = rbacPermissionGroup["created_at"] ? moment(rbacPermissionGroup["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uuid = rbacPermissionGroup["uuid"];
                                    let name = rbacPermissionGroup["name"];
                                    let rbacPermissionCount = rbacPermissionGroup["rbac_permissions"] ? `${rbacPermissionGroup["rbac_permissions"].length}个` : `0个`;
                                    let divBtnGroup = '';
                                    divBtnGroup += `<td class="">`;
                                    divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                    divBtnGroup += `<a href="{{ route("web.RbacPermission:Index") }}?rbac_permission_group_uuid=${uuid}" class="btn btn-default"><i class="fa fa-eye"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-success" onclick="fnToCreatePermission('${uuid}')"><i class="fa fa-plus">&nbsp;</i>添加单个权限</a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-success" onclick="modalStoreResourcesRbacPermissions('${uuid}')"><i class="fa fa-archive">&nbsp;</i>批量添加资源权限</a>`;
                                    divBtnGroup += `<a href="{{ url("rbacPermissionGroup") }}/${uuid}/edit" class="btn btn-warning"><i class="fa fa-edit"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDelete('${uuid}')"><i class="fa fa-trash"></i></a>`;
                                    divBtnGroup += `</div>`;
                                    divBtnGroup += `</td>`;

                                    render.push([
                                        createdAt,
                                        uuid,
                                        name,
                                        rbacPermissionCount,
                                        divBtnGroup,
                                    ]);
                                });
                            }
                            return render;
                        },
                        error: function (err) {
                            console.log(`{{ route("web.RbacPermissionGroup:Index") }} fail:`, err);
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
                        targets: 4,
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
            if ($select2.length > 0) $('.select2').select2();

            fnFillTblRbacPermissionGroup();  // 填充权限分组表
        });

        /**
         * 跳转到创建权限页面
         */
        function fnToCreatePermission(uuid = "") {
            if (uuid) {
                location.href = `{{ route("web.RbacPermission:Create") }}?rbac_permission_group_uuid=${uuid}`
            }
        }

        /**
         * 打开批量添加资源权限模态框
         */
        function modalStoreResourcesRbacPermissions(uuid = "") {
            if (uuid) {
                $hdnRbacPermissionGroupUUID.val(uuid);
                $modalStoreResourceRbacPermissions.modal("show");
            }
        }

        /**
         * 批量添加资源权限
         */
        function fnStoreResourceRbacPermissions() {
            let data = $frmStoreResourceRbacPermissions.serializeArray();
            let loading = layer.msg("处理中……", {time: 0,});
            let rbacPermissionGroupUUID = $hdnRbacPermissionGroupUUID.val();

            $.ajax({
                url: `{{ route("web.RbacPermission:PostResource") }}`,
                type: 'post',
                data,
                async: true,
                success: function (res) {
                    console.log(`{{ route("web.RbacPermission:PostResource") }} success:`, res);
                    layer.close(loading);
                    layer.msg(res["msg"], {time: 1000,}, function () {
                        tblRbacPermissionGroup.ajax.reload();
                        $modalStoreResourceRbacPermissions.modal("hide");
                        $txtUri_frmStoreResourceRbacPermissions.val("");
                    });
                },
                error: function (err) {
                    console.log(`{{ route("web.RbacPermission:PostResource") }} fail:`, err);
                    layer.close(loading);
                    layer.msg(err["responseJSON"]["msg"], {time: 1500,}, function () {
                        if (err["status"] === 401) location.href = "{{ route("web.Authorization:GetLogin") }}";
                    });
                }
            });
        }

        /**
         * 删除权限分组
         * @param {string} uuid
         */
        function fnDelete(uuid = "") {
            if (uuid && confirm("删除不可恢复，是否确认？")) {
                let loading = layer.msg('处理中……', {time: 0,});
                $.ajax({
                    url: `{{ url("rbacPermissionGroup") }}/${uuid}`,
                    type: 'delete',
                    data: {},
                    async: true,
                    success: res => {
                        console.log(`{{ url("rbacPermissionGroup") }}/${uuid} success:`, res);
                        layer.close(loading);
                        layer.msg(res['msg'], {time: 1000,}, function () {
                            tblRbacPermissionGroup.ajax.reload();
                        });
                    },
                    error: err => {
                        console.log(`{{ url("rbacPermissionGroup") }}/${uuid} fail:`, err);
                        layer.close(loading);
                        layer.msg(err["responseJSON"], {time: 1500,}, () => {
                            if (err.status === 401) location.href = `{{ route("web.Authorization:GetLogin") }}`;
                        });
                    },
                });
            }
        }
    </script>
@endsection