<?php

namespace App\Http\Controllers\V1\Basic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jericho\BadRequestException;
use Jericho\CurlHelper;

class SceneWorkshopController extends Controller
{
    private $_spas_protocol = null;
    private $_spas_url_root = null;
    private $_spas_port = null;
    private $_spas_api_root = null;
    private $_spas_username = null;
    private $_spas_password = null;
    private $_spas_url = 'basic/sceneWorkshop';
    private $_root_url = null;
    private $_auth = null;

    public function __construct()
    {
        $this->_spas_protocol = env('SPAS_PROTOCOL');
        $this->_spas_url_root = env('SPAS_URL_ROOT');
        $this->_spas_port = env('SPAS_PORT');
        $this->_spas_api_root = env('SPAS_API_ROOT');
        $this->_spas_username = env('SPAS_USERNAME');
        $this->_spas_password = env('SPAS_PASSWORD');
        $this->_root_url = "{$this->_spas_protocol}://{$this->_spas_url_root}:{$this->_spas_port}/{$this->_spas_api_root}";
        $this->_auth = ["Username:{$this->_spas_username}", "Password:{$this->_spas_password}"];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $db = DB::table('maintains')
            ->where('parent_unique_code', env('ORGANIZATION_CODE'))
            ->where('type', 'SCENE_WORKSHOP')
            ->where('deleted_at', null)
            ->orderBy(request('order_by', 'id'), request('direction', 'desc'));
        $scene_workshops = request('page') ? $db->paginate(request('limit', 15)) : $db->get();
        return response()->json($scene_workshops);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $unique_code
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($unique_code)
    {
        DB::table('maintains')->where('unique_code', $unique_code)->delete();
        return response()->json(['message' => '删除成功']);
    }

    public function getInit()
    {
        try {
            $scene_workshops_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
            ]);
            if ($scene_workshops_response['code'] != 200) return response()->json($scene_workshops_response['body'], $scene_workshops_response['code']);

            $stations_response = CurlHelper::init([
                'url' => "{$this->_root_url}/basic/station",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
            ]);
            if ($stations_response['code'] != 200) return response()->json($stations_response['body'], $stations_response['code']);

            $insert = [];
            $current_time = date('Y-m-d H:i:s');
            foreach ($scene_workshops_response['body']['data'] as $datum) {
                $insert[] = [
                    'created_at' => $current_time,
                    'updated_at' => $current_time,
                    'name' => $datum['name'],
                    'unique_code' => $datum['unique_code'],
                    'parent_unique_code' => $datum['paragraph'],
                    'type' => 'SCENE_WORKSHOP',
                ];
            }
            foreach ($stations_response['body']['data'] as $datum) {
                $insert[] = [
                    'created_at' => $current_time,
                    'updated_at' => $current_time,
                    'name' => $datum['name'],
                    'unique_code' => $datum['unique_code'],
                    'parent_unique_code' => $datum['scene_workshop'],
                    'type' => 'STATION',
                ];
            }

            if ($insert) {
                DB::table('maintains')->truncate();
                DB::table('maintains')->insert($insert);
            }

            return response()->json(['message' => '备份成功']);
        } catch (BadRequestException $e) {
            return response()->json(['message' => '数据中台链接失败'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
