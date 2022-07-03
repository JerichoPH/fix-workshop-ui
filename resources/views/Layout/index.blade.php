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
            let $liMessagesCount = $('#liMessagesCount');
            let $spanMessagesCount = $('#spanMessagesCount');
            let $ulMessages = $('#ulMessages');
            let $spanScrapedAndCycleFixWarningCount = $('#spanScrapedAndCycleFixWarningCount');
            let $divScrapedAndCycleFixWarnings = $('#divScrapedAndCycleFixWarnings');

            /**
             * 必须是正数
             * @param event
             */
            let mustBePositiveNumber = function (event) {
                event.target.value = event.target.value >= 0 ? event.target.value : 0;
            }

            /**
             * 颠倒字典KEY和VALUE
             */
            let dictFlip = function (arr = {}) {
                let arrNew = {};

                for (let k in arr) {
                    let value = arr[k];
                    arrNew[value] = k;
                }
                return arrNew;
            }

            /**
             * 初始化消息列表
             */
            function fnInitMessage() {
                let html = '';
                $.ajax({
                    url: `/message`,
                    type: 'get',
                    data: {
                        limit: 5,
                        ordering: 'id desc'
                    },
                    async: true,
                    success: function (res) {
                        if (res['data'].hasOwnProperty('messages')) {
                            let {messages} = res['data'];

                            let unreadCount = 0;
                            $.each(messages, function (index, item) {
                                let isUnRead = false;
                                if (!item['is_read']) {
                                    unreadCount++;
                                    isUnRead = true;
                                }
                                let subjoin = JSON.parse(item['subjoin']);
                                // let createdAt = moment(item['created_at']).format('YYYY-MM-DD hh:mm:ss');
                                let createdAt = moment(item['created_at']).format('YYYY-MM-DD');
                                let title = item['title'].tooLong(20);
                                let intro = item['intro'].tooLong(20);

                                let urlCombo = subjoin['before']['uri'].indexOf('?') !== -1 ? '&' : '?';

                                if (subjoin['before']) {
                                    switch (subjoin['before']['operator']) {
                                        case 'href':
                                            html += `<li>`;
                                            html += `<a href="${subjoin['before']['uri']}${urlCombo}message_id=${item['id']}">`;
                                            html += `<div class="pull-left text-center">`;
                                            html += `&emsp;<h5 class="fa fa-bell-o"></h5>`;
                                            html += `</div>`;
                                            html += `<h5 style="${isUnRead ? 'font-weight: bold;' : ''}">${title}  <small> ${createdAt}</small></h5>`;
                                            html += `<p>${intro}</p>`;
                                            html += `</a>`;
                                            html += `</li>`;
                                            break;
                                        default:
                                            html += `<li>`;
                                            html += `<a href="/message/${item['id']}${urlCombo}type=input&page=1">`;
                                            html += `<div class="pull-left text-center">`;
                                            html += `&emsp;<h5 class="fa fa-bell-o"></h5>`;
                                            html += `</div>`;
                                            html += `<h5 style="${isUnRead ? 'font-weight: bold;' : ''}">${title}<small><i class="fa fa-clock-o"></i> ${createdAt}</small></h5>`;
                                            html += `<p>${intro}</p>`;
                                            html += `</a>`;
                                            html += `</li>`;
                                            break;
                                    }
                                } else {
                                    html += `<li>`;
                                    html += `<a href="/message/${item['id']}${urlCombo}type=input&page=1">`;
                                    html += `<div class="pull-left text-center">`;
                                    html += `&emsp;<h5 class="fa fa-bell-o"></h5>`;
                                    html += `</div>`;
                                    html += `<h5 style="${isUnRead ? 'font-weight: bold;' : ''}">${title}<small><i class="fa fa-clock-o"></i> ${createdAt}</small></h5>`;
                                    html += `<p>${intro}</p>`;
                                    html += `</a>`;
                                    html += `</li>`;
                                }
                            });
                            $spanMessagesCount.text(res['count']);  // 消息总数
                            $liMessagesCount.text(unreadCount);  // 未读消息数
                            $ulMessages.html(html);
                        }
                    },
                    fail: function (err) {
                        console.log(`{{url('message')}} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        layer.alert(err.responseText, {icon: 2, title: '错误',});
                    }
                });
            }

            /**
             * get scraped and cycle fix plan warning list
             */
            function fnInitRemind() {
                $.ajax({
                    url: `{{ url('remind') }}`,
                    type: 'get',
                    data: {},
                    async: true,
                    success: function (res) {
                        console.log(`{{ url('remind') }} success:`, res);
                        let {
                            scraped_statistics: scrapedStatistics,
                            cycle_fix_plan_statistics: cycleFixPlanStatistics,
                            fixed_overdue_6_month_statistics: fixedOverdue6MonthStatistics,
                        } = res['data'];
                        let total = 0;

                        let html = '';

                        if (fixedOverdue6MonthStatistics.length > 0) {
                            html += `<div class="row">`;
                            html += `<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">`;
                            html += `<table class="table table-bordered table-condensed table-hover">`;
                            html += `<thead>`;
                            html += `<tr>`;
                            html += `<th colspan="4" class="text-center"><h3>所内超6个月成品检修提醒</h3></th>`;
                            html += `</tr>`;
                            html += `<tr>`;
                            html += `<th>种类</th>`;
                            html += `<th>类型</th>`;
                            html += `<th>型号</th>`;
                            html += `<th>数量</th>`;
                            html += `</tr>`;
                            html += `</thead>`;
                            html += `<tbody>`;
                            fixedOverdue6MonthStatistics.map(function (fixedOverdue6MonthStatistic) {
                                let {
                                    category_name: categoryName,
                                    category_unique_code: categoryUniqueCode,
                                    entire_model_name: entireModelName,
                                    entire_model_unique_code: entireModelUniqueCode,
                                    sub_model_name: subModelName,
                                    sub_model_unique_code: subModelUniqueCode,
                                    aggregate
                                } = fixedOverdue6MonthStatistic;
                                let overdue6MonthAt = moment().subtract(6, "months").startOf("day").format("YYYY-MM-DD 00:00:00");
                                let queries = {
                                    checked_at: `0001-01-01 00:00:00~${overdue6MonthAt}`,
                                    category_unique_code: categoryUniqueCode,
                                    entire_model_unique_code: entireModelUniqueCode,
                                    status: "FIXED",
                                    work_area_unique_code: "{{ session('account.work_area_unique_code') }}",
                                };
                                if (subModelUniqueCode !== "") queries["model_unique_code"] = subModelUniqueCode;
                                let url = `{{ url('entire/instance') }}?${$.param(queries)}`;
                                html += `<tr>`;
                                html += `<td><a href="${url}">${categoryName}</a></td>`;
                                html += `<td><a href="${url}">${entireModelName}</a></td>`;
                                html += `<td><a href="${url}">${subModelName}</a></td>`;
                                html += `<td><a href="${url}">${aggregate}</a></td>`;
                                html += `</tr>`;
                                total++;
                            });
                            html += `</tbody>`;
                            html += `</html>`;
                            html += `</div>`;
                            html += `</div>`;
                        }

                        if (scrapedStatistics.length > 0) {
                            html += `<div class="row">`;
                            html += `<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">`;
                            html += `<table class="table table-bordered table-condensed table-hover">`;
                            html += `<thead>`;
                            html += `<tr>`;
                            html += `<th colspan="3" class="text-center"><h3>寿命超期提醒</h3></th>`;
                            html += `</tr>`;
                            html += `<tr>`;
                            html += `<th>车站</th>`;
                            html += `<th>种类</th>`;
                            html += `<th>超期数量</th>`;
                            html += `</tr>`;
                            html += `</thead>`;
                            html += `<tbody>`;
                            scrapedStatistics.map(function (scrapedStatistic) {
                                let {category_name: categoryName, category_unique_code: categoryUniqueCode, station_unique_code: stationUniqueCode, station_name: stationName, aggregate} = scrapedStatistic;
                                let queries = {
                                    is_scraped: 'in',
                                    station_unique_code: stationUniqueCode,
                                    category_unique_code: categoryUniqueCode,
                                };
                                let url = `{{ url('entire/instance') }}?${$.param(queries)}`;
                                html += `<tr>`;
                                html += `<td><a href="${url}">${stationName ? stationName : "无"}</a></td>`;
                                html += `<td><a href="${url}">${categoryName}</a></td>`;
                                html += `<td><a href="${url}">${aggregate}</a></td>`;
                                html += `</tr>`;
                                total++;
                            });
                            html += `</tbody>`;
                            html += `</html>`;
                            html += `</div>`;
                            html += `</div>`;
                        }

                        if (cycleFixPlanStatistics.length > 0) {
                            html += `<div class="row">`;
                            html += `<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">`;
                            html += `<table class="table table-bordered table-condensed table-hover">`;
                            html += `<thead>`;
                            html += `<tr>`;
                            html += `<th colspan="3" class="text-center"><h3>周期修超期提醒</h3></th>`;
                            html += `</tr>`;
                            html += `<tr>`;
                            html += `<th>车站</th>`;
                            html += `<th>种类</th>`;
                            html += `<th>周期修计划数量</th>`;
                            html += `</tr>`;
                            html += `</thead>`;
                            html += `<tbody>`;
                            cycleFixPlanStatistics.map(function (cycleFixPlanStatistic) {
                                let {category_name: categoryName, category_unique_code: categoryUniqueCode, station_unique_code: stationUniqueCode, station_name: stationName, aggregate} = cycleFixPlanStatistic;
                                let originAt = moment().startOf('month').format('YYYY-MM-DD');
                                let finishAt = moment().endOf('month').format('YYYY-MM-DD');
                                let queries = {
                                    use_next_fixing_day: 1,
                                    next_fixing_day: `${originAt}~${finishAt}`,
                                    station_unique_code: stationUniqueCode,
                                    category_unique_code: categoryUniqueCode,
                                };
                                let url = `{{ url('entire/instance') }}?${$.param(queries)}`;
                                html += `<tr>`;
                                html += `<td><a href="${url}">${stationName ? stationName : "无"}</a></td>`;
                                html += `<td><a href="${url}">${categoryName}</a></td>`;
                                html += `<td><a href="${url}">${aggregate}</a></td>`;
                                html += `</tr>`;
                                total++;
                            });
                            html += `</tbody>`;
                            html += `</html>`;
                            html += `</div>`;
                            html += `</div>`;
                        }

                        $divScrapedAndCycleFixWarnings.html(html);
                        $spanScrapedAndCycleFixWarningCount.text(total);
                    },
                    error: function (err) {
                        console.log(`{{ url("remind") }} fail:`, err);
                        if (err.status === 401) location.href = "{{ url('login') }}";
                        layer.alert(err["responseJSON"]["msg"], {icon: 2, title: "错误",});
                    }
                });
            }

            // $('.content-header').hide()
            $(function () {
                // if (self != top) {
                //     $('body').addClass('layout-top-nav')
                //     $('header').hide()
                //     $('aside').hide()
                //     $('footer').hide()
                //     $('.content-header').hide()
                // }
                // 刷新站列表
                // fnGetStationName($('#selMaintainWorkshop').val());
                // 刷新部件型号列表
                // fnGetPartModelUniqueCodeByCategoryUniqueCode($("#selPartModelCategoryUniqueCode").val());


                fnInitRemind();

                // 定时获取消息列表 (2秒）
                let i = 0;
                setInterval(function () {
                    // 每次页面打开最多十分钟获取消息，然后停止获取消息
                    if (i < parseInt("{{ env('SPAS_GET_MESSAGE_LIVE_TIME', 1) }}")) {
                        fnInitMessage();
                        i++;
                    }
                }, 2000);
            });

            {{--/**--}}
            {{-- * 打开搜索窗口--}}
            {{-- */--}}
            {{--function fnModalSearch() {--}}
            {{--    $("#modalSearch").modal("show");--}}

            {{--    $('.select2').select2();--}}

            {{--    // iCheck for checkbox and radio inputs--}}
            {{--    if ($('input[type="checkbox"].minimal, input[type="radio"].minimal').length > 0) {--}}
            {{--        $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({--}}
            {{--            checkboxClass: 'icheckbox_minimal-blue',--}}
            {{--            radioClass: 'iradio_minimal-blue'--}}
            {{--        });--}}
            {{--    }--}}

            {{--    if (document.getElementById('modalSearchDateRangePicker')) {--}}
            {{--        $("#modalSearchDateRangePicker").daterangepicker();--}}
            {{--    }--}}
            {{--}--}}

            {{--/**--}}
            {{-- * 打开扫码输入窗口--}}
            {{-- */--}}
            {{--function fnModalScanQrCode() {--}}
            {{--    $("#modalScanQrCode").modal("show");--}}
            {{--    document.getElementById("txtQrCode").focus();--}}
            {{--}--}}

            {{--/**--}}
            {{-- * 跳转到设备详情页面（二维码）--}}
            {{-- */--}}
            {{--function fnScanQrCode() {--}}
            {{--    $.ajax({--}}
            {{--        url: "{{url('qrcode/parse')}}",--}}
            {{--        type: "get",--}}
            {{--        data: {--}}
            {{--            type: 'scan',--}}
            {{--            params: JSON.parse($("#txtQrCode").val())--}}
            {{--        },--}}
            {{--        async: false,--}}
            {{--        success: function (response) {--}}
            {{--            switch (response.type) {--}}
            {{--                case "redirect":--}}
            {{--                    location.href = response.url;--}}
            {{--                    break;--}}
            {{--                default:--}}
            {{--                    console.log('ok');--}}
            {{--                    break;--}}
            {{--            }--}}
            {{--        },--}}
            {{--        error: function (error) {--}}
            {{--            // console.log('fail:', error);--}}
            {{--            if (error.status === 401) location.href = "{{ url('login') }}";--}}
            {{--            layer.alert(error.responseText, {icon: 2, title: '错误',});--}}
            {{--        },--}}
            {{--    });--}}
            {{--}--}}

            {{--/**--}}
            {{-- * 跳转到设备详情页面（条形码）--}}
            {{-- */--}}
            {{--function fnScanBarCode() {--}}
            {{--    $.ajax({--}}
            {{--        url: "{{url('barcode/parse')}}",--}}
            {{--        type: "get",--}}
            {{--        data: {--}}
            {{--            type: 'scan',--}}
            {{--            serial_number: $("#txtBarCode").val()--}}
            {{--        },--}}
            {{--        async: false,--}}
            {{--        success: function (response) {--}}
            {{--            switch (response.type) {--}}
            {{--                case "redirect":--}}
            {{--                    location.href = response.url;--}}
            {{--                    break;--}}
            {{--                default:--}}
            {{--                    console.log('ok');--}}
            {{--                    break;--}}
            {{--            }--}}
            {{--        },--}}
            {{--        error: function (error) {--}}
            {{--            // console.log('fail:', error);--}}
            {{--            if (error.status === 401) location.href = "{{ url('login') }}";--}}
            {{--            layer.alert(error.responseText, {icon: 2, title: '错误',});--}}
            {{--        },--}}
            {{--    });--}}
            {{--}--}}

            {{--var searchType = 'Entire';--}}

            {{--/**--}}
            {{-- * 切换搜索类型--}}
            {{-- * @param searchType--}}
            {{-- */--}}
            {{--function fnSearchType(searchType) {--}}
            {{--    this.searchType = searchType;--}}
            {{--}--}}

            {{--/**--}}
            {{-- * 根据车间名称获取站名称--}}
            {{-- * @param {string} workshopName 车间名称--}}
            {{-- */--}}
            {{--function fnGetStationName(workshopName) {--}}
            {{--    if (workshopName !== '') {--}}
            {{--        $.ajax({--}}
            {{--            url: "{{url('maintain')}}",--}}
            {{--            type: "get",--}}
            {{--            data: {--}}
            {{--                'type': 'STATION',--}}
            {{--                workshopName: workshopName--}}
            {{--            },--}}
            {{--            async: false,--}}
            {{--            success: function (response) {--}}
            {{--                console.log(response);--}}
            {{--                html = '';--}}
            {{--                $.each(response, function (index, item) {--}}
            {{--                    html += `<option value="${item.name}">${item.name}</option>`;--}}
            {{--                });--}}
            {{--                $("#selIndexMaintainStation").html(html);--}}
            {{--            },--}}
            {{--            error: function (error) {--}}
            {{--                // console.log('fail:', error);--}}
            {{--                if (error.status === 401) location.href = "{{ url('login') }}";--}}
            {{--                layer.alert(error.responseText, {icon: 2, title: '错误',});--}}
            {{--            },--}}
            {{--        });--}}
            {{--    }--}}
            {{--}--}}

            {{--/**--}}
            {{-- * 搜索--}}
            {{-- */--}}
            {{--function fnSearch() {--}}
            {{--    $.ajax({--}}
            {{--        url: "{{url('search')}}",--}}
            {{--        type: "post",--}}
            {{--        data: $("#frmSearch" + this.searchType).serialize(),--}}
            {{--        async: true,--}}
            {{--        success: function (response) {--}}
            {{--            location.href = response;--}}
            {{--        },--}}
            {{--        error: function (error) {--}}
            {{--            // console.log('fail:', error);--}}
            {{--            if (error.status === 401) location.href = "{{ url('login') }}";--}}
            {{--            layer.alert(error.responseText, {icon: 2, title: '错误',});--}}
            {{--        },--}}
            {{--    });--}}
            {{--}--}}

            {{--/**--}}
            {{-- * 根据类型获取部件型号--}}
            {{-- * @param categoryUniqueCode--}}
            {{-- */--}}
            {{--function fnGetPartModelUniqueCodeByCategoryUniqueCode(categoryUniqueCode) {--}}
            {{--    $.ajax({--}}
            {{--        url: "{{url('part/model')}}",--}}
            {{--        type: "get",--}}
            {{--        data: {type: 'category_unique_code', category_unique_code: categoryUniqueCode},--}}
            {{--        async: true,--}}
            {{--        success: function (response) {--}}
            {{--            html = '<option value="">全部</option>';--}}
            {{--            for (let i = 0; i < response.length; i++) {--}}
            {{--                html += '<option value="' + response[i].unique_code + '">${response[i].name}</option>';--}}
            {{--            }--}}
            {{--            $("#selIndexPartModelUniqueCode").html(html);--}}
            {{--        },--}}
            {{--        error: function (error) {--}}
            {{--            // console.log('fail:', error);--}}
            {{--            if (error.status === 401) location.href = "{{ url('login') }}";--}}
            {{--            layer.alert(error.responseText, {icon: 2, title: '错误',});--}}
            {{--        },--}}
            {{--    });--}}
            {{--}--}}
        </script>
        @yield('script')
        </body>
    </body>
</html>
