<?php

namespace App\Http\Controllers;

use App\Facades\JsonResponseFacade;
use Curl\Curl;
use Illuminate\Http\Request;
use Jericho\TextHelper;

class MessageController extends Controller
{

    private $_spas_protocol = null;
    private $_spas_url_root = null;
    private $_spas_port = null;
    private $_spas_api_root = null;
    private $_spas_username = null;
    private $_spas_password = null;
    private $_spas_url = 'message/index';
    private $_root_url = null;
    private $_auth = null;
    private $_curl = null;

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
        $this->_curl = new Curl();
        $this->_curl->setHeader('Access-Key', env('GROUP_ACCESS_KEY'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            # 获取和自己相关的消息
            $params = [
                'nonce' => time() . TextHelper::rand(),
                'receiver_paragraph_original_id' => session('account.id'),
                'receiver_paragraph_unique_code' => env('ORGANIZATION_CODE'),
                'organization_type_unique_code' => 'FIX_WORKSHOP',
                'limit' => request('limit'),
                'ordering' => request('ordering')
            ];
            $this->_curl->setHeader('Sign', TextHelper::makeSign($params, env('GROUP_SECRET_KEY')));
            $this->_curl->get(env('GROUP_URL') . "/message", $params);

            if ($this->_curl->error) return JsonResponseFacade::data([]);
            return JsonResponseFacade::data($this->_curl->response->data);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param $message_id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function show($message_id)
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * 消息星标
     * @param int $message_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function putMarkStar(int $message_id)
    {
        //
    }

    /**
     * 收件箱
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function getInput()
    {
        //
    }

    /**
     * 发件箱
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function getSend()
    {
        //
    }

    /**
     * 回复消息页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function getReply()
    {
        return view('Message.reply_ajax');
    }
}
