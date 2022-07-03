<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use App\Model\EntireInstance;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Jericho\HttpResponseHelper;
use Milon\Barcode\DNS1D;
use Throwable;

class BarCodeController extends Controller
{
    /**
     * 保存打印标签的编码
     * @param Request $request
     * @return mixed
     */
    final public function postPrintSerialNumber(Request $request)
    {
        try {
            $identity_codes = $request->get('identity_codes');
            if (empty($identity_codes)) return JsonResponseFacade::errorEmpty('编码不存在');

            if (!session('account.id')) return JsonResponseFacade::errorForbidden('用户不存在，请重新登录');
            if (!session('account.work_area_unique_code')) return JsonResponseFacade::errorForbidden('当前用户没有所属工区');

            $entire_instances = collect([]);
            DB::table('entire_instances as ei')
                ->select(['identity_code', 'serial_number', 'model_name',])
                ->whereNull('ei.deleted_at')
                ->where('work_area_unique_code', session('account.work_area_unique_code'))
                ->whereIn('ei.identity_code', $identity_codes)
                ->get()
                ->each(function ($entire_instance) use (&$entire_instances) {
                    $entire_instances[$entire_instance->identity_code] = $entire_instance;
                });

            $diff = [];
            $diff = array_diff($entire_instances->keys()->toArray(), $identity_codes);
            if ($diff) return JsonResponseFacade::errorForbidden("以下设备器材没有找到：<br>" . implode('<br>', $diff));

            $account_id = session('account.id');
            DB::beginTransaction();
            foreach ($identity_codes as $identity_code) {
                $entire_instance = DB::table('entire_instances as ei')->select(['ei.serial_number', 'ei.model_name'])->where('ei.identity_code', $identity_code)->first();
                if ($entire_instance) {
                    if (@$entire_instance->serial_number) {
                        DB::table('print_serial_numbers')
                            ->insert([
                                'created_at' => now(),
                                'updated_at' => now(),
                                'serial_number' => $entire_instance->serial_number,
                                'account_id' => session('account.id'),
                                'model_name' => @$entire_instance->model_name ?: '',
                            ]);
                    }
                }
            }
            DB::commit();

            return JsonResponseFacade::ok();
        } catch (Exception $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 打印所编号条形码
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|RedirectResponse|\Illuminate\View\View
     */
    final public function getPrintSerialNumber()
    {
        try {
            $print_serial_numbers = DB::table('print_serial_numbers as psn')
                ->select(['serial_number', 'model_name'])
                ->where('psn.account_id', session('account.id'))
                ->orderBy('psn.id')
                ->get();

            DB::table('print_serial_numbers')
                ->where('account_id', session('account.id'))
                ->delete();

            if (!DB::table('print_serial_numbers')->where('id', '>', 0)->exists()) {
                DB::table('print_serial_numbers')->truncate();
            }

            return view('BarCode.printSerialNumber', [
                'print_serial_numbers' => $print_serial_numbers,
            ]);
        } catch (ModelNotFoundException $e) {
            return back()->with('danger', '数据不存在');
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    public function show($entireInstanceIdentityCode)
    {
        $entireInstance = EntireInstance::where('identity_code', $entireInstanceIdentityCode)->firstOrFail();
        $barcode = new DNS1D();
        return view($this->view())
            ->with('serialNumber', date('Y-m') . $entireInstance->entire_model_unique_code)
            ->with('entireInstanceIdentityCode', $entireInstanceIdentityCode)
            ->with('entireInstance', $entireInstance)
            ->with('barcode', $barcode);
    }

    private function view($viewName = null)
    {
        $viewName = $viewName ?: request()->route()->getActionMethod();
        return "BarCode.{$viewName}";
    }

    /**
     * 解析条形码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function parse(Request $request)
    {
        $entireInstance = DB::table('entire_instances')->where('rfid_code', $request->serial_number)->first(['identity_code']);
        if (!$entireInstance) return response()->make('数据不存在', 404);
//        return response()->json($identityCode);
        try {
            switch (request()->type) {
                case 'scan':
                    return Response::json([
                        'type' => 'redirect',
                        'url' => url('search', $entireInstance->identity_code)
                    ]);
                case 'buy_in':
                    break;
                case 'fixing':
                    break;
                case 'return_factory':
                    break;
                case 'factory_return':
                    break;
            }
        } catch (ModelNotFoundException $exception) {
            return Response::make('数据不存在', 404);
        } catch (\Exception $exception) {
            return Response::make('意外错误', 500);
        }
    }
}
