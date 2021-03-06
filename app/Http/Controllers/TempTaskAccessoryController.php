<?php

namespace App\Http\Controllers;

use Curl\Curl;
use Illuminate\Http\Request;
use Jericho\TextHelper;

class TempTaskAccessoryController extends Controller
{
    private $_curl = null;

    public function __construct()
    {
        $this->_curl = new Curl();
        $this->_curl->setHeader('Access-Key', env('GROUP_ACCESS_KEY'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    final public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    final public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    final public function edit($id)
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
    final public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    final public function destroy($id)
    {
        try {
            $params = ['nonce' => TextHelper::rand()];
            $sign = TextHelper::makeSign($params, env('GROUP_SECRET_KEY'));
            $this->_curl->setHeader('Sign', $sign);
            $this->_curl->delete(env('GROUP_URL') . "/tempTaskAccessory/{$id}", $params);
            if ($this->_curl->error) {
                return response()->json($this->_curl->response, $this->_curl->errorCode);
            } else {
                return response()->json($this->_curl->response);
            }
        } catch (\Throwable $e) {
            return response()->json(['msg' => '????????????', 'details' => [$e->getMessage(), $e->getFile(), $e->getLine()]], 500);
        }
    }

    /**
     * ??????
     * @param int $id
     */
    final public function getDownload(int $id)
    {
        try {
            # ??????????????????
            $params = ['nonce' => TextHelper::rand()];
            $sign = TextHelper::makeSign($params, env('GROUP_SECRET_KEY'));
            $this->_curl->setHeader('Sign', $sign);
            $this->_curl->get(env('GROUP_URL') . "/tempTaskAccessory/{$id}", $params);
            if ($this->_curl->error) {
                echo "<h1>?????????{$this->_curl->response->msg}</h1>";
            }
            $filename = $this->_curl->response->data->name;

            $accessoryPath = storage_path('tempTask/download/');
            if (!is_dir($accessoryPath)) mkdir($accessoryPath);
            $this->_curl->download(env('GROUP_URL') . "/tempTaskAccessory/download/{$id}", $accessoryPath . $filename);

            $fp = fopen($accessoryPath . $filename, "r");
            $fileSize = filesize($accessoryPath . $filename);
            //??????????????????????????????
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            Header("Accept-Length:" . $fileSize);
            Header("Content-Disposition: attachment; filename=" . $filename);
            $buffer = 1024;  //?????????????????????????????????????????????????????????????????????????????????????????????
            $fileCount = 0; //?????????????????????
            //????????????????????????
            while (!feof($fp) && $fileCount < $fileSize) {
                $fileCon = fread($fp, $buffer);
                $fileCount += $buffer;
                echo $fileCon;
            }
            fclose($fp);

            // ??????????????????????????????
            if ($fileCount >= $fileSize) unlink($accessoryPath . $filename);
        } catch (\Throwable $e) {
            \App\Facades\CommonFacade::ddExceptionWithAppDebug($e);
//            return back()->with('danger', '????????????');
        }
    }
}
