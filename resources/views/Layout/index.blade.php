<!DOCTYPE html>
<html>

<head>
    @include('Layout.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('style')
</head>
@if(request("is_iframe")!=1)
    <body class="hold-transition skin-blue-light {{ session('account.account') == '8d' ? 'layout-top-nav' : '' }} sidebar-mini">
    @else
        <body class="hold-transition skin-blue-light layout-top-nav sidebar-mini">
        @endif
        <div class="wrapper">
            @if(request("is_iframe")!=1)
                @if(session('account.account') == '8d')
                    @include('Layout.main-header-without-sidebar')
                @else
                    @include('Layout.main-header')
                    @include('Layout.main-sidebar')
                @endif
                <div class="content-wrapper">
                    @yield('content')
                </div>
                @include('Layout.footer')
            @else
                <div class="content-wrapper">
                    @yield('content')
                </div>
            @endif
        </div>

        @include('Layout.script')
        <script>
            /**
             * 绑定全选按钮事件
             * @param {string} checkAll 全选按钮ID
             * @param {string} checkItem 被全选按钮类型名称
             */
            function fnCheckAll(checkAll = "", checkItem = "") {
                // 全选按钮事件绑定
                $(`#${checkAll}`).on("change", function () {
                    $(`.${checkItem}`).prop("checked", $(`#${checkAll}`).is(":checked"));
                });
            }

            /**
             * 初始化菜单
             */
            function fnInitMenu() {
                let currentUriName = "{{ request()->route()->getName() }}".split(":")[0];
                let activeUUIDs = [];
                let html = '';
                html = '<li class="header">菜单</li>';

                // 加入统计报表页面
                html = '<li>' +
                    '<a href="/">' +
                    '<i class="fa fa-home">&nbsp;</i><span>首页</span>' +
                    '</a>' +
                    '</li>';

                let fillMenuItem = function (arr) {
                    for (let k = 0; k < arr.length; k++) {
                        if (arr[k]["subs"]) {
                            html += `
<li class="treeview" id="menu_${arr[k]["uuid"]}">
    <a href="${arr[k]["url"]}" style="font-size: 14px;">
        <i class="${arr[k]["icon"] ? arr[k]["icon"] : "fa fa-circle-o"}">&nbsp;</i><span>${arr[k]["name"]}</span>
        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
    </a>
<ul class="treeview-menu">
`;
                            fillMenuItem(arr[k]["subs"]);
                            html += '</ul></li>';
                        } else {
                            // 判断是否是当前路由
                            if (arr[k]["uri_name"] === currentUriName) {
                                activeUUIDs.push(arr[k]["uuid"]);
                                if (arr[k]["parent_uuid"]) {
                                    activeUUIDs.push(arr[k]["parent_uuid"]);
                                }
                            }
                            html += `
<li id="menu_${arr[k]["uuid"]}">
    <a href="${arr[k]["url"]}" style="font-size: 14px;">
        <i class="${arr[k]["icon"] ? arr[k]["icon"] : "fa fa-circle-o"}">&nbsp;</i><span>${arr[k]["name"]}</span>
    </a>
</li>
`;
                        }
                    }
                };

                $.ajax({
                    url: `{{ route("web.Authorization:GetMenus") }}`,
                    type: 'get',
                    data: {},
                    async: true,
                    success: function (res) {
                        console.log(`{{ route("web.Authorization:GetMenus") }} success:`, res);

                        let {menus,} = res["data"];
                        if (menus.length > 0) {
                            fillMenuItem(menus);
                        }
                        $('#divTree').html(html);
                        if (activeUUIDs.length > 0) {
                            activeUUIDs.map(function (activeUUID) {
                                $(`#menu_${activeUUID}`).addClass("active");
                            });
                        }
                    },
                    error: function (err) {
                        console.log(`{{ route("web.Authorization:GetMenus") }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        alert(err['responseJSON']['msg']);
                    }
                });
            }

            $(function () {
                fnInitMenu();  // 初始化菜单
            });
        </script>
        @yield('script')
        </body>
    </body>
</html>
