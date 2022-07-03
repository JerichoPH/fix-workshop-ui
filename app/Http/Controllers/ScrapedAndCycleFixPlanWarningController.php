<?php

namespace App\Http\Controllers;

use App\Facades\CommonFacade;
use App\Facades\JsonResponseFacade;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScrapedAndCycleFixPlanWarningController extends Controller
{
    /**
     * get warning list
     */
    final public function index()
    {
        try {
            // generate scraped statistics
            $scraped_statistics = DB::table('entire_instances as ei')
                ->selectRaw(implode(',', [
                    'count(c.unique_code)  as aggregate',
                    'c.unique_code         as category_unique_code',
                    'c.name                as category_name',
                    's.unique_code         as station_unique_code',
                    's.name                as station_name',
                ]))
                ->join(DB::raw('categories c '), 'c.unique_code', '=', 'ei.category_unique_code')
                ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                ->where('ei.scarping_at', '<', now())
                ->where('ei.deleted_at', null)
                ->where('ei.status', '<>', 'SCRAP')
                ->where('c.deleted_at', null)
                ->groupBy([
                    'c.unique_code',
                    'c.name',
                    's.unique_code',
                    's.name',
                ])
                ->get();

            // generate cycle fix plan
            $cycle_fix_plan_statistics = collect([]);
            if (session('account.work_area_unique_code')) {
                $cycle_fix_plan_statistics = DB::table('entire_instances as ei')
                    ->selectRaw(implode(',', [
                        'count(c.unique_code)  as aggregate',
                        'c.unique_code         as category_unique_code',
                        'c.name                as category_name',
                        's.unique_code         as station_unique_code',
                        's.name                as station_name',
                    ]))
                    ->join(DB::raw('categories c'), 'c.unique_code', '=', 'ei.category_unique_code')
                    ->join(DB::raw('maintains s'), 's.name', '=', 'ei.maintain_station_name')
                    ->join(DB::raw('maintains sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                    ->whereNull('ei.deleted_at')
                    ->whereIn('ei.status', ['INSTALLED', 'INSTALLING'])
                    ->whereNull('c.deleted_at')
                    ->where('work_area_unique_code', session('account.work_area_unique_code'))
                    ->whereNotNull('next_fixing_day')
                    ->whereBetween('next_fixing_day', [now()->startOfMonth(), now()->endOfMonth(),])
                    ->groupBy(['c.unique_code', 'c.name', 's.unique_code', 's.name',])
                    ->get();
            }

            return JsonResponseFacade::dict([
                'scraped_statistics' => $scraped_statistics,
                'cycle_fix_plan_statistics' => $cycle_fix_plan_statistics,
            ]);
        } catch (ModelNotFoundException $e) {
            return JsonResponseFacade::errorEmpty();
        } catch (Throwable $e) {
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }
}
