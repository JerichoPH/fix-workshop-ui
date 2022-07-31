@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            菜单管理
            <small>列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li class="active">菜单-列表</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <form id="frmSearch">
                    <div class="row">
                        <div class="col-md-8">
                            <h3 class="box-title">菜单-列表</h3>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <div class="input-group-addon">父级</div>
                                <select name="parent_uuid" id="selParentMenu" class="select2 form-control" style="width: 100%;"></select>
                                <div class="input-group-btn">
                                    <a href="javascript:" class="btn btn-default" onclick="fnSearch()"><i class="fa fa-search"></i></a>
                                    <a href="javascript:" class="btn btn-success" onclick="fnToCreate()"><i class="fa fa-plus"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="box-body">
                <table class="table table-hover table-striped table-condensed" id="tblMenu">
                    <thead>
                    <tr>
                        <th>创建时间</th>
                        <th>名称</th>
                        <th>URL</th>
                        <th>路由名称</th>
                        <th>图标</th>
                        <th>所属父级</th>
                        <th>所属权限</th>
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
        let tblMenu = null;
        let $selParentMenu = $("#selParentMenu");
        let $frmSearch = $("#frmSearch");

        /**
         * 填充顶级菜单下拉列表
         */
        function fnFillParentMenu() {
            $.ajax({
                url: `{{ route("web.Menu:Index") }}`,
                type: 'get',
                data: {parent_uuid: "",},
                async: true,
                success: function (res) {
                    console.log(`{{ route("web.Menu:Index") }} success:`, res);

                    let {menus,} = res["data"];

                    $selParentMenu.empty();
                    $selParentMenu.append(`<option value="">顶级</option>`);
                    if (menus.length > 0) {
                        menus.map(function (menu) {
                            $selParentMenu.append(`<option value="${menu["uuid"]}" ${"{{ request("parent_uuid") }}" === menu["uuid"] ? "selected" : ""}>${menu["name"]}</option>`);
                        });
                    }
                },
                error: function (err) {
                    console.log(`{{ route("web.Menu:Index") }} fail:`, err);
                    if (err.status === 401) location.href = "{{ url('login') }}";
                    alert(err['responseJSON']['msg']);
                }
            });
        }

        /**
         * 填充菜单表
         */
        function fnFillTblMenu() {
            if (document.getElementById('tblMenu')) {
                tblMenu = $('#tblMenu').DataTable({
                    ajax: {
                        url: `{{ route("web.Menu:Index") }}?{!! http_build_query(request()->all()) !!}`,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.Menu:Index") }}?{!! http_build_query(request()->all()) !!} success:`, res);
                            let {menus: menus,} = res['data'];
                            let render = [];
                            if (menus.length > 0) {
                                $.each(menus, (key, menu) => {
                                    let createdAt = menu["created_at"] ? moment(menu["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uuid = menu["uuid"];
                                    let name = menu["name"];
                                    let url = menu["url"];
                                    let uriName = menu["uri_name"];
                                    let icon = menu["icon"];
                                    let parentName = menu["parent"] ? menu["parent"]["name"] : "";
                                    let rbacRoleNames = [];
                                    if (menu["rbac_roles"].length > 0) {
                                        menu["rbac_roles"].map(function (rbacRole) {
                                            rbacRoleNames.push(rbacRole["name"]);
                                        });
                                    }
                                    let divBtnGroup = '';
                                    divBtnGroup += `<td class="align-middle">`;
                                    divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                    divBtnGroup += `<a href="{{ url("menu") }}/${uuid}/edit" class="btn btn-warning"><i class="fa fa-edit"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDelete('${uuid}')"><i class="fa fa-trash"></i></a>`;
                                    divBtnGroup += `</div>`;
                                    divBtnGroup += `</td>`;

                                    render.push([
                                        createdAt,
                                        name,
                                        url,
                                        uriName,
                                        `<i class="${icon}"></i>`,
                                        parentName,
                                        `<span class="label label-default">${rbacRoleNames.join('</span><span class="label label-default">')}</span>`,
                                        divBtnGroup,
                                    ]);
                                });
                            }
                            return render;
                        },
                        error: function (err) {
                            console.log(`{{ route("web.Menu:Index") }}?{!! http_build_query(request()->all()) !!} fail:`, err);
                            if (err["status"] === 406) {
                                layer.alert(err["responseJSON"]["msg"], {icon:2, });
                            }else{
                                layer.msg(err["responseJSON"]["msg"], {time: 1500,}, function () {
                                    if (err["status"] === 401) location.href = `{{ route("web.Authorization:GetLogin") }}`;
                                });
                            }
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
                    iDisplayLength: 200,  // 默认分页数
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

            fnFillParentMenu();  // 填充顶级菜单下拉列表
            fnFillTblMenu();  // 填充菜单表
        });

        /**
         * 搜索
         */
        function fnSearch() {
            let queries = $.param($frmSearch.serializeArray());

            tblMenu.ajax.url(`{{ route("web.Menu:Index") }}?${queries}`);
            tblMenu.ajax.reload();
        }

        /**
         * 跳转到新建页面
         */
        function fnToCreate() {
            let parentUUID = $selParentMenu.val();
            location.href = `{{ route('web.Menu:Create') }}?parent_uuid=${parentUUID}`;
        }

        /**
         * 删除
         * @param uuid
         */
        function fnDelete(uuid = "") {
            let loading = layer.msg("处理中……", {time: 0});

            if (uuid && confirm("删除不可恢复，是否确认？")) {
                $.ajax({
                    url: `{{ url("menu") }}/${uuid}`,
                    type: 'delete',
                    data: {},
                    async: true,
                    success: function (res) {
                        console.log(`{{ url("menu") }}/${uuid} success:`, res);

                        layer.close(loading);
                        layer.msg(res["msg"], {time: 1000,}, function () {
                            tblMenu.ajax.reload();
                        });
                    },
                    error: function (err) {
                        console.log(`{{ url("menu") }}/${uuid} fail:`, err);
                        layer.close(loading);
                        layer.msg(err["responseJSON"]["msg"], {time: 1500,}, function () {
                            if (err["status"] === 401) location.href = `{{ route("web.Authorization:GetLogin") }}`;
                        });
                    }
                });
            }
        }
    </script>
@endsection