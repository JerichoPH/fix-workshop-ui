<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SqlserverController extends Controller
{
    //SQL server更新数据存入mysql
    public function index(Request $request)
    {

        $type_id = $request->get('data');
        // $type_id = 1;
        $str = '上传时间[' . date("Y-m-d H:i:s", time()) . '] || ' . $type_id . "\r\n";
        file_put_contents("text.txt", $str, FILE_APPEND);
        $data = DB::table('type_ids')->max('TYPE_ID');
        // echo $type_id;
        //SQLserver服务器TYPE_ID 减
        // $jud = $type_id - $data;
        $sql = "SELECT * FROM tTYPE_INDEX WHERE TYPE_ID > $data";
        // $sql = "SELECT * FROM tTYPE_INDEX";
        $res = DB::connection('sqlsrv')->select($sql)->toArray();
        //object(stdClass)转成可用array
        function object_array($array)
        {
            if (is_object($array)) {
                $array = (array)$array;
            }
            if (is_array($array)) {
                foreach ($array as $key => $value) {
                    $array[$key] = object_array($value);
                }
            }
            return $array;
        }

        $res = object_array($res);

        DB::table('type_ids')->insert($res);
    }

}
