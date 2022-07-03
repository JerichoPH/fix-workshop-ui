<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Jericho\BadRequestException;
use Jericho\CurlHelper;

class OfficialDocumentController extends Controller
{
    private $_spas_protocol = null;
    private $_spas_url_root = null;
    private $_spas_port = null;
    private $_spas_api_root = null;
    private $_spas_username = null;
    private $_spas_password = null;
    private $_spas_url = 'basic/officialDocument';
    private $_root_url = null;
    private $_auth = null;

    final public function __construct()
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
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $official_documents_response = CurlHelper::init([
                'url' => "{$this->_root_url}/{$this->_spas_url}",
                'headers' => $this->_auth,
                'method' => CurlHelper::GET,
                'queries' => [
                    'page' => request('page', 1),
                    'code' => env('ORGANIZATION_CODE'),
                ]
            ]);
            if ($official_documents_response['code'] < 200 || $official_documents_response['code'] > 399)
                return response()->json($official_documents_response['body'], $official_documents_response['code']);

            $official_documents = new LengthAwarePaginator(
                $official_documents_response['body']['data'],
                $official_documents_response['body']['count'],
                $official_documents_response['body']['limit'],
                $official_documents_response['body']['current_page']
            );

            return view("OfficialDocument.index", [
                'spas_url' => "{$this->_spas_protocol}://{$this->_spas_url_root}:{$this->_spas_port}",
                'official_documents' => $official_documents,
            ]);
        } catch (BadRequestException $e) {
            return back()->with('danger', '数据中台连接失败');
        } catch (\Throwable $th) {
            return back()->with('danger', $th->getMessage(), 500);
        }
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
