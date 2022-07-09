<header class="main-header">
    <!-- Header Navbar: style can be found in header.less -->
    <nav class="navbar navbar-static-top" style="background-color: #0477c4;">

        <div class="navbar-header">
            <a href="{{ url('/') }}"><img src="/images/logo{{ env('RAILWAY_CODE') ? '-'.env('RAILWAY_CODE') : '' }}.png" alt="" style="height: 44px;position: relative; top: 5px;left: 5px;"></a>
        </div>
        <ul class="nav navbar-nav" style="position: relative;">
            <li class="dropdown tasks-menu">
                <a href="{{ url('/') }}" role="button" style="font-size: 18px;">{{ env("APP_NAME") }}</a>
            </li>
        </ul>

        <!-- Navbar Right Menu -->
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
                <!-- Messages: style can be found in dropdown.less-->
                <li class="dropdown tasks-menu">
                    <a href="javascript:" onclick="history.back(-1);" style="font-size: 22px;"><i class="fa fa-arrow-left"></i></a>
                </li>
                {{--<li class="dropdown tasks-menu">--}}
                {{--    <a href="javascript:" onclick="location.href='/warehouse/report/scanInBatch'" style="font-size: 22px;"><i class="fa fa-barcode"></i></a>--}}
                {{--</li>--}}
                {{--<li class="dropdown tasks-menu">--}}
                {{--    <a href="javascript:" onclick="fnModalSearch()" style="font-size: 22px;"><i class="fa fa-search">&nbsp;</i>老搜索</a>--}}
                {{--</li> --}}
                <li class="dropdown tasks-menu">
                    <a href="{{ url('query') }}" style="font-size: 22px;"><i class="fa fa-search">&nbsp;</i></a>
                </li>
                {{--<li class="dropdown tasks-menu">--}}
                {{--    <a href="javascript:" onclick="fnModalScanQrCode()" style="font-size: 22px;"><i class="fa fa-qrcode"></i></a>--}}
                {{--</li>--}}
            <!-- User Account: style can be found in dropdown.less -->
                <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <img src="/images/account-avatar-lack.jpeg" class="user-image" alt="{{ session('account.nickname') }}">
                        <span class="hidden-xs">{{ session('account.nickname') }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <!-- User image -->
                        <li class="user-header">
                            <img src="/images/account-avatar-lack.jpeg" onclick="location.href='/profile'" class="img-circle" alt="{{ session('account.nickname') }}">

                            <p>
                                {{ session('account.nickname') }} - 管理员
                                <small>{{ session('account.created_at') }}</small>
                            </p>
                        </li>
                        <!-- Menu Body -->
                    {{--<li class="user-body">--}}
                    {{--<div class="row">--}}
                    {{--<div class="col-xs-4 text-center">--}}
                    {{--<a href="#">Followers</a>--}}
                    {{--</div>--}}
                    {{--<div class="col-xs-4 text-center">--}}
                    {{--<a href="#">Sales</a>--}}
                    {{--</div>--}}
                    {{--<div class="col-xs-4 text-center">--}}
                    {{--<a href="#">Friends</a>--}}
                    {{--</div>--}}
                    {{--</div>--}}
                    {{--</li>--}}
                    <!--Menu Footer-->
                        <li class="user-footer">
                            <div class="pull-left">
                                {{--<a href="{{url('/profile')}}" class="btn btn-default">个人中心</a>--}}
                            </div>
                            <div class="pull-right">
                                <a href="{{ url('/logout') }}" class="btn btn-default">退出登录</a>
                            </div>
                        </li>
                    </ul>
                </li>
                <!-- Control Sidebar Toggle Button -->
                {{--<li>--}}
                {{--    <a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>--}}
                {{--</li>--}}
            </ul>
        </div>
        <!-- /.navbar-custom-menu -->
        <!-- /.container-fluid -->
    </nav>
    <nav class="navbar navbar-default">

        <ul style="padding-left:30px" class="nav nav-pills" id="divTree2"></ul>

    </nav>
</header>
<script>
    let treeJson = JSON.parse('{!! session('account.treeJson') !!}');

    window.onload = function () {
        parseJsonMenu({!! session('account.treeJson') !!});
    };

    let currentMenu = '{{ session('account.currentMenu') }}';
    let currentCategoryUniqueCode = '{{session('currentCategoryUniqueCode')}}';

    parseJsonMenu = (arr) => {
        let html = '';
        let monitor = '';
        switch ('{{ env("ORGANIZATION_CODE") }}') {
            case 'B041':
                monitor = `<a href="{{ url('newmonitor/') }}" target="_blank"><i class="fa fa-map-o">&nbsp;</i>{{ env('ORGANIZATION_CODE') }}</a>`;
                break;
            case 'B049':
                monitor = `<a href="{{ url('monitor/') }}" target="_blank"><i class="fa fa-map-o">&nbsp;</i>{{ env('ORGANIZATION_CODE') }}</a>`;
                break;
            default:
                break;
        }

        // 加入统计报表页面
        html = monitor;
        let autoMenu = true;
        // 加入其他菜单
        if (autoMenu) {
            if (arr.length !== 0) {
                let pp = function (arr) {
                    for (let i = 0; i < arr.length; i++) {
                        if (arr[i].title === 'hr') {
                            html += ` `;
                        } else if (arr[i].sub && arr[i].sub.length !== 0) {
                            let isCurrent = false;

                            for (let j = 0; j < arr[i].sub.length; j++) {
                                if (arr[i].sub[j].action_as === currentMenu) isCurrent = true;
                            }
                            if (arr[i].sub.some(value => {
                                return value.action_as === currentMenu;
                            })) isCurrent = true;

                            isCurrent = (arr[i].action_as === currentMenu);

                            html += `
                                <li class="dropdown" ${isCurrent ? 'active' : ''}">
                                <a class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="${arr[i].uri}" style="font-size: 14px;">
                                    <i class="fa fa-${arr[i].icon}">&nbsp;</i><span>${arr[i].title}</span>
                                </a>
                                <ul class="dropdown-menu">`;
                            pp(arr[i].sub);
                            html += '</ul></li>';
                        } else {
                            let isCurrent = arr[i].action_as === currentMenu;
                            html += `
                                    <li ${isCurrent ? 'class="active"' : ''}>
                                    <a  href="${arr[i].uri}" style="font-size: 14px;">
                                        <i class="fa fa-${arr[i].icon}">&nbsp;</i><span>${arr[i].title}</span>
                                    </a>
                                    </li>`;
                        }
                    }
                };
                pp(arr);
            }
        }
        $('#divTree').html(html);
        $('#divTree2').html(html);
    }
</script>
