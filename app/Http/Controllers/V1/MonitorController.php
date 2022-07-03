<?php

namespace App\Http\Controllers\V1;

use App\Facades\JsonResponseFacade;
use App\Facades\ModelBuilderFacade;
use App\Model\Maintain;
use Curl\Curl;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MonitorController extends Controller
{
    private $_e_url = null;
    private $_curl = null;
    private $_current_time = null;
    private $_workshop_unique_code = null;
    private $_station_unique_code = null;
    private $_workshop = null;
    private $_station = null;
    private $_e_switch = false;

    public function __construct(Request $request)
    {
        $this->_curl = new Curl();
        $this->_e_url = env('MONITOR_API_FOR_E_WORKSHOP_URL', '');
        $this->_e_switch = env('MONITOR_API_FOR_E_WORKSHOP_SWITCH', false);
        $this->_current_time = date('Y-m-d H:i:s');
        $this->_workshop_unique_code = $request->get('workshop_unique_code');
        $this->_station_unique_code = $request->get('station_unique_code');
        if ($this->_workshop_unique_code) {
            $this->_workshop = Maintain::with([])->where('unique_code', $this->_workshop_unique_code)->first();
            if (!$this->_workshop) return JsonResponseFacade::errorEmpty('车间没有找到');
        }
        if ($this->_station_unique_code) {
            $this->_station = Maintain::with([])->where('unique_code', $this->_station_unique_code)->first();
            if (!$this->_station) return JsonResponseFacade::errorEmpty('车站没有找到');
        }
    }

    /**
     * 基础数据
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getBasicInfo(): JsonResponse
    {
        try {
            // 获取检修车间数据
            $kinds_Q = DB::table('entire_models as sm')
                ->select([
                    'c.name as category_name',
                    'c.unique_code as category_unique_code',
                    'em.name as entire_model_name',
                    'em.unique_code as entire_model_unique_code',
                    'sm.name as sub_model_name',
                    'sm.unique_code as sub_model_unique_code',
                ])
                ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'sm.parent_unique_code')
                ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->where('sm.is_sub_model', true)
                ->where('em.is_sub_model', false)
                ->where('sm.deleted_at', null)
                ->where('em.deleted_at', null)
                ->where('c.deleted_at', null);

            $kinds_S = DB::table('part_models as pm')
                ->select([
                    'c.name as category_name',
                    'c.unique_code as category_unique_code',
                    'em.name as entire_model_name',
                    'em.unique_code as entire_model_unique_code',
                    'pm.name as sub_model_name',
                    'pm.unique_code as sub_model_unique_code',
                ])
                ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
                ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
                ->where('em.is_sub_model', false)
                ->where('pm.deleted_at', null)
                ->where('em.deleted_at', null)
                ->where('c.deleted_at', null);
            $a = ModelBuilderFacade::unionAll($kinds_S, $kinds_Q)->get()->toArray();

            $kinds = [];
            foreach ($a as $kind_q) {
                if (!array_key_exists($kind_q->category_unique_code, $kinds)) {
                    $kinds[$kind_q->category_unique_code] = [
                        'name' => $kind_q->category_name,
                        'unique_code' => $kind_q->category_unique_code,
                        'subs' => [],
                    ];
                }
                if (!array_key_exists($kind_q->entire_model_unique_code, $kinds[$kind_q->category_unique_code]['subs'])) {
                    $kinds[$kind_q->category_unique_code]['subs'][$kind_q->entire_model_unique_code] = [
                        'name' => $kind_q->entire_model_name,
                        'unique_code' => $kind_q->entire_model_unique_code,
                        'subs' => [],
                    ];
                }
                if (!array_key_exists($kind_q->sub_model_unique_code, $kinds[$kind_q->category_unique_code]['subs'][$kind_q->entire_model_unique_code]['subs'])) {
                    $kinds[$kind_q->category_unique_code]['subs'][$kind_q->entire_model_unique_code]['subs'][] = [
                        'name' => $kind_q->sub_model_name,
                        'unique_code' => $kind_q->sub_model_unique_code,
                    ];
                }
            }

            $kinds2 = array_values($kinds);
            foreach ($kinds2 as $key => $item) $kinds2[$key]['subs'] = array_values($item['subs']);

            $statuses = [
                ['name' => '入所', 'unique_code' => 'FIXING'],
                ['name' => '车间备品', 'unique_code' => 'FIXED'],
                ['name' => '上道', 'unique_code' => 'INSTALLED'],
                ['name' => '下道', 'unique_code' => 'UNINSTALLED'],
                ['name' => '现场备品', 'unique_code' => 'INSTALLING'],
                ['name' => '送修', 'unique_code' => 'SEND_REPAIR'],
            ];

            $workshopPoints = Maintain::with([])
                ->where('is_show', true)
                ->where('type', 'SCENE_WORKSHOP')
                ->where(function ($query) {
                    $query->where('lon', '<>', '')
                        ->where('lon', '<>', null);
                })
                ->where(function ($query) {
                    $query->where('lat', '<>', '')
                        ->where('lat', '<>', null);
                })
                ->get()
                ->toArray();
            $stationPoints = Maintain::with([])
                ->where('type', 'STATION')
                ->where(function ($query) {
                    $query->where('lon', '<>', '')
                        ->where('lon', '<>', null);
                })
                ->where(function ($query) {
                    $query->where('lat', '<>', '')
                        ->where('lat', '<>', null);
                })
                ->get()
                ->toArray();
            $maintains = Maintain::with(['Subs'])
                ->where('parent_unique_code',env('ORGANIZATION_CODE'))
                ->where('type', 'SCENE_WORKSHOP')
                ->get()
                ->toArray();

            if ($this->_e_switch) {
                // 获取电子车间数据
                $this->_curl->get("{$this->_e_url}/basicInfo");
                if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg ?? '数据异常', $this->_curl->response);
                $el_data = $this->_curl->response->data;
                // 合并数据
                $el_data->statuses = array_merge((array)$el_data->statuses, $statuses);
                $el_data->kinds = array_merge($el_data->kinds, $kinds2);
                return JsonResponseFacade::data([
                    'statuses' => $el_data->statuses,
                    'kinds' => $el_data->kinds,
                    'workshopPoints' => $el_data->workshopPoints,
                    'stationPoints' => $el_data->stationPoints,
                    'maintains' => $el_data->maintains,
                ]);
            }

            return JsonResponseFacade::data([
                'statuses' => $statuses,
                'kinds' => $kinds2,
                'workshopPoints' => $workshopPoints,
                'stationPoints' => $stationPoints,
                'maintains' => $maintains,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 设备状态统计（左上）
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getStatus(): JsonResponse
    {
        try {
            // 获取检修车间数据
            $material_statuses = [
                'FIXING' => '入所',
                'FIXED' => '车间备品',
                'INSTALLED' => '上道',
                'UNINSTALLED' => '下道',
                'INSTALLING' => '现场备品',
                'SEND_REPAIR' => '送修',
            ];

            $statistics_Q = $this->__joinQ(
                DB::table('entire_instances as ei')
                    ->selectRaw('distinct count(ei.id) as count, ei.status')
            )
                ->whereIn('ei.status', array_keys($material_statuses))
                ->when(
                    $this->_workshop_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_workshop_name', $this->_workshop->name);
                    }
                )
                ->when(
                    $this->_station_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_station_name', $this->_station->name);
                    }
                )
                ->groupBy(['ei.status']);

            $statistics_S = $this->__joinS(
                DB::table('entire_instances as ei')
                    ->selectRaw('distinct count(ei.id) as count, ei.status')
            )
                ->whereIn('ei.status', array_keys($material_statuses))
                ->when(
                    $this->_workshop_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_workshop_name', $this->_workshop->name);
                    }
                )
                ->when(
                    $this->_station_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_station_name', $this->_station->name);
                    }
                )
                ->groupBy(['ei.status']);

            $Q = $statistics_Q->pluck('count', 'status')->toArray();
            $S = $statistics_S->pluck('count', 'status')->toArray();

            $x = [];
            $statistics = [];
            foreach ($material_statuses as $unique_code => $name) {
                $x[] = $unique_code;
                $statistics[] = ($Q[$unique_code] ?? 0) + ($S[$unique_code] ?? 0);
            }

            if ($this->_e_switch) {
                // 获取电子车间数据
                $this->_curl->get("{$this->_e_url}/status", ['workshop_unique_code' => request('workshop_unique_code')]);
                if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg ?? '数据异常', $this->_curl->response);
                $el_data = $this->_curl->response->data;
                // 合并数据
                if (@$el_data->statistics) {
                    foreach ($statistics as $key => &$statistic) {
                        $statistic += $el_data->statistics[$key] ?? 0;
                    }
                }
            }

            return JsonResponseFacade::data([
                'x' => $x,
                'statistics' => $statistics,
            ]);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    final private function __joinQ(Builder $builder): Builder
    {
        return $builder
            ->join(DB::raw('entire_models as sm'), 'sm.unique_code', '=', 'ei.model_unique_code')
            ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'sm.parent_unique_code')
            ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->where('ei.deleted_at', null)
            ->where('sm.deleted_at', null)
            ->where('em.deleted_at', null)
            ->where('c.deleted_at', null)
            ->where('sm.is_sub_model', true)
            ->where('em.is_sub_model', false);
    }

    final private function __joinS(Builder $builder): Builder
    {
        return $builder
            ->join(DB::raw('part_models as pm'), 'pm.unique_code', '=', 'ei.model_unique_code')
            ->join(DB::raw('entire_models as em'), 'em.unique_code', '=', 'pm.entire_model_unique_code')
            ->join(DB::raw('categories as c'), 'c.unique_code', '=', 'em.category_unique_code')
            ->where('ei.deleted_at', null)
            ->where('pm.deleted_at', null)
            ->where('em.deleted_at', null)
            ->where('c.deleted_at', null)
            ->where('em.is_sub_model', false);
    }

    /**
     * 资产统计（左中）
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getProperty(): JsonResponse
    {
        try {
            // 获取检修车间数据
            $statistics_Q = $this->__joinQ(
                DB::table('entire_instances as ei')
                    ->selectRaw('distinct count(ei.id) as count , c.name as category_name , c.unique_code as category_unique_code')
            )
                ->when(
                    $this->_workshop_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_workshop_name', $this->_workshop->name);
                    }
                )
                ->when(
                    $this->_station_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_station_name', $this->_station->name);
                    }
                );

            $statistics_S = $this->__joinS(
                DB::table('entire_instances as ei')
                    ->selectRaw('distinct count(ei.id) as count , c.name as category_name , c.unique_code as category_unique_code')
            )
                ->when(
                    $this->_workshop_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_workshop_name', $this->_workshop->name);
                    }
                )
                ->when(
                    $this->_station_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_station_name', $this->_station->name);
                    }
                );

            $Q = $statistics_Q->groupBy(['c.name', 'c.unique_code'])->get()->pluck('count', 'category_name')->toArray();
            $S = $statistics_S->groupBy(['c.name', 'c.unique_code'])->get()->pluck('count', 'category_name')->toArray();

            $x = array_unique(array_merge(array_keys($Q), array_keys($S)));
            $statistics = [];
            foreach ($x as $n) $statistics[] = ($Q[$n] ?? 0) + ($S[$n] ?? 0);

            if ($this->_e_switch) {
                // 获取电子车间数据
                $this->_curl->get("{$this->_e_url}/property", ['workshop_unique_code' => request('workshop_unique_code')]);
                if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg ?? '数据异常', $this->_curl->response);
                $el_data = $this->_curl->response->data;
                // 组合数据
                $x = array_merge($x, $el_data->x);
                $statistics = array_merge($statistics, $el_data->statistics);
            }

            return JsonResponseFacade::data([
                'x' => $x,
                'statistics' => $statistics,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 现场备品统计（左下）
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getInstalling(): JsonResponse
    {
        try {
            // 获取检修车间数据
            $statistics_Q = $this->__joinQ(
                DB::table('entire_instances as ei')
                    ->selectRaw('distinct count(ei.id) as count , c.name as category_name , c.unique_code as category_unique_code')
            )
                ->where('ei.status', 'INSTALLING')
                ->when(
                    $this->_workshop,
                    function ($query) {
                        $query->where('ei.maintain_workshop_name', $this->_workshop->name);
                    }
                )
                ->when(
                    $this->_station,
                    function ($query) {
                        $query->where('ei.maintain_station_name', $this->_station->name);
                    }
                );
            $statistics_S = $this->__joinS(
                DB::table('entire_instances as ei')
                    ->selectRaw('distinct count(ei.id) as count , c.name as category_name , c.unique_code as category_unique_code')
            )
                ->where('ei.status', 'INSTALLING')
                ->when(
                    $this->_workshop,
                    function ($query) {
                        $query->where('ei.maintain_workshop_name', $this->_workshop->name);
                    }
                )
                ->when(
                    $this->_station,
                    function ($query) {
                        $query->where('ei.maintain_station_name', $this->_station->name);
                    }
                );
            $S = $statistics_Q->groupBy(['category_name', 'category_unique_code'])->get()->toArray();
            $Q = $statistics_S->groupBy(['category_name', 'category_unique_code'])->get()->toArray();

            $x = array_merge(array_column($S, 'category_name'), array_column($Q, 'category_name'));
            $statistics = array_merge(array_column($S, 'count'), array_column($Q, 'count'));

            if ($this->_e_switch) {
                // 获取电子车间数据
                $this->_curl->get("{$this->_e_url}/installing", ['workshop_unique_code' => request('workshop_unique_code')]);
                if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg ?? '数据异常', $this->_curl->response);
                $el_data = $this->_curl->response->data;
                // 组合数据
                $x = array_merge($x, $el_data->x);
                $statistics = array_merge($statistics, $el_data->statistics);
            }

            return JsonResponseFacade::data([
                'x' => $x,
                'statistics' => $statistics,
            ]);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 故障统计（右上）
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getBreakdown(): JsonResponse
    {
        try {
            // 获取检修车间数据
            $breakdown_S = $this->__joinS(
                DB::table('breakdown_logs as bl')
                    ->join(DB::raw('entire_instances ei'), 'bl.entire_instance_identity_code', '=', 'ei.identity_code')
            );
            $breakdown_Q = $this->__joinQ(
                DB::table('breakdown_logs as bl')
                    ->join(DB::raw('entire_instances ei'), 'bl.entire_instance_identity_code', '=', 'ei.identity_code')
            );

            if (!$this->_workshop && !$this->_station) {
                $breakdown_S->selectRaw('count(*) as count, sw.name as name')
                    ->join(DB::raw('maintains sw'), 'sw.name', '=', 'ei.maintain_workshop_name')
                    ->where('ei.maintain_workshop_name', '<>', '')
                    ->groupBy('name');
                $breakdown_Q->selectRaw('count(*) as count, sw.name as name')
                    ->join(DB::raw('maintains sw'), 'sw.name', '=', 'ei.maintain_workshop_name')
                    ->where('ei.maintain_workshop_name', '<>', '')
                    ->groupBy('sw.name');
            } elseif ($this->_workshop && !$this->_station) {
                $breakdown_S->selectRaw('count(*) as count, s.name as name')
                    ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->where('ei.maintain_workshop_name', $this->_workshop->name)
                    ->groupBy('s.name');
                $breakdown_Q->selectRaw('count(*) as count, s.name as name')
                    ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->where('ei.maintain_workshop_name', $this->_workshop->name)
                    ->groupBy('s.name');
            } elseif ($this->_workshop && $this->_station) {
                $breakdown_S->selectRaw('count(*) as count, c.name as name')
                    ->where('ei.maintain_station_name', $this->_station->name)
                    ->groupBy('c.name');
                $breakdown_Q->selectRaw('count(*) as count, c.name as name')
                    ->where('ei.maintain_station_name', $this->_station->name)
                    ->groupBy('c.name');
            }

            $S = $breakdown_S->get()->toArray();
            $Q = $breakdown_Q->get()->toArray();

            $x = array_merge(array_column($Q, 'name'), array_column($Q, 'name'));
            $statistics = array_merge(array_column($Q, 'count'), array_column($S, 'count'));

            if ($this->_e_switch) {
                // 获取电子车间数据
                $this->_curl->get("{$this->_e_url}/installing", ['workshop_unique_code' => request('workshop_unique_code')]);
                if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg ?? '数据异常', $this->_curl->response);
                $el_data = $this->_curl->response->data;
                // 组合数据
                $x = array_merge($x, $el_data->x);
                $statistics = array_merge($statistics, $el_data->statistics);
            }

            return JsonResponseFacade::data([
                'x' => $x,
                'statistics' => $statistics,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $e) {
            return JsonResponseFacade::errorException($e);
        }
    }

    /**
     * 超期统计（右中）
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getScraped(): JsonResponse
    {
        try {
            // 获取检修车间数据
            $statistics_S = $this->__joinS(
                DB::table('entire_instances as ei')
                    ->selectRaw('distinct count(ei.id) as count , c.name as category_name , c.unique_code as category_unique_code')
            )
                ->when(
                    $this->_workshop_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_workshop_name', $this->_workshop->name);
                    }
                )
                ->when(
                    $this->_station_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_station_name', $this->_station->name);
                    }
                )
                ->groupBy(['c.name', 'c.unique_code'])
                ->get()
                ->toArray();
            $statistics_Q = $this->__joinQ(
                DB::table('entire_instances as ei')
                    ->selectRaw('distinct count(ei.id) as count , c.name as category_name , c.unique_code as category_unique_code')
            )
                ->when(
                    $this->_workshop_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_workshop_name', $this->_workshop->name);
                    }
                )
                ->when(
                    $this->_station_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_station_name', $this->_station->name);
                    }
                )
                ->groupBy(['c.name', 'c.unique_code'])
                ->get()
                ->toArray();

            $overdue_S = $this->__joinS(
                DB::table('entire_instances as ei')
                    ->selectRaw('distinct count(ei.id) as count , c.name as category_name , c.unique_code as category_unique_code')
            )
                ->where('ei.scarping_at', '<', $this->_current_time)
                ->when(
                    $this->_workshop_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_workshop_name', $this->_workshop->name);
                    }
                )
                ->when(
                    $this->_station_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_station_name', $this->_station->name);
                    }
                )
                ->groupBy(['c.name', 'c.unique_code'])
                ->pluck('count', 'category_name')
                ->toArray();
            $overdue_Q = $this->__joinQ(
                DB::table('entire_instances as ei')
                    ->selectRaw('distinct count(ei.id) as count , c.name as category_name , c.unique_code as category_unique_code')
            )
                ->where('ei.scarping_at', '<', $this->_current_time)
                ->when(
                    $this->_workshop_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_workshop_name', $this->_workshop->name);
                    }
                )
                ->when(
                    $this->_station_unique_code,
                    function ($query) {
                        $query->where('ei.maintain_station_name', $this->_station->name);
                    }
                )
                ->groupBy(['c.name', 'c.unique_code'])
                ->pluck('count', 'category_name')
                ->toArray();

            $x = array_merge(array_column($statistics_S, 'category_name'), array_column($statistics_Q, 'category_name'));
            $statistics = array_merge(array_column($statistics_S, 'count'), array_column($statistics_Q, 'count'));
            $overdueStatistics = [];
            foreach ($x as $cn) $overdueStatistics[] = ($overdue_Q[$cn] ?? 0) + ($overdue_S[$cn] ?? 0);

            if ($this->_e_switch) {
                // 获取电子车间数据
                $this->_curl->get("{$this->_e_url}/scraped", ['workshop_unique_code' => request('workshop_unique_code')]);
                if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg ?? '数据异常', $this->_curl->response);
                $el_data = $this->_curl->response->data;
                // 组合数据
                $x = array_merge($x, $el_data->x);
                $statistics = array_merge($statistics, $el_data->statistics);
                $overdueStatistics = array_merge($overdueStatistics, $el_data->overdue_statistics);
            }

            return JsonResponseFacade::data([
                'x' => $x,
                'statistics' => $statistics,
                'overdue_statistics' => $overdueStatistics,
            ]);
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 台账（右下）
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    final public function getMaintain(): JsonResponse
    {
        try {
            $el_data2 = [];
            # 没有选择车间车站
            if (empty($this->_workshop_unique_code) && empty($this->_station_unique_code)) {
                $workshops = DB::table('maintains as sw')
                    ->where('is_show', true)
                    ->select(['name', 'unique_code'])
                    ->where('type', 'SCENE_WORKSHOP')
                    ->get()
                    ->toArray();

                $statistics_S = $this->__joinS(
                    DB::table('entire_instances as ei')
                        ->selectRaw('count(ei.id) as count, sw.unique_code as workshop_unique_code')
                        ->join(DB::raw('maintains as sw'), 'sw.name', '=', 'ei.maintain_workshop_name')
                )->groupBy(['workshop_unique_code'])->pluck('count', 'workshop_unique_code')->toArray();
                $statistics_Q = $this->__joinQ(
                    DB::table('entire_instances as ei')
                        ->selectRaw('count(ei.id) as count, sw.unique_code as workshop_unique_code')
                        ->join(DB::raw('maintains as sw'), 'sw.name', '=', 'ei.maintain_workshop_name')
                )->groupBy(['workshop_unique_code'])->pluck('count', 'workshop_unique_code')->toArray();

                if ($this->_e_switch) {
                    // 获取电子车间数据
                    $this->_curl->get("{$this->_e_url}/maintain", ['workshop_unique_code' => request('workshop_unique_code')]);
                    if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg ?? '数据异常', $this->_curl->response);
                    $el_data = $this->_curl->response->data;
                    foreach ($el_data as $el_datum) $el_data2[$el_datum->unique_code] = $el_datum;
                    // 合并数据
                    foreach ($workshops as $workshop) {
                        if (array_key_exists($workshop->unique_code, $el_data2)) {
                            $el_data2[$workshop->unique_code]->count += ($statistics_S[$workshop->unique_code] ?? 0) + ($statistics_Q[$workshop->unique_code] ?? 0);
                        } else {
                            $el_data2[$workshop->unique_code] = [
                                'name' => $workshop->name,
                                'unique_code' => $workshop->unique_code,
                                'count' => ($statistics_S[$workshop->unique_code] ?? 0) + ($statistics_Q[$workshop->unique_code] ?? 0),
                            ];
                        }
                    }
                }
            }
            # 选择车间
            if (!empty($this->_workshop_unique_code) && empty($this->_station_unique_code)) {
                $stations = DB::table('maintains as s')
                    ->join(DB::raw('maintains as sw'), 'sw.unique_code', '=', 's.parent_unique_code')
                    ->where('s.parent_unique_code', $this->_workshop->unique_code)
                    ->where('sw.is_show', true)
                    ->select(['s.name', 's.unique_code'])
                    ->get()
                    ->toArray();

                $statistics_S = $this->__joinS(
                    DB::table('entire_instances as ei')
                        ->selectRaw('count(ei.id) as count, s.unique_code as station_unique_code')
                        ->join(DB::raw('maintains as s'), 's.name', '=', 'ei.maintain_station_name')
                )
                    ->groupBy(['station_unique_code'])
                    ->pluck('count', 'station_unique_code')
                    ->toArray();
                $statistics_Q = $this->__joinQ(
                    DB::table('entire_instances as ei')
                        ->selectRaw('count(ei.id) as count, s.unique_code as station_unique_code')
                        ->join(DB::raw('maintains as s'), 's.name', '=', 'ei.maintain_station_name')
                )
                    ->groupBy(['station_unique_code'])
                    ->pluck('count', 'station_unique_code')
                    ->toArray();

                if ($this->_e_switch) {
                    // 获取电子车间数据
                    $this->_curl->get("{$this->_e_url}/maintain", ['workshop_unique_code' => request('workshop_unique_code')]);
                    if ($this->_curl->error) return JsonResponseFacade::errorForbidden($this->_curl->response->msg ?? '数据异常', $this->_curl->response);
                    $el_data = $this->_curl->response->data;
                    foreach ($el_data as $el_datum) $el_data2[$el_datum->unique_code] = $el_datum;
                    // 合并数据
                    foreach ($stations as $station) {
                        if (array_key_exists($station->unique_code, $el_data2)) {
                            $el_data2[$station->unique_code]->count += ($statistics_S[$station->unique_code] ?? 0) + ($statistics_Q[$station->unique_code] ?? 0);
                        } else {
                            $el_data2[$station->unique_code] = [
                                'name' => $station->name,
                                'unique_code' => $station->unique_code,
                                'count' => ($statistics_S[$station->unique_code] ?? 0) + ($statistics_Q[$station->unique_code] ?? 0),
                            ];
                        }
                    }
                }
            }
            return JsonResponseFacade::data(array_values($el_data2));
        } catch (ModelNotFoundException $exception) {
            return JsonResponseFacade::errorEmpty();
        } catch (\Exception $exception) {
            return JsonResponseFacade::errorException($exception);
        }
    }

    /**
     * 设备列表
     */
    final public function getEntireInstances()
    {
        $statuses = collect([
            '入所' => 'FIXING',
            '车间备品' => 'FIXED',
            '上道' => 'INSTALLED',
            '下道' => 'UNINSTALLED',
            '现场备品' => 'INSTALLING',
            '送修' => 'SEND_REPAIR',
        ]);

        $entire_instances_Q = $this->__joinQ(
            DB::table('entire_instances as ei')
                ->selectRaw(
                    join(',', [
                        "concat('https://hefei.zhongchengkeshi.com/api/pda/entireInstanceByIdentityCode/',ei.identity_code) as url",
                        'ei.identity_code as unique_code',
                        'c.name as category_name',
                        'em.name as entire_model_name',
                        'sm.name as sub_model_name',
                        'ei.maintain_workshop_name as workshop_name',
                        'ei.maintain_station_name as station_name',
                        'ei.factory_name',
                        'ei.status as state',
                        "DATE_FORMAT(ei.next_fixing_time,'%Y-%m-%d') as cycle_fix_at",
                        "ei.installed_at",
                        "DATE_FORMAT(ei.scarping_at,'%Y-%m-%d') as due_date_at",
                        "if(now() > ei.scarping_at,true,null) as is_overdue",
                    ])
                )
        )
            ->when(request('category_unique_code'), function ($query) {
                $query->where('c.unique_code', request('category_unique_code'));
            })
            ->when(request('entire_model_unique_code'), function ($query) {
                $query->where('em.unique_code', request('entire_model_unique_code'));
            })
            ->when(request('sub_model_unique_code'), function ($query) {
                $query->where('sm.unique_code', request('sub_model_unique_code'));
            })
            ->when($statuses->get(request('state_name')), function ($query) use ($statuses) {
                $query->where('ei.status', $statuses->get(request('state_name')));
            })
            ->when(request('workshop_unique_code'), function ($query) {
                $query->where('ei.maintain_workshop_name', $this->_workshop->name);
            })
            ->when(request('station_unique_code'), function ($query) {
                $query->where('ei.maintain_station_name', $this->_station->name);
            })
            ->orderByDesc('ei.updated_at');


        $entire_instances_S = $this->__joinS(
            DB::table('entire_instances as ei')
                ->selectRaw(
                    join(',', [
                        "concat('https://hefei.zhongchengkeshi.com/api/pda/entireInstanceByIdentityCode/',ei.identity_code) as url",
                        'ei.identity_code as unique_code',
                        'c.name as category_name',
                        'em.name as entire_model_name',
                        'pm.name as sub_model_name',
                        'ei.maintain_workshop_name as workshop_name',
                        'ei.maintain_station_name as station_name',
                        'ei.factory_name',
                        'ei.status as state',
                        "DATE_FORMAT(ei.next_fixing_time,'%Y-%m-%d') as cycle_fix_at",
                        "ei.installed_at",
                        "DATE_FORMAT(ei.scarping_at,'%Y-%m-%d') as due_date_at",
                        "if(now() > ei.scarping_at,true,null) as is_overdue",
                    ])
                )
        )
            ->when(request('category_unique_code'), function ($query) {
                $query->where('c.unique_code', request('category_unique_code'));
            })
            ->when(request('entire_model_unique_code'), function ($query) {
                $query->where('em.unique_code', request('entire_model_unique_code'));
            })
            ->when(request('sub_model_unique_code'), function ($query) {
                $query->where('pm.unique_code', request('sub_model_unique_code'));
            })
            ->when($statuses->get(request('state_name')), function ($query) use ($statuses) {
                $query->where('ei.status', $statuses->get(request('state_name')));
            })
            ->when(request('workshop_unique_code'), function ($query) {
                $query->where('ei.maintain_workshop_name', $this->_workshop->name);
            })
            ->when(request('station_unique_code'), function ($query) {
                $query->where('ei.maintain_station_name', $this->_station->name);
            })
            ->orderByDesc('ei.updated_at');

        $entire_instances = ModelBuilderFacade::unionAll($entire_instances_Q, $entire_instances_S)->get();

        return JsonResponseFacade::data($entire_instances);
    }
}
