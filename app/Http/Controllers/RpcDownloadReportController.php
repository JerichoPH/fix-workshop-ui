<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Chumper\Zipper\Facades\Zipper;

class RpcDownloadReportController extends Controller
{
    private $_organizationCode = null;
    private $_organizationName = null;

    public function __construct()
    {
        $this->_organizationCode = env('ORGANIZATION_CODE');
        $this->_organizationName = env('ORGANIZATION_NAME');
    }

    /**
     * 下载周期修报表
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
    public function planAndFinish()
    {
        if (request('date')) {
            list($year, $month) = explode('-', request('date'));
        } else {
            $year = strval(Carbon::now()->year);
        }

        $fileDir = storage_path("app/周期修计划和完成情况/{$year}");

        if (is_dir($fileDir)) {
            $zipName = "{$this->_organizationCode}{$this->_organizationName}{$year}年种类统计.zip";
            Zipper::make(public_path($zipName))->add("{$fileDir}/{$this->_organizationCode}{$this->_organizationName}{$year}年种类统计.xlsx")->close();
            return redirect(url("/{$zipName}"));
        }

        return response()->make("没有对应的报表：{$year} 周期修统计（种类）");
    }

    public function planAndFinishWithCategory()
    {
        if (request('date')) {
            list($year, $month) = explode('-', request('date'));
        } else {
            $carbon = Carbon::now();
            $year = strval($carbon->year);
            $month = str_pad($carbon->month, 2, '0', STR_PAD_LEFT);
        }
        $fileDir = storage_path("app/周期修计划和完成情况");

        if (is_dir($fileDir)) {
            $zipName = "{$this->_organizationCode}{$this->_organizationName}{$year}年类型统计.zip";
            Zipper::make(public_path($zipName))->add("{$fileDir}/{$year}/{$this->_organizationCode}{$this->_organizationName}{$year}年类型统计.xlsx")->close();
            return redirect(url("/{$zipName}"));
        }

        return response()->make("没有对应的报表：{$year} 周期修统计（类型）");
    }

    public function planAndFinishWithEntireModel()
    {
        if (request('date')) {
            list($year, $month) = explode('-', request('date'));
        } else {
            $carbon = Carbon::now();
            $year = strval($carbon->year);
            $month = str_pad($carbon->month, 2, '0', 0);
        }
        $fileDir = storage_path("app/周期修计划和完成情况");

        if (is_dir($fileDir)) {
            $zipName = "{$this->_organizationCode}{$this->_organizationName}{$year}年型号和子类统计.zip";
            Zipper::make(public_path($zipName))->add("{$fileDir}/{$year}/{$this->_organizationCode}{$this->_organizationName}{$year}年型号和子类统计.xlsx")->close();
            return redirect(url("/{$zipName}"));
        }

        return response()->make("没有对应的报表：{$year} 周期修统计（型号和子类）");
    }


    public function quality()
    {
        if (request()->has('qualityDate')) {
            $year = request('qualityDate');
            $now = Carbon::create($year, 1, 1);
            $originAt = $now->format('Y-m-d');
            $finishAt = $now->endOfYear()->format('Y-m-d');
        } else {
            $now = Carbon::now();
            $year = $now->year;
            $originAt = $now->format('Y-m-d');
            $finishAt = $now->endOfYear()->format('Y-m-d');
        }

        $fileDir = storage_path("app/质量报告");

        if (is_dir($fileDir)) {
            $zipName = "{$this->_organizationCode}{$this->_organizationName}{$year}年质量报告.zip";
            Zipper::make(public_path($zipName))->add("{$fileDir}/{$year}/{$this->_organizationCode}{$this->_organizationName}{$year}年质量报告.xlsx")->close();
            return redirect(url("/{$zipName}"));
        }

        return response()->make("没有对应的报表：{$year} 质量报告");
    }

    public function scraped()
    {
        if (is_dir(storage_path('app/超期使用'))) {
            $zipName = "{$this->_organizationCode}{$this->_organizationName}超期使用.zip";
            Zipper::make(public_path($zipName))->add(storage_path("app/超期使用/{$this->_organizationCode}{$this->_organizationName}超期使用.xlsx"))->close();
            return redirect(url("/{$zipName}"));
        }

        return response()->make("没有对应的报表：超期使用");
    }
}
