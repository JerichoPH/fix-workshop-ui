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

            $(function () {

            });
        </script>
        @yield('script')
        </body>
    </body>
</html>
