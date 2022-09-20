<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="/AdminLTE/bower_components/font-awesome/css/font-awesome.min.css">
    <link href="/layui/css/layui.css" rel="stylesheet">
    <title>{{ env("APP_NAME") }}</title>
    @yield('style')
</head>
<body>
<ul class="layui-nav" lay-filter="navTop" id="navTop">
    <li class="layui-nav-item layui-this"><a href="">最新活动</a></li>
    <li class="layui-nav-item"><a href="">产品</a></li>
    <li class="layui-nav-item"><a href="">大数据</a></li>
    <li class="layui-nav-item">
        <a href="javascript:">解决方案</a>
        <dl class="layui-nav-child"> <!-- 二级菜单 -->
            <dd><a href="">移动模块</a></dd>
            <dd><a href="">后台模版</a></dd>
            <dd><a href="">电商平台</a></dd>
        </dl>
    </li>
    <li class="layui-nav-item"><a href="">社区</a></li>
</ul>
@yield('content')
</body>
<script src="/AdminLTE/bower_components/moment/min/moment.min.js"></script>
<script src="/layui/layui.js"></script>
<script>
    //注意：导航 依赖 element 模块，否则无法进行功能性操作
    layui.use(function () {
        let {jquery: $, element,} = layui;
        let $navTop = $('#navTop');

        let fnInitMenu = function () {
            let currentUriName = "{{ request()->route()->getName() }}".split(":")[0];
            let activeUuids = [];
            let html = '';

            html += `<li class="layui-nav-item"><a href="{{ route("web.Home:Index") }}"><i class="fa fa-home">&nbsp;</i>首页</a></li>`;

            let fnFillMenuItem = function (arr) {
                for (let k = 0; k < arr.length; k++) {
                    if (arr[k]["subs"]) {
                        html += `
<li class="layui-nav-item">
    <a href="javascript:" id=menu_${arr[k]["uuid"]}><i class="${arr[k]["icon"] ? arr[k]["icon"] : "fa fa-circle-o"}">&nbsp;</i>${arr[k]["name"]}</a>
        <dl class="layui-nav-child">`;
                        fnFillMenuItem(arr[k]["subs"]);
                        html += '</dl>';
                    } else {
                        // 判断是否是当前路由
                        if (arr[k]["uri_name"] === currentUriName) {
                            activeUuids.push(arr[k]["uuid"]);
                            if (arr[k]["parent_uuid"]) {
                                activeUuids.push(arr[k]["parent_uuid"]);
                            }
                        }
                        html += `<dd id="menu_${arr[k]["uuid"]}"><a href="${arr[k]["url"]}"><i class="${arr[k]["icon"] ? arr[k]["icon"] : "fa fa-circle-o"}">&nbsp;</i>${arr[k]["name"]}</a></dd>`;
                    }
                }
            };

            $.ajax({
                url: `{{ route("web.Authorization:GetMenus") }}`,
                type: 'get',
                data: {},
                async: true,
                beforeSend: function () {
                },
                success: function (res) {
                    console.log(`{{ route("web.Authorization:GetMenus") }} success:`, res);

                    let {menus,} = res['data'];
                    if (menus.length > 0) {
                        fnFillMenuItem(menus);
                    }
                    $navTop.html(html);
                    if (activeUuids.length > 0) {
                        activeUuids.map(function (activeUUID) {
                            $(`#menu_${activeUUID}`).addClass("layui-this");
                        });
                    }
                },
                error: function (err) {
                    console.error(`{{ route("web.Authorization:GetMenus") }} error:`, err);
                },
                complete: function () {
                    element.init();
                },
            });
        };

        $(function () {
            fnInitMenu();
        });

    });
</script>
@yield('script')
</html>