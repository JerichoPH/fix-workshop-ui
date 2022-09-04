@extends('Layout.index')
@section('content')
    <!-- 面包屑 -->
    <section class="content-header">
        <h1>
            用户管理
            <small>列表</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="/"><i class="fa fa-dashboard"></i> 主页</a></li>
            <li class="active">用户-列表</li>
        </ol>
    </section>
    <section class="content">
        @include('Layout.alert')
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">用户-列表</h3>
                <!--右侧最小化按钮-->
                <div class="pull-right btn-group btn-group-sm">
                    <a href="{{ route('web.Account:Create', []) }}" class="btn btn-success"><i class="fa fa-plus"></i></a>
                    <a href="javascript:" class="btn btn-default" onclick="wsSendMsg()">测试</a>
                </div>
                <hr>
            </div>
            <div class="box-body">
                <table class="table table-hover table-striped table-condensed" id="tblAccount">
                    <thead>
                    <tr>
                        <th>创建时间</th>
                        <th>编号</th>
                        <th>用户名</th>
                        <th>昵称</th>
                        <th>所属路局</th>
                        <th>所属站段</th>
                        <th>所属车间</th>
                        <th>所属工区</th>
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
        let tblAccount = null;
        let ws = null;

        /**
         * 填充用户表
         */
        function fnFillTblAccount() {
            if (document.getElementById('tblAccount')) {
                tblAccount = $('#tblAccount').DataTable({
                    ajax: {
                        url: `{{ route("web.Account:Index") }}`,
                        dataSrc(res) {
                            console.log(`{{ route("web.Account:Index") }} success:`, res);
                            let {accounts,} = res['data'];
                            let render = [];
                            if (accounts.length > 0) {
                                $.each(accounts, (_, account) => {
                                    let createdAt = account["updated_at"] ? moment(account["updated_at"]).format("YYYY-MM-DD HH:mm:ss") : "";
                                    let uuid = account["uuid"];
                                    let username = account["username"];
                                    let nickname = account["nickname"];
                                    let organizationRailwayName = account["organization_railway"] ? account["organization_railway"]["name"] : "";
                                    let organizationParagraphName = account["organization_paragraph"] ? account["organization_paragraph"]["name"] : "";
                                    let organizationWorkshopName = account["organization_workshop"] ? account["organization_workshop"]["name"] : "";
                                    let organizationWorkAreaName = account["organization_work_area"] ? account["organization_workshop"]["name"] : "";
                                    let divBtnGroup = '';
                                    divBtnGroup += `<td class="">`;
                                    divBtnGroup += `<div class="btn-group btn-group-sm">`;
                                    divBtnGroup += `<a href="{{ route("web.Account:Index") }}/${uuid}/edit" class="btn btn-warning"><i class="fa fa-edit"></i></a>`;
                                    divBtnGroup += `<a href="javascript:" class="btn btn-danger" onclick="fnDelete('${uuid}')"><i class="fa fa-trash"></i></a>`;
                                    divBtnGroup += `</div>`;
                                    divBtnGroup += `</td>`;

                                    render.push([
                                        createdAt,
                                        uuid,
                                        username,
                                        nickname,
                                        organizationRailwayName,
                                        organizationParagraphName,
                                        organizationWorkshopName,
                                        organizationWorkAreaName,
                                        divBtnGroup,
                                    ]);
                                });
                            }
                            return render;
                        },
                        error(err) {
                            console.log(`{{ route("web.Account:Index") }} fail:`, err);
                            layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                                if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                            });
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

            fnFillTblAccount();  // 填充用户表
            wsInit();
        });

        function wsSendMsg() {
            let msg = JSON.stringify({uri: "ping", context: {},})
            ws.send(msg);
        }

        /**
         * 创建链接
         */
        function wsInit() {
            // 创建长连接
            ws = new WebSocket("ws://127.0.0.1:90/ws");
            //连接打开时触发
            ws.onopen = function (evt) {
                console.log("Connection open ...");
                ws.send("Hello WebSockets!");
            };
            //接收到消息时触发
            ws.onmessage = function (evt) {
                console.log("Received Message: " + evt.data);
            };
            //连接关闭时触发
            ws.onclose = function (evt) {
                console.log("Connection closed.");
            };
        }

        /**
         * 删除用户
         * @param uuid
         */
        function fnDelete(uuid = "") {
            console.log(uuid);
            if (uuid) {

                let loading = layer.msg('处理中……', {time: 0,});
                $.ajax({
                    url: `{{ route("web.Account:Index") }}/${uuid}`,
                    type: 'delete',
                    data: {},
                    async: true,
                    beforeSend() {
                    },
                    success(res) {
                        console.log(`{{ route("web.Account:Index") }}/${uuid} success:`, res);
                        layer.close(loading);
                        layer.msg(res['msg'], {time: 1000,}, function () {
                            tblAccount.ajax.reload();
                        });
                    },
                    error(err) {
                        console.log(`{{ route("web.Account:Index") }}/${uuid} fail:`, err);
                        layer.close(loading);
                        layer.msg(err["responseJSON"]["msg"], {icon: 2,}, function () {
                            if (err.status === 401) location.href = '{{ route('web.Authorization:GetLogin') }}';
                        });
                    },
                    complete() {
                    },
                });
            }
        }
    </script>
@endsection