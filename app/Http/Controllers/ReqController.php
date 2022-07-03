<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReqController extends Controller
{
    public function index(){
    	$data = file_get_contents("php://input");
        file_put_contents("1.txt", $data,FILE_APPEND);
        $fileContent = file_get_contents("1.txt");
        echo $fileContent;
    }
}
