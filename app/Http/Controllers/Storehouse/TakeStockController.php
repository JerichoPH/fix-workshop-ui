<?php

namespace App\Http\Controllers\Storehouse;

use App\Http\Controllers\Controller;
use App\Model\EntireInstance;
use App\Model\PartInstance;
use App\Model\TakeStock;
use App\Model\TakeStockInstance;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\HttpResponseHelper;
use Jericho\TextHelper;

class TakeStockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    final public function index(Request $request)
    {
        $originAt = Carbon::now()->startOfMonth()->toDateString();
        $finishAt = Carbon::now()->endOfMonth()->toDateString();
        $state = $request->get('state');
        $result = $request->get('result');
        $updated_at = $request->get('updated_at');
        $use_made_at = $request->get('use_made_at');
        $takeStocks = TakeStock::with(['WithAccount',])
            ->whereHas("WithAccount", function ($WithAccount) {
                $WithAccount->where("work_area_unique_code", session("account.work_area_unique_code"));
            })
            ->when(
                session('account.read_scope') === 1,
                function ($query) {
                    return $query->where('account_id', session('account.id'));
                }
            )
            ->when(
                !empty($state),
                function ($query) use ($state) {
                    return $query->where('state', $state);
                }
            )
            ->when(
                !empty($result),
                function ($query) use ($result) {
                    return $query->where('result', $result);
                }
            )
            ->when(
                $use_made_at == 1,
                function ($query) use ($updated_at) {
                    if (!empty($updated_at)) {
                        $tmp_updated_at = explode('~', $updated_at);
                        $tmp_left = Carbon::createFromFormat('Y-m-d', $tmp_updated_at[0])->startOfDay()->format('Y-m-d H:i:s');
                        $tmp_right = Carbon::createFromFormat('Y-m-d', $tmp_updated_at[1])->endOfDay()->format('Y-m-d H:i:s');
                        return $query->whereBetween('updated_at', [$tmp_left, $tmp_right]);
                    }
                }
            )
            ->orderBy('updated_at', 'desc')
            ->paginate();

        return view('Storehouse.TakeStock.index', [
            'states' => TakeStock::$STATE,
            'results' => TakeStock::$RESULT,
            'originAt' => $originAt,
            'finishAt' => $finishAt,
            'takeStocks' => $takeStocks,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param $takeStockUniqueCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function show($takeStockUniqueCode)
    {
        try {
            $takeStockInstances = DB::table('take_stock_instances as t')
                ->selectRaw('count(t.id) as count,t.difference,t.category_name,t.category_unique_code')
                ->where('t.take_stock_unique_code', $takeStockUniqueCode)
                ->groupBy(['t.difference', 't.category_name', 't.category_unique_code'])
                ->get()
                ->toArray();

            $takeStock = TakeStock::with([])->where('unique_code', $takeStockUniqueCode)->firstOrFail();

            return view('Storehouse.TakeStock.show', [
                'differences' => TakeStockInstance::$DIFFERENCE,
                'takeStockInstances' => TextHelper::toJson($takeStockInstances),
                'currentTakeStockUniqueCode' => $takeStockUniqueCode,
                'takeStock' => $takeStock
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', "数据不存在");
        } catch (\Exception $exception) {
            return back()->with('danger', "{$exception->getMessage()}==>{$exception->getLine()}==>{$exception->getCode()}");
        }
    }

    /**
     * 根据种类获取型号差异列表
     * @param string $takeStockUniqueCode
     * @param string $categoryUniqueCode
     * @return \Illuminate\Http\JsonResponse
     */
    final public function showWithSubModel(string $takeStockUniqueCode, string $categoryUniqueCode)
    {
        try {
            $takeStockInstances = DB::table('take_stock_instances as t')
                ->selectRaw('count(t.id) as count,t.sub_model_unique_code,t.sub_model_name,t.difference')
                ->where('t.take_stock_unique_code', $takeStockUniqueCode)
                ->where('t.category_unique_code', $categoryUniqueCode)
                ->groupBy(['t.sub_model_unique_code', 't.difference', 't.sub_model_name'])
                ->get()
                ->toArray();

            return HttpResponseHelper::data($takeStockInstances);
        } catch (\Exception $exception) {
            return HttpResponseHelper::error('错误');
        }
    }

    /**
     * 根据型号获取差异设备信息
     *
     * @param Request $request
     * @param string $takeStockUniqueCode
     * @param string $subModelUniqueCode
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    final public function showWithMaterial(Request $request, string $takeStockUniqueCode, string $subModelUniqueCode)
    {
        try {
            $difference = $request->get('difference', '');
            $takeStockInstances = TakeStockInstance::with([])
                ->where('take_stock_unique_code', $takeStockUniqueCode)
                ->where('sub_model_unique_code', $subModelUniqueCode)
                ->when(
                    !empty($difference),
                    function ($query) use ($difference) {
                        return $query->where('difference', $difference);
                    }
                )
                ->get();
            $takeStock = TakeStock::with([])->where('unique_code', $takeStockUniqueCode)->firstOrFail();

            return view('Storehouse.TakeStock.showWithMaterial', [
                'takeStockInstances' => $takeStockInstances,
                'differences' => TakeStockInstance::$DIFFERENCE,
                'currentTakeStockUniqueCode' => $takeStockUniqueCode,
                'currentSubModelUniqueCode' => $subModelUniqueCode,
                'takeStock' => $takeStock,
            ]);
        } catch (ModelNotFoundException $exception) {
            return back()->with('danger', "数据不存在");
        } catch (\Exception $exception) {
            return back()->with('danger', "{$exception->getMessage()}==>{$exception->getLine()}==>{$exception->getCode()}");
        }
    }

    /**
     * 开始盘点
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    final public function startTakeStock(Request $request)
    {
        try {
            $takeStockUniqueCode = $request->get('take_stock_unique_code', '');
            $takeStockMaterials = TakeStockInstance::with([])->where('take_stock_unique_code', $takeStockUniqueCode)->orderBy('id')->get();
            $takeStock = null;
            if (!empty($takeStockUniqueCode)) $takeStock = TakeStock::with([])->where('unique_code', $takeStockUniqueCode)->first();

            $storehouses = DB::table('storehouses')->get();
            return view('Storehouse.TakeStock.takeStock', [
                'takeStockMaterials' => $takeStockMaterials,
                'storehouses' => $storehouses,
                'takeStock' => $takeStock
            ]);
        } catch (\Exception $exception) {
            return back()->with('danger', "{$exception->getMessage()}==>{$exception->getLine()}==>{$exception->getCode()}");
        }
    }

    /**
     * 盘点准备
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function takeStockReady(Request $request)
    {
        try {
            $codes[] = $storehouse_unique_code = $request->get('storehouse_unique_code');
            $codes[] = $area_unique_code = $request->get('area_unique_code');
            $codes[] = $platoon_unique_code = $request->get('platoon_unique_code');
            $codes[] = $shelf_unique_code = $request->get('shelf_unique_code');
            $codes[] = $tier_unique_code = $request->get('tier_unique_code');
            $codes[] = $position_unique_code = $request->get('position_unique_code');
            $location_unique_code = array_last(array_filter($codes));
            $entireInstances = EntireInstance::with(['WithPosition'])
                ->when(
                    !empty($storehouse_unique_code),
                    function ($query) use ($storehouse_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse', function ($q) use ($storehouse_unique_code) {
                            $q->where('unique_code', $storehouse_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($area_unique_code),
                    function ($query) use ($area_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea', function ($q) use ($area_unique_code) {
                            $q->where('unique_code', $area_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($platoon_unique_code),
                    function ($query) use ($platoon_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon', function ($q) use ($platoon_unique_code) {
                            $q->where('unique_code', $platoon_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($shelf_unique_code),
                    function ($query) use ($shelf_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf', function ($q) use ($shelf_unique_code) {
                            $q->where('unique_code', $shelf_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($tier_unique_code),
                    function ($query) use ($tier_unique_code) {
                        return $query->whereHas('WithPosition.WithTier', function ($q) use ($tier_unique_code) {
                            $q->where('unique_code', $tier_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($position_unique_code),
                    function ($query) use ($position_unique_code) {
                        return $query->where('location_unique_code', $position_unique_code);
                    }
                )
                ->where('is_bind_location', 1)
                ->get();
            $partInstances = PartInstance::with(['PartCategory'])
                ->when(
                    !empty($storehouse_unique_code),
                    function ($query) use ($storehouse_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea.WithStorehouse', function ($q) use ($storehouse_unique_code) {
                            $q->where('unique_code', $storehouse_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($area_unique_code),
                    function ($query) use ($area_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon.WithArea', function ($q) use ($area_unique_code) {
                            $q->where('unique_code', $area_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($platoon_unique_code),
                    function ($query) use ($platoon_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf.WithPlatoon', function ($q) use ($platoon_unique_code) {
                            $q->where('unique_code', $platoon_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($shelf_unique_code),
                    function ($query) use ($shelf_unique_code) {
                        return $query->whereHas('WithPosition.WithTier.WithShelf', function ($q) use ($shelf_unique_code) {
                            $q->where('unique_code', $shelf_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($tier_unique_code),
                    function ($query) use ($tier_unique_code) {
                        return $query->whereHas('WithPosition.WithTier', function ($q) use ($tier_unique_code) {
                            $q->where('unique_code', $tier_unique_code);
                        });
                    }
                )
                ->when(
                    !empty($position_unique_code),
                    function ($query) use ($position_unique_code) {
                        return $query->where('location_unique_code', $position_unique_code);
                    }
                )
                ->where('is_bind_location', 1)
                ->get();

            if ($entireInstances->isEmpty() && $partInstances->isEmpty()) return HttpResponseHelper::errorEmpty('仓库设备为空');
            $takeStockName = '整仓';
            if (!empty($storehouse_unique_code)) {
                $storehouse = DB::table('storehouses')->where('unique_code', $storehouse_unique_code)->first();
                $takeStockName = $storehouse->name ?? '';
            }
            if (!empty($area_unique_code)) {
                $area = DB::table('areas')->where('unique_code', $area_unique_code)->first();
                $takeStockName .= $area->name ?? '';
            }
            if (!empty($platoon_unique_code)) {
                $platoon = DB::table('platoons')->where('unique_code', $platoon_unique_code)->first();
                $takeStockName .= $platoon->name ?? '';
            }
            if (!empty($shelf_unique_code)) {
                $shelf = DB::table('shelves')->where('unique_code', $shelf_unique_code)->first();
                $takeStockName .= $shelf->name ?? '';
            }
            if (!empty($tier_unique_code)) {
                $tier = DB::table('tiers')->where('unique_code', $tier_unique_code)->first();
                $takeStockName .= $tier->name ?? '';
            }
            if (!empty($position_unique_code)) {
                $position = DB::table('positions')->where('unique_code', $position_unique_code)->first();
                $takeStockName .= $position->name ?? '';
            }

            $takeStockUniqueCode = DB::transaction(function () use ($entireInstances, $partInstances, $takeStockName, $location_unique_code) {
                DB::table('take_stocks')->where('account_id', session('account.id'))->where('state', 'START')->update(['state' => 'CANCEL', 'updated_at' => date('Y-m-d H:i:s')]);
                $takeStock = new TakeStock();
                $takeStockUniqueCode = $takeStock->getUniqueCode();
                $takeStock->fill([
                    'unique_code' => $takeStockUniqueCode,
                    'state' => 'START',
                    'account_id' => session('account.id'),
                    'location_unique_code' => empty($location_unique_code) ? '' : $location_unique_code,
                    'name' => $takeStockName
                ]);
                $takeStock->save();

                $takeStockInstances = [];
                foreach ($entireInstances as $entireInstance) {
                    $takeStockInstances[] = [
                        'take_stock_unique_code' => $takeStockUniqueCode,
                        'stock_identity_code' => $entireInstance->identity_code,
                        'real_stock_identity_code' => '',
                        'difference' => '-',
                        'category_unique_code' => $entireInstance->category_unique_code ?? '',
                        'category_name' => $entireInstance->category_name ?? '',
                        'sub_model_unique_code' => $entireInstance->model_unique_code ?? '',
                        'sub_model_name' => $entireInstance->model_name ?? '',
                        'location_unique_code' => $entireInstance->location_unique_code ?? '',
                        'location_name' => empty($entireInstance->WithPosition) ? '' : $entireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $entireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $entireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $entireInstance->WithPosition->WithTier->WithShelf->name . $entireInstance->WithPosition->WithTier->name . $entireInstance->WithPosition->name,
                        'material_type' => 'ENTIRE',
                    ];
                }
                foreach ($partInstances as $partInstance) {
                    $takeStockInstances[] = [
                        'take_stock_unique_code' => $takeStockUniqueCode,
                        'stock_identity_code' => $partInstance->identity_code,
                        'real_stock_identity_code' => '',
                        'difference' => '-',
                        'category_unique_code' => $partInstance->part_category_id ?? '',
                        'category_name' => $partInstance->PartCategory->name ?? '',
                        'sub_model_unique_code' => $partInstance->part_model_unique_code ?? '',
                        'sub_model_name' => $partInstance->part_model_name ?? '',
                        'location_unique_code' => $partInstance->location_unique_code ?? '',
                        'location_name' => empty($partInstance->WithPosition) ? '' : $partInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $partInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $partInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $partInstance->WithPosition->WithTier->WithShelf->name . $partInstance->WithPosition->WithTier->name . $partInstance->WithPosition->name,
                        'material_type' => 'PART',
                    ];
                }

                DB::table('take_stock_instances')->insert($takeStockInstances);

                return $takeStockUniqueCode;
            });

            return HttpResponseHelper::data(['message' => '成功', 'take_stock_unique_code' => $takeStockUniqueCode]);
        } catch (Exception $exception) {
            return HttpResponseHelper::error($exception->getMessage(), [$exception->getMessage(), $exception->getFile(), $exception->getLine()]);
        }
    }

    /**
     * 盘点扫码保存
     * @param Request $request
     * @param $realStockIdentityCode
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    final public function takeStockMaterialStore(Request $request, $realStockIdentityCode)
    {
        try {
            $takeStockUniqueCode = $request->get('take_stock_unique_code', '');
            $takeStock = TakeStock::with([])->where('state', 'START')->where('unique_code', $takeStockUniqueCode)->select('unique_code')->first();
            if (empty($takeStock)) return HttpResponseHelper::errorEmpty('请点击开始盘点按钮');
            $takeStockInstance = TakeStockInstance::with([])->where('take_stock_unique_code', $takeStockUniqueCode)->where('stock_identity_code', $realStockIdentityCode)->first();
            if ($takeStockInstance) {
                if (empty($takeStockInstance->real_stock_identity_code)) {
                    $takeStockInstance->fill([
                        'real_stock_identity_code' => $realStockIdentityCode,
                        'difference' => '='
                    ]);
                    $takeStockInstance->saveOrFail();
                } else {
                    return HttpResponseHelper::errorValidate('重复扫码');
                }
            } else {
                $takeStockInstance = TakeStockInstance::with([])->where('take_stock_unique_code', $takeStockUniqueCode)->where('stock_identity_code', '')->where('real_stock_identity_code', $realStockIdentityCode)->first();
                if (empty($takeStockInstance)) {
                    #新录入 检测编码是否在系统中录入
                    $entireInstance = EntireInstance::with([])->where('identity_code', $realStockIdentityCode)->first();
                    $partInstance = PartInstance::with(['PartCategory'])->where('identity_code', $realStockIdentityCode)->first();
                    if (empty($entireInstance) && empty($partInstance)) return HttpResponseHelper::errorEmpty('设备不存在');
                    $takeStockInstances = [];
                    if (!empty($entireInstance)) {
                        $takeStockInstances[] = [
                            'take_stock_unique_code' => $takeStockUniqueCode,
                            'stock_identity_code' => $entireInstance->identity_code,
                            'real_stock_identity_code' => '',
                            'difference' => '-',
                            'category_unique_code' => $entireInstance->category_unique_code ?? '',
                            'category_name' => $entireInstance->category_name ?? '',
                            'sub_model_unique_code' => $entireInstance->model_unique_code ?? '',
                            'sub_model_name' => $entireInstance->model_name ?? '',
                            'location_unique_code' => $entireInstance->location_unique_code ?? '',
                            'location_name' => empty($entireInstance->WithPosition) ? '' : $entireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $entireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $entireInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $entireInstance->WithPosition->WithTier->WithShelf->name . $entireInstance->WithPosition->WithTier->name . $entireInstance->WithPosition->name,
                            'material_type' => 'ENTIRE',
                        ];
                    }
                    if (!empty($partInstance)) {
                        $takeStockInstances[] = [
                            'take_stock_unique_code' => $takeStockUniqueCode,
                            'stock_identity_code' => $partInstance->identity_code,
                            'real_stock_identity_code' => '',
                            'difference' => '-',
                            'category_unique_code' => $partInstance->part_category_id ?? '',
                            'category_name' => $partInstance->PartCategory->name ?? '',
                            'sub_model_unique_code' => $partInstance->part_model_unique_code ?? '',
                            'sub_model_name' => $partInstance->part_model_name ?? '',
                            'location_unique_code' => $partInstance->location_unique_code ?? '',
                            'location_name' => empty($partInstance->WithPosition) ? '' : $partInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->WithStorehouse->name . $partInstance->WithPosition->WithTier->WithShelf->WithPlatoon->WithArea->name . $partInstance->WithPosition->WithTier->WithShelf->WithPlatoon->name . $partInstance->WithPosition->WithTier->WithShelf->name . $partInstance->WithPosition->WithTier->name . $partInstance->WithPosition->name,
                            'material_type' => 'PART',
                        ];
                    }

                    DB::table('take_stock_instances')->insert($takeStockInstances);
                } else {
                    return HttpResponseHelper::errorValidate('重复扫码');
                }
            }

            return HttpResponseHelper::created('成功');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error('错误');
        }
    }

    /**
     * 扫码移除
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    final public function takeStockMaterialDestory($id)
    {
        try {
            $takeStockInstance = TakeStockInstance::with([])->where('id', $id)->firstOrFail();
            $stock_identity_code = $takeStockInstance->stock_identity_code;
            $real_stock_identity_code = $takeStockInstance->real_stock_identity_code;
            if (!empty($stock_identity_code) && !empty($real_stock_identity_code)) {
                $takeStockInstance->fill([
                    'real_stock_identity_code' => '',
                    'difference' => '-'
                ]);
                $takeStockInstance->saveOrFail();
            } else {
                $takeStockInstance->delete();
            }

            return HttpResponseHelper::created('成功');
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('数据不存在');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error('错误');
        }
    }

    /**
     * 确认盘点
     * @param string $takeStockUniqueCode
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    final public function takeStock(string $takeStockUniqueCode)
    {
        try {
            $takeStock = TakeStock::with([])->where('unique_code', $takeStockUniqueCode)->firstOrFail();
            $result = 'YESDIF';
            $stock_diff = TakeStockInstance::with([])->where('take_stock_unique_code', $takeStockUniqueCode)->where('difference', '-')->count();
            $real_stock_diff = TakeStockInstance::with([])->where('take_stock_unique_code', $takeStockUniqueCode)->where('difference', '+')->count();
            if ($stock_diff == 0 && $real_stock_diff == 0) $result = 'NODIF';
            $takeStock->fill([
                'result' => $result,
                'state' => 'END',
                'stock_diff' => $stock_diff,
                'real_stock_diff' => $real_stock_diff
            ]);
            $takeStock->saveOrFail();
            # 修改设备最后盘点时间
            $takeStockMaterials = TakeStockInstance::with([])->where('take_stock_unique_code', $takeStockUniqueCode)->select('stock_identity_code', 'real_stock_identity_code', 'material_type')->get();
            foreach ($takeStockMaterials as $takeStockMaterial) {
                if ($takeStockMaterial->material_type == 'ENTIRE') {
                    $identity_codes = [];
                    if (!empty($takeStockMaterial->stock_identity_code)) $identity_codes[] = $takeStockMaterial->stock_identity_code;
                    if (!empty($takeStockMaterial->real_stock_identity_code)) $identity_codes[] = $takeStockMaterial->real_stock_identity_code;
                    DB::table('entire_instances')->whereIn('identity_code', array_unique($identity_codes))->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                        'last_take_stock_at' => date('Y-m-d H:i:s')
                    ]);
                }
                if ($takeStockMaterial->material_type == 'PART') {
                    $identity_codes = [];
                    if (!empty($takeStockMaterial->stock_identity_code)) $identity_codes[] = $takeStockMaterial->stock_identity_code;
                    if (!empty($takeStockMaterial->real_stock_identity_code)) $identity_codes[] = $takeStockMaterial->real_stock_identity_code;
                    DB::table('part_instances')->whereIn('identity_code', array_unique($identity_codes))->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                        'last_take_stock_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            return HttpResponseHelper::created('成功');
        } catch (ModelNotFoundException $exception) {
            return HttpResponseHelper::errorEmpty('数据不存在');
        } catch (\Exception $exception) {
            return HttpResponseHelper::error('错误');
        }
    }

}
