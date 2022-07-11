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
                                    <a href="javascript:" class="btn btn-primary" onclick="fnSearch()"><i class="fa fa-search"></i></a>
                                    <a href="{{ route('web.Menu:Create', ['page' => request('page', 1), ]) }}" class="btn btn-success"><i class="fa fa-plus"></i></a>
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
                        <th>所属父级</th>
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

                    if (menus.length > 0) {
                        $selParentMenu.empty();
                        $selParentMenu.append(`<option value="">顶级</option>`);
                        menus.map(function (menu) {
                            $selParentMenu.append(`<option value="${menu["uuid"]}">${menu["name"]}</option>`);
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
                        url: `{{ route("web.Menu:Index") }}`,
                        dataSrc: function (res) {
                            console.log(`{{ route("web.Menu:Index") }} success:`, res);
                            let {menus: menus,} = res['data'];
                            let render = [];
                            if (menus.length > 0) {
                                $.each(menus, (key, menu) => {
                                    let createdAt = menu["created_at"] ? moment(menu["created_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uuid = menu["uuid"];
                                    let name = menu["name"];
                                    let url = menu["url"];
                                    let uriName = menu["uri_name"];
                                    let parentName = menu["parent"] ? menu["parent"]["name"] : "";
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
                                        parentName,
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
    </script>
@endsection