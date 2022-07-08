<aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
        <!-- Sidebar user panel -->
    {{--        <div class="user-panel">--}}
    {{--            <div class="pull-left image">--}}
    {{--                @if(session()->has('account.avatar'))--}}
    {{--                    <img src="/{{session('account.avatar')}}" onclick="location.href='/profile'"
    class="img-circle" alt="{{session('account.nickname')}}">--}}
    {{--                @endif--}}
    {{--            </div>--}}
    {{--            <div class="pull-left info">--}}
    {{--                <p>{{session('account.nickname')}}</p>--}}
    {{--                <a href="#"><i class="fa fa-circle text-success"></i> Online</a>--}}
    {{--            </div>--}}
    {{--        </div>--}}
    <!-- search form -->
    {{--        <form action="#" method="get" class="sidebar-form">--}}
    {{--            <div class="input-group">--}}
    {{--                <input type="text" name="q" class="form-control" placeholder="Search...">--}}
    {{--                <span class="input-group-btn">--}}
    {{--                <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>--}}
    {{--                </button>--}}
    {{--              </span>--}}
    {{--            </div>--}}
    {{--        </form>--}}
    <!-- /.search form -->
        <!-- sidebar menu: : style can be found in sidebar.less -->
        <ul class="sidebar-menu" data-widget="tree" id="divTree" style="font-size: 14px;">
            {{--<li class="header">说明</li>--}}
            {{--<li><a href="javascript:" onclick="fnDescription1()"><i class="fa fa-circle-o text-red"></i> <span>关于SPU和SKU的添加说明</span></a></li>--}}
            {{--<li><a href="#"><i class="fa fa-circle-o text-yellow"></i> <span>Warning</span></a></li>--}}
            {{--<li><a href="#"><i class="fa fa-circle-o text-aqua"></i> <span>Information</span></a></li>--}}
        </ul>
    </section>
    <!-- /.sidebar -->
</aside>
<script>
    window.onload = function () {
        {{--parseJsonMenu({!! session('account.treeJson') !!});--}}
    };

    {{--let currentMenu = '{{ session('account.currentMenu') }}';--}}
    let currentMenu = '{{ request()->route()->getAction()['as'] ?? '' }}';
    let currentCategoryUniqueCode = '{{session('currentCategoryUniqueCode')}}';

    parseJsonMenu = (arr) => {
        let html = '';
        // 加入菜单头
        html = '<li class="header">菜单</li>';

        // 加入统计报表页面
        html = '<li>' +
            '<a href="/">' +
            '<i class="fa fa-home">&nbsp;</i><span>首页</span>' +
            '</a>' +
            '</li>';

        // 加入设备类型菜单
        {{--let category = JSON.parse('{!! \App\Model\Category::all()->toJson() !!}');--}}
        {{--if (category.length) {--}}
        {{--    html += currentMenu === 'categoryItem.show' ?--}}
        {{--        '<li class="treeview active">' :--}}
        {{--        '<li class="treeview">';--}}
        {{--    html += '<a href="javascript:">' +--}}
        {{--        '<i class="fa fa-cubes"></i>&nbsp;<span>检修管理</span>' +--}}
        {{--        '<span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>' +--}}
        {{--        '</a>' +--}}
        {{--        '<ul class="treeview-menu">';--}}
        {{--    for (let i in category) {--}}
        {{--        html += currentCategoryUniqueCode === category[i].unique_code ?--}}
        {{--            '<li class="active">' :--}}
        {{--            '<li>';--}}
        {{--        html += '<a href="/category/' + category[i].unique_code + '" style="font-size: 14px;">' +--}}
        {{--            '<i class="fa fa-cube"></i>&nbsp;' + category[i].name + '</a></li>';--}}
        {{--    }--}}
        {{--    html += '</ul></li>';--}}
        {{--}--}}

        let autoMenu = true;
        // 加入其他菜单
        if (autoMenu) {
            if (arr.length !== 0) {
                let pp = function (arr) {
                    for (let i = 0; i < arr.length; i++) {
                        if (arr[i].title === 'hr') {
                            html += `<li><hr></li>`;
                        } else if (arr[i].sub && arr[i].sub.length !== 0) {
                            let isCurrent = false;

                            for (let j = 0; j < arr[i].sub.length; j++) {
                                if (arr[i].sub[j].action_as === currentMenu) isCurrent = true;
                                // console.log('子集菜单', arr[i].sub[j].title, arr[i].sub[j].action_as, currentMenu, isCurrent);
                            }
                            // if (arr[i].sub.some(value => {
                            //     return value.action_as === currentMenu;
                            // })) isCurrent = true;

                            // isCurrent = (arr[i].action_as === currentMenu);
                            html += `
<li class="treeview ${isCurrent ? 'active' : ''}">
    <a href="${arr[i].uri}" style="font-size: 14px;">
        <i class="fa fa-${arr[i].icon}">&nbsp;</i><span>${arr[i].title}</span>
        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
    </a>
<ul class="treeview-menu">
`;
                            pp(arr[i].sub);
                            html += '</ul></li>';
                        } else {
                            let isCurrent = arr[i].action_as === currentMenu;
                            // console.log('父级菜单', arr[i].title, arr[i].action_as, currentMenu, isCurrent);
                            html += `
<li ${isCurrent ? 'class="active"' : ''}>
    <a href="${arr[i].uri}" style="font-size: 14px;">
        <i class="fa fa-${arr[i].icon}">&nbsp;</i><span>${arr[i].title}</span>
    </a>
</li>
`;
                        }
                    }
                };
                pp(arr);
            }

            // if (arr.length != 0) {
            //     var pp = function (arr) {
            //         for (var i = 0; i < arr.length; i++) {
            //             if (arr[i].sub && arr[i].sub.length != 0) {
            //                 var isCurrent = false;
            //
            //                 for (let j = 0; j < arr[i].sub.length; j++) {
            //                     if (arr[i].sub[j].action_as == currentMenu) isCurrent = true;
            //                 }
            //
            //                 if (arr[i].action_as == currentMenu) isCurrent = true;
            //                 html += isCurrent ?
            //                     '<li class="treeview active">' :
            //                     '<li class="treeview">';
            //                 html += '<a href="' + arr[i].uri + '" style="font-size: 14px;">' +
            //                     '<i class="fa fa-' + arr[i].icon + '">&nbsp;</i><span>' + arr[i].title + '</span>' +
            //                     '<span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>' +
            //                     '</a>' +
            //                     '<ul class="treeview-menu">';
            //                 pp(arr[i].sub);
            //                 html += '</ul></li>';
            //             } else {
            //                 var isCurrent = arr[i].action_as == currentMenu;
            //                 html += isCurrent ?
            //                     '<li class="active">' :
            //                     '<li>';
            //                 html += '<a href="' + arr[i].uri + '" style="font-size: 14px;">' +
            //                     '<i class="fa fa-' + arr[i].icon + '">&nbsp;</i><span>' + arr[i].title + '</span></a></li>';
            //             }
            //         }
            //     };
            //     pp(arr);
            // }
        }
        $('#divTree').html(html);
    }
</script>
