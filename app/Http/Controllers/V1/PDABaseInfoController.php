<?php

namespace App\Http\Controllers\V1;

use App\Facades\DingResponseFacade;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Jericho\HttpResponseHelper;
use Jericho\TextHelper;

class PDABaseInfoController extends Controller
{
    use Helpers;

    final private function getAccounts(): array
    {
        return TextHelper::parseJson(TextHelper::toJson(DB::table('accounts')
            ->where('deleted_at', null)
            ->where('workshop_code', env('ORGANIZATION_CODE'))
            ->get([
                'nickname',
//                'supervision',
//                'work_area',
//                'read_scope',
//                'write_scope',
                'password',
                'account'
            ])));
    }

    final private function getMaintains(): array
    {
        $maintains = [];
        foreach (DB::table('maintains as m')
                     ->select([
                         'm.name',
                         'm2.name as parent_name',
                         'm2.unique_code',
                     ])
                     ->where('m.deleted_at', null)
                     ->leftJoin(DB::raw('maintains m2'), 'm2.unique_code', '=', 'm.parent_unique_code')
                     ->where('m2.parent_unique_code', env('ORGANIZATION_CODE'))
                     ->get() as $maintain) {
            $maintains[$maintain->parent_name][] = $maintain->name;
        }

//        foreach (DB::select("select mt.name, mt2.name as parent_name, mt2.unique_code
//from maintains mt
//         left join maintains mt2 on mt2.unique_code = mt.parent_unique_code
//where mt2.parent_unique_code = '" . env('ORGANIZATION_CODE') . "'") as $maintain) {
//            $maintains[$maintain->parent_name][] = $maintain->name;
//        }
        $maintainWithUniqueCode = [];
        foreach ($maintains as $key => $value) {
            $uniqueCode = DB::table('maintains')->where('name', $key)->first(['unique_code'])->unique_code;
            $v = [];
            foreach ($value as $stationName) $v[] = ['name' => $stationName, 'crossroad' => ['道岔1', '道岔2', '道岔3', '道岔4']];
            $maintainWithUniqueCode[$uniqueCode] = ['scene_workshop' => $key, 'stations' => $v];
        }
        $maintainWithArray = [];
        foreach ($maintainWithUniqueCode as $key => $item) $maintainWithArray[] = $item;

        return TextHelper::parseJson(TextHelper::toJson($maintainWithArray));
    }

    /**
     * 更新手持终端基础信息
     * @return \Illuminate\Http\JsonResponse
     */
    final public function index()
    {
        try {
            $accounts = $this->getAccounts();
            $maintainWithArray = $this->getMaintains();

//            # 保存用户列表缓存
//            file_put_contents(storage_path('pda/accounts.json'), TextHelper::toJson($accounts));
//            # 保存站场列表缓存
//            file_put_contents(storage_path('pda/maintains.json'), TextHelper::toJson($maintainWithArray));

            return HttpResponseHelper::data([
                'accounts' => $accounts,
                'stations' => $maintainWithArray,
            ]);
        } catch (\Exception $exception) {
            $msg = $exception->getMessage();
            $file = $exception->getFile();
            $line = $exception->getLine();
            return HttpResponseHelper::error("{$msg}\r\n{$file}\r\n{$line}");
        }
    }

    /**
     * 检查是否需要更新
     * @return \Illuminate\Http\JsonResponse
     */
    final public function isNeedUpload()
    {
        return HttpResponseHelper::created('1');
        try {
            $accounts = [];
            $maintainWithArray = [];

            # 保存用户列表缓存
            if (is_file(storage_path('pda/accounts.json'))) $accounts = file_get_contents(storage_path('pda/accounts.json'));
            # 保存站场列表缓存
            if (is_file(storage_path('pda/maintains.json'))) $maintainWithArray = file_get_contents(storage_path('pda/maintains.json'));

            if (($accounts == TextHelper::toJson($this->getAccounts())) && $this->getMaintains()) {
                return HttpResponseHelper::created('0');
            } else {
                return HttpResponseHelper::created('1');
            }
        } catch (\Exception $exception) {
            $msg = $exception->getMessage();
            $file = $exception->getFile();
            $line = $exception->getLine();
            return HttpResponseHelper::error("{$msg}\r\n{$file}\r\n{$line}");
        }
    }
}
