<?php

namespace App\Console\Commands;

use App\Libraries\Super\TextHelper;
use Hprose\Http\Client;
use Illuminate\Console\Command;
use App\Libraries\Super\FileSystem;
use Illuminate\Support\Facades\DB;

class MonitorPolylineCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'MonitorPolyline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '处理数据';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return bool
     * @throws \Exception
     */
    public function handle()
    {
        $a = [
            [
                [
                    "lon"=> 113.160209,
                    "lat"=> 27.506395,
                    "name"=> "淦田",
                    "unique_code"=> "G00205"
                ],
                [
                    "lon"=> 113.168801,
                    "lat"=> 27.517218,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.174802,
                    "lat"=> 27.539226,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.173544,
                    "lat"=> 27.546113,
                    "name"=> "昭陵",
                    "unique_code"=> "G00212"
                ],
                [
                    "lon"=> 113.172718,
                    "lat"=> 27.54781,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.167759,
                    "lat"=> 27.553864,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.149182,
                    "lat"=> 27.565361,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.141493,
                    "lat"=> 27.568852,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.13869,
                    "lat"=> 27.573367,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.128413,
                    "lat"=> 27.602246,
                    "name"=> "三门",
                    "unique_code"=> "G00209"
                ],
                [
                    "lon"=> 113.125467,
                    "lat"=> 27.610025,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.123778,
                    "lat"=> 27.645455,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.149613,
                    "lat"=> 27.714138,
                    "name"=> "渌口",
                    "unique_code"=> "G00206"
                ],
                [
                    "lon"=> 113.150117,
                    "lat"=> 27.732045,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.157087,
                    "lat"=> 27.74087,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.173688,
                    "lat"=> 27.773764,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.174946,
                    "lat"=> 27.782681,
                    "name"=> "七斗冲",
                    "unique_code"=> "G00208"
                ],
                [
                    "lon"=> 113.177245,
                    "lat"=> 27.78984,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.176491,
                    "lat"=> 27.794633,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.169412,
                    "lat"=> 27.804411,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.168406,
                    "lat"=> 27.810395,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.169933,
                    "lat"=> 27.816529,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.163879,
                    "lat"=> 27.842038,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.160429,
                    "lat"=> 27.849703,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.14929,
                    "lat"=> 27.858327,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.140577,
                    "lat"=> 27.866662,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.134863,
                    "lat"=> 27.879946,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.132743,
                    "lat"=> 27.88167,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.121892,
                    "lat"=> 27.885885,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.104914,
                    "lat"=> 27.901098,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.104177,
                    "lat"=> 27.905679,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.102722,
                    "lat"=> 27.907451,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.090433,
                    "lat"=> 27.913963,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.088098,
                    "lat"=> 27.915958,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.075899,
                    "lat"=> 27.930975,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.070131,
                    "lat"=> 27.935268,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.052345,
                    "lat"=> 27.943852,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.049434,
                    "lat"=> 27.946341,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.035313,
                    "lat"=> 27.962072,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.028953,
                    "lat"=> 27.970336,
                    "name"=> "易家湾",
                    "unique_code"=> "G00282"
                ],
                [
                    "lon"=> 113.025108,
                    "lat"=> 27.973303,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.021803,
                    "lat"=> 27.977227,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.999884,
                    "lat"=> 27.996972,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.97024,
                    "lat"=> 28.046813,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.967509,
                    "lat"=> 28.059214,
                    "name"=> "大托铺",
                    "unique_code"=> "G00281"
                ],
                [
                    "lon"=> 112.963521,
                    "lat"=> 28.081366,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.964778,
                    "lat"=> 28.103992,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.967194,
                    "lat"=> 28.115929,
                    "name"=> "黑石铺",
                    "unique_code"=> "G00280"
                ],
                [
                    "lon"=> 112.968515,
                    "lat"=> 28.134927,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.982672,
                    "lat"=> 28.145725,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.989931,
                    "lat"=> 28.148528,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.01476,
                    "lat"=> 28.156681,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.019575,
                    "lat"=> 28.163592,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.021653,
                    "lat"=> 28.20072,
                    "name"=> "长沙",
                    "unique_code"=> "G00275"
                ],
                [
                    "lon"=> 113.021438,
                    "lat"=> 28.216636,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.999734,
                    "lat"=> 28.235541,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.998872,
                    "lat"=> 28.249352,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.996357,
                    "lat"=> 28.255079,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.990176,
                    "lat"=> 28.276713,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.990105,
                    "lat"=> 28.296053,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.965527,
                    "lat"=> 28.339682,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.951442,
                    "lat"=> 28.348838,
                    "name"=> "长沙北",
                    "unique_code"=> "G00270"
                ],
                [
                    "lon"=> 112.940518,
                    "lat"=> 28.356721,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.933763,
                    "lat"=> 28.369435,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.956903,
                    "lat"=> 28.422437,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.968689,
                    "lat"=> 28.431713,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.986943,
                    "lat"=> 28.478206,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 112.995854,
                    "lat"=> 28.494842,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.035235,
                    "lat"=> 28.5474,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.054351,
                    "lat"=> 28.602722,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.055555,
                    "lat"=> 28.608081,
                    "name"=> "川山坪",
                    "unique_code"=> "G00244"
                ],
                [
                    "lon"=> 113.062544,
                    "lat"=> 28.637346,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.050327,
                    "lat"=> 28.682734,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.065023,
                    "lat"=> 28.742259,
                    "name"=> "古培塘",
                    "unique_code"=> "G00242"
                ],
                [
                    "lon"=> 113.071204,
                    "lat"=> 28.755655,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.082163,
                    "lat"=> 28.820233,
                    "name"=> "汨罗",
                    "unique_code"=> "G00241"
                ],
                [
                    "lon"=> 113.086906,
                    "lat"=> 28.831688,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.096105,
                    "lat"=> 28.840105,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.103579,
                    "lat"=> 28.860036,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.104585,
                    "lat"=> 28.871108,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.097829,
                    "lat"=> 28.887428,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.10092,
                    "lat"=> 28.900646,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.108825,
                    "lat"=> 28.940545,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.123413,
                    "lat"=> 28.961847,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.139295,
                    "lat"=> 29.016884,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.133905,
                    "lat"=> 29.04196,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.136546,
                    "lat"=> 29.064015,
                    "name"=> "黄秀桥",
                    "unique_code"=> "G00237"
                ],
                [
                    "lon"=> 113.131821,
                    "lat"=> 29.116899,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.131103,
                    "lat"=> 29.155577,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.141164,
                    "lat"=> 29.190395,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.133762,
                    "lat"=> 29.228289,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.106022,
                    "lat"=> 29.269446,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.100165,
                    "lat"=> 29.309767,
                    "name"=> "湖滨",
                    "unique_code"=> "G00267"
                ],
                [
                    "lon"=> 113.089278,
                    "lat"=> 29.332,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.09623,
                    "lat"=> 29.357423,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.110603,
                    "lat"=> 29.381156,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.126198,
                    "lat"=> 29.382604,
                    "name"=> "岳阳",
                    "unique_code"=> "G00265"
                ],
                [
                    "lon"=> 113.136187,
                    "lat"=> 29.384807,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.167484,
                    "lat"=> 29.406142,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.180995,
                    "lat"=> 29.411082,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.212759,
                    "lat"=> 29.433638,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.241433,
                    "lat"=> 29.449397,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.263423,
                    "lat"=> 29.458549,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.283563,
                    "lat"=> 29.478107,
                    "name"=> "云溪",
                    "unique_code"=> "G00260"
                ],
                [
                    "lon"=> 113.293426,
                    "lat"=> 29.489536,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.298259,
                    "lat"=> 29.49389,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.303092,
                    "lat"=> 29.504311,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.318471,
                    "lat"=> 29.510551,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.343642,
                    "lat"=> 29.510393,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.374903,
                    "lat"=> 29.516114,
                    "name"=> "路口铺",
                    "unique_code"=> "G00258"
                ],
                [
                    "lon"=> 113.387766,
                    "lat"=> 29.51598,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.404547,
                    "lat"=> 29.507745,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.413674,
                    "lat"=> 29.505388,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.440838,
                    "lat"=> 29.493599,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.451474,
                    "lat"=> 29.493348,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.461356,
                    "lat"=> 29.49099,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.482879,
                    "lat"=> 29.480835,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.490497,
                    "lat"=> 29.480741,
                    "name"=> "临湘",
                    "unique_code"=> "G00257"
                ],
                [
                    "lon"=> 113.502247,
                    "lat"=> 29.482093,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.502067,
                    "lat"=> 29.482124,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.517733,
                    "lat"=> 29.487155,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.536706,
                    "lat"=> 29.489104,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.564876,
                    "lat"=> 29.498409,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.58507,
                    "lat"=> 29.503753,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.60415,
                    "lat"=> 29.505545,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.648958,
                    "lat"=> 29.517489,
                    "name"=> null,
                    "unique_code"=> null
                ],
                [
                    "lon"=> 113.725853,
                    "lat"=> 29.571502,
                    "name"=> "赵李桥",
                    "unique_code"=> "G00255"
                ]
            ],
            [["lon"=>113.160209,"lat"=>27.506395,"name"=>"淦田","unique_code"=>"G00205"],["lon"=>113.168801,"lat"=>27.517218,"name"=>null,"unique_code"=>null],["lon"=>113.174802,"lat"=>27.539226,"name"=>null,"unique_code"=>null],["lon"=>113.173544,"lat"=>27.546113,"name"=>"昭陵","unique_code"=>"G00212"],["lon"=>113.172718,"lat"=>27.54781,"name"=>null,"unique_code"=>null],["lon"=>113.167759,"lat"=>27.553864,"name"=>null,"unique_code"=>null],["lon"=>113.149182,"lat"=>27.565361,"name"=>null,"unique_code"=>null],["lon"=>113.141493,"lat"=>27.568852,"name"=>null,"unique_code"=>null],["lon"=>113.13869,"lat"=>27.573367,"name"=>null,"unique_code"=>null],["lon"=>113.128413,"lat"=>27.602246,"name"=>"三门","unique_code"=>"G00209"],["lon"=>113.125467,"lat"=>27.610025,"name"=>null,"unique_code"=>null],["lon"=>113.123778,"lat"=>27.645455,"name"=>null,"unique_code"=>null],["lon"=>113.149613,"lat"=>27.714138,"name"=>"渌口","unique_code"=>"G00206"],["lon"=>113.150117,"lat"=>27.732045,"name"=>null,"unique_code"=>null],["lon"=>113.157087,"lat"=>27.74087,"name"=>null,"unique_code"=>null],["lon"=>113.173688,"lat"=>27.773764,"name"=>null,"unique_code"=>null],["lon"=>113.174946,"lat"=>27.782681,"name"=>"七斗冲","unique_code"=>"G00208"],["lon"=>113.177245,"lat"=>27.78984,"name"=>null,"unique_code"=>null],["lon"=>113.176491,"lat"=>27.794633,"name"=>null,"unique_code"=>null],["lon"=>113.169412,"lat"=>27.804411,"name"=>null,"unique_code"=>null],["lon"=>113.168406,"lat"=>27.810395,"name"=>null,"unique_code"=>null],["lon"=>113.169933,"lat"=>27.816529,"name"=>null,"unique_code"=>null],["lon"=>113.163879,"lat"=>27.842038,"name"=>null,"unique_code"=>null],["lon"=>113.160429,"lat"=>27.849575,"name"=>null,"unique_code"=>null],["lon"=>113.159414,"lat"=>27.85439,"name"=>null,"unique_code"=>null],["lon"=>113.157716,"lat"=>27.86227,"name"=>null,"unique_code"=>null],["lon"=>113.157087,"lat"=>27.8635,"name"=>null,"unique_code"=>null],["lon"=>113.148311,"lat"=>27.870653,"name"=>null,"unique_code"=>null],["lon"=>113.13825,"lat"=>27.879858,"name"=>null,"unique_code"=>null],["lon"=>113.137693,"lat"=>27.881183,"name"=>null,"unique_code"=>"G00181"],["lon"=>113.135663,"lat"=>27.881167,"name"=>null,"unique_code"=>null],["lon"=>113.132069,"lat"=>27.884184,"name"=>null,"unique_code"=>null],["lon"=>113.121505,"lat"=>27.892964,"name"=>null,"unique_code"=>null],["lon"=>113.109001,"lat"=>27.903404,"name"=>null,"unique_code"=>null],["lon"=>113.095419,"lat"=>27.911353,"name"=>null,"unique_code"=>null],["lon"=>113.088052,"lat"=>27.915981,"name"=>null,"unique_code"=>null],["lon"=>113.075899,"lat"=>27.930975,"name"=>null,"unique_code"=>null],["lon"=>113.070131,"lat"=>27.935268,"name"=>null,"unique_code"=>null],["lon"=>113.052345,"lat"=>27.943852,"name"=>null,"unique_code"=>null],["lon"=>113.049434,"lat"=>27.946341,"name"=>null,"unique_code"=>null],["lon"=>113.035313,"lat"=>27.962072,"name"=>null,"unique_code"=>null],["lon"=>113.028953,"lat"=>27.970336,"name"=>"易家湾","unique_code"=>"G00282"],["lon"=>113.025108,"lat"=>27.973303,"name"=>null,"unique_code"=>null],["lon"=>113.021803,"lat"=>27.977227,"name"=>null,"unique_code"=>null],["lon"=>112.999884,"lat"=>27.996972,"name"=>null,"unique_code"=>null],["lon"=>112.97024,"lat"=>28.046813,"name"=>null,"unique_code"=>null],["lon"=>112.967509,"lat"=>28.059214,"name"=>"大托铺","unique_code"=>"G00281"],["lon"=>112.963521,"lat"=>28.081366,"name"=>null,"unique_code"=>null],["lon"=>112.964778,"lat"=>28.103992,"name"=>null,"unique_code"=>null],["lon"=>112.967194,"lat"=>28.115929,"name"=>"黑石铺","unique_code"=>"G00280"],["lon"=>112.968515,"lat"=>28.134927,"name"=>null,"unique_code"=>null],["lon"=>112.982672,"lat"=>28.145725,"name"=>null,"unique_code"=>null],["lon"=>112.989931,"lat"=>28.148528,"name"=>null,"unique_code"=>null],["lon"=>113.01476,"lat"=>28.156681,"name"=>null,"unique_code"=>null],["lon"=>113.019575,"lat"=>28.163592,"name"=>null,"unique_code"=>null],["lon"=>113.021653,"lat"=>28.20072,"name"=>"长沙","unique_code"=>"G00275"],["lon"=>113.021438,"lat"=>28.216636,"name"=>null,"unique_code"=>null],["lon"=>112.999734,"lat"=>28.235541,"name"=>null,"unique_code"=>null],["lon"=>112.998872,"lat"=>28.249352,"name"=>null,"unique_code"=>null],["lon"=>112.996357,"lat"=>28.255079,"name"=>null,"unique_code"=>null],["lon"=>112.990176,"lat"=>28.276713,"name"=>null,"unique_code"=>null],["lon"=>112.990105,"lat"=>28.296053,"name"=>null,"unique_code"=>null],["lon"=>112.965527,"lat"=>28.339682,"name"=>null,"unique_code"=>null],["lon"=>112.951442,"lat"=>28.348838,"name"=>"长沙北","unique_code"=>"G00270"],["lon"=>112.940518,"lat"=>28.356721,"name"=>null,"unique_code"=>null],["lon"=>112.933763,"lat"=>28.369435,"name"=>null,"unique_code"=>null],["lon"=>112.956903,"lat"=>28.422437,"name"=>null,"unique_code"=>null],["lon"=>112.968689,"lat"=>28.431713,"name"=>null,"unique_code"=>null],["lon"=>112.986943,"lat"=>28.478206,"name"=>null,"unique_code"=>null],["lon"=>112.995854,"lat"=>28.494842,"name"=>null,"unique_code"=>null],["lon"=>113.035235,"lat"=>28.5474,"name"=>null,"unique_code"=>null],["lon"=>113.054351,"lat"=>28.602722,"name"=>null,"unique_code"=>null],["lon"=>113.055555,"lat"=>28.608081,"name"=>"川山坪","unique_code"=>"G00244"],["lon"=>113.062544,"lat"=>28.637346,"name"=>null,"unique_code"=>null],["lon"=>113.050327,"lat"=>28.682734,"name"=>null,"unique_code"=>null],["lon"=>113.065023,"lat"=>28.742259,"name"=>"古培塘","unique_code"=>"G00242"],["lon"=>113.071204,"lat"=>28.755655,"name"=>null,"unique_code"=>null],["lon"=>113.082163,"lat"=>28.820233,"name"=>"汨罗","unique_code"=>"G00241"],["lon"=>113.086906,"lat"=>28.831688,"name"=>null,"unique_code"=>null],["lon"=>113.096105,"lat"=>28.840105,"name"=>null,"unique_code"=>null],["lon"=>113.103579,"lat"=>28.860036,"name"=>null,"unique_code"=>null],["lon"=>113.104585,"lat"=>28.871108,"name"=>null,"unique_code"=>null],["lon"=>113.097829,"lat"=>28.887428,"name"=>null,"unique_code"=>null],["lon"=>113.10092,"lat"=>28.900646,"name"=>null,"unique_code"=>null],["lon"=>113.108825,"lat"=>28.940545,"name"=>null,"unique_code"=>null],["lon"=>113.123413,"lat"=>28.961847,"name"=>null,"unique_code"=>null],["lon"=>113.139295,"lat"=>29.016884,"name"=>null,"unique_code"=>null],["lon"=>113.133905,"lat"=>29.04196,"name"=>null,"unique_code"=>null],["lon"=>113.136546,"lat"=>29.064015,"name"=>"黄秀桥","unique_code"=>"G00237"],["lon"=>113.131821,"lat"=>29.116899,"name"=>null,"unique_code"=>null],["lon"=>113.131103,"lat"=>29.155577,"name"=>null,"unique_code"=>null],["lon"=>113.141164,"lat"=>29.190395,"name"=>null,"unique_code"=>null],["lon"=>113.133762,"lat"=>29.228289,"name"=>null,"unique_code"=>null],["lon"=>113.106022,"lat"=>29.269446,"name"=>null,"unique_code"=>null],["lon"=>113.100165,"lat"=>29.309767,"name"=>"湖滨","unique_code"=>"G00267"],["lon"=>113.089278,"lat"=>29.332,"name"=>null,"unique_code"=>null],["lon"=>113.09623,"lat"=>29.357423,"name"=>null,"unique_code"=>null],["lon"=>113.110603,"lat"=>29.381156,"name"=>null,"unique_code"=>null],["lon"=>113.126198,"lat"=>29.382604,"name"=>"岳阳","unique_code"=>"G00265"],["lon"=>113.136187,"lat"=>29.384807,"name"=>null,"unique_code"=>null],["lon"=>113.167484,"lat"=>29.406142,"name"=>null,"unique_code"=>null],["lon"=>113.180995,"lat"=>29.411082,"name"=>null,"unique_code"=>null],["lon"=>113.212759,"lat"=>29.433638,"name"=>null,"unique_code"=>null],["lon"=>113.241433,"lat"=>29.449397,"name"=>null,"unique_code"=>null],["lon"=>113.263423,"lat"=>29.458549,"name"=>null,"unique_code"=>null],["lon"=>113.283563,"lat"=>29.478107,"name"=>"云溪","unique_code"=>"G00260"],["lon"=>113.293426,"lat"=>29.489536,"name"=>null,"unique_code"=>null],["lon"=>113.298259,"lat"=>29.49389,"name"=>null,"unique_code"=>null],["lon"=>113.303092,"lat"=>29.504311,"name"=>null,"unique_code"=>null],["lon"=>113.318471,"lat"=>29.510551,"name"=>null,"unique_code"=>null],["lon"=>113.343642,"lat"=>29.510393,"name"=>null,"unique_code"=>null],["lon"=>113.374903,"lat"=>29.516114,"name"=>"路口铺","unique_code"=>"G00258"],["lon"=>113.387766,"lat"=>29.51598,"name"=>null,"unique_code"=>null],["lon"=>113.404547,"lat"=>29.507745,"name"=>null,"unique_code"=>null],["lon"=>113.413674,"lat"=>29.505388,"name"=>null,"unique_code"=>null],["lon"=>113.440838,"lat"=>29.493599,"name"=>null,"unique_code"=>null],["lon"=>113.451474,"lat"=>29.493348,"name"=>null,"unique_code"=>null],["lon"=>113.461356,"lat"=>29.49099,"name"=>null,"unique_code"=>null],["lon"=>113.482879,"lat"=>29.480835,"name"=>null,"unique_code"=>null],["lon"=>113.490497,"lat"=>29.480741,"name"=>"临湘","unique_code"=>"G00257"],["lon"=>113.502247,"lat"=>29.482093,"name"=>null,"unique_code"=>null],["lon"=>113.502067,"lat"=>29.482124,"name"=>null,"unique_code"=>null],["lon"=>113.517733,"lat"=>29.487155,"name"=>null,"unique_code"=>null],["lon"=>113.536706,"lat"=>29.489104,"name"=>null,"unique_code"=>null],["lon"=>113.564876,"lat"=>29.498409,"name"=>null,"unique_code"=>null],["lon"=>113.58507,"lat"=>29.503753,"name"=>null,"unique_code"=>null],["lon"=>113.60415,"lat"=>29.505545,"name"=>null,"unique_code"=>null],["lon"=>113.648958,"lat"=>29.517489,"name"=>null,"unique_code"=>null],["lon"=>113.725853,"lat"=>29.571502,"name"=>"赵李桥","unique_code"=>"G00255"]],
            [["lon"=>113.160209,"lat"=>27.506395,"name"=>"淦田","unique_code"=>"G00205"],["lon"=>113.168801,"lat"=>27.517218,"name"=>null,"unique_code"=>null],["lon"=>113.174802,"lat"=>27.539226,"name"=>null,"unique_code"=>null],["lon"=>113.173544,"lat"=>27.546113,"name"=>"昭陵","unique_code"=>"G00212"],["lon"=>113.172718,"lat"=>27.54781,"name"=>null,"unique_code"=>null],["lon"=>113.167759,"lat"=>27.553864,"name"=>null,"unique_code"=>null],["lon"=>113.149182,"lat"=>27.565361,"name"=>null,"unique_code"=>null],["lon"=>113.141493,"lat"=>27.568852,"name"=>null,"unique_code"=>null],["lon"=>113.13869,"lat"=>27.573367,"name"=>null,"unique_code"=>null],["lon"=>113.128413,"lat"=>27.602246,"name"=>"三门","unique_code"=>"G00209"],["lon"=>113.125467,"lat"=>27.610025,"name"=>null,"unique_code"=>null],["lon"=>113.123778,"lat"=>27.645455,"name"=>null,"unique_code"=>null],["lon"=>113.149613,"lat"=>27.714138,"name"=>"渌口","unique_code"=>"G00206"],["lon"=>113.150117,"lat"=>27.732045,"name"=>null,"unique_code"=>null],["lon"=>113.157087,"lat"=>27.74087,"name"=>null,"unique_code"=>null],["lon"=>113.173688,"lat"=>27.773764,"name"=>null,"unique_code"=>null],["lon"=>113.174946,"lat"=>27.782681,"name"=>"七斗冲","unique_code"=>"G00208"],["lon"=>113.177245,"lat"=>27.78984,"name"=>null,"unique_code"=>null],["lon"=>113.176491,"lat"=>27.794633,"name"=>null,"unique_code"=>null],["lon"=>113.169412,"lat"=>27.804411,"name"=>null,"unique_code"=>null],["lon"=>113.168406,"lat"=>27.810395,"name"=>null,"unique_code"=>null],["lon"=>113.169933,"lat"=>27.816529,"name"=>null,"unique_code"=>null],["lon"=>113.163879,"lat"=>27.842038,"name"=>null,"unique_code"=>null],["lon"=>113.160444,"lat"=>27.849495,"name"=>null,"unique_code"=>null],["lon"=>113.158539,"lat"=>27.852817,"name"=>null,"unique_code"=>null],["lon"=>113.149197,"lat"=>27.85831,"name"=>null,"unique_code"=>null],["lon"=>113.140645,"lat"=>27.866581,"name"=>null,"unique_code"=>null],["lon"=>113.134105,"lat"=>27.880696,"name"=>null,"unique_code"=>null],["lon"=>113.12178,"lat"=>27.885932,"name"=>null,"unique_code"=>null],["lon"=>113.115277,"lat"=>27.889117,"name"=>null,"unique_code"=>null],["lon"=>113.11118,"lat"=>27.888638,"name"=>null,"unique_code"=>null],["lon"=>113.09343,"lat"=>27.889915,"name"=>null,"unique_code"=>null],["lon"=>113.085345,"lat"=>27.88685,"name"=>null,"unique_code"=>null],["lon"=>113.074601,"lat"=>27.890873,"name"=>null,"unique_code"=>null],["lon"=>113.072158,"lat"=>27.890745,"name"=>null,"unique_code"=>null],["lon"=>113.046524,"lat"=>27.884973,"name"=>"十里冲","unique_code"=>"G00210"],["lon"=>113.022931,"lat"=>27.870374,"name"=>null,"unique_code"=>null],["lon"=>113.008989,"lat"=>27.859228,"name"=>null,"unique_code"=>null],["lon"=>112.982328,"lat"=>27.862422,"name"=>null,"unique_code"=>null],["lon"=>112.98186,"lat"=>27.862645,"name"=>null,"unique_code"=>null],["lon"=>112.978016,"lat"=>27.862933,"name"=>"湘潭东","unique_code"=>"G00283"]],
            [["lon"=>111.510772,"lat"=>27.579412,"name"=>"邵阳北高铁","unique_code"=>"G00114"],["lon"=>111.538545,"lat"=>27.577912,"name"=>null,"unique_code"=>null],["lon"=>111.627945,"lat"=>27.579577,"name"=>null,"unique_code"=>null],["lon"=>111.759529,"lat"=>27.608455,"name"=>null,"unique_code"=>null],["lon"=>111.812493,"lat"=>27.607238,"name"=>null,"unique_code"=>null],["lon"=>111.981087,"lat"=>27.64731,"name"=>null,"unique_code"=>null],["lon"=>112.019534,"lat"=>27.669515,"name"=>"娄底南","unique_code"=>"G00112"],["lon"=>112.139835,"lat"=>27.746655,"name"=>null,"unique_code"=>null],["lon"=>112.178498,"lat"=>27.765835,"name"=>null,"unique_code"=>null],["lon"=>112.299949,"lat"=>27.781177,"name"=>null,"unique_code"=>null],["lon"=>112.446121,"lat"=>27.83754,"name"=>null,"unique_code"=>null],["lon"=>112.553631,"lat"=>27.89515,"name"=>"韶山南","unique_code"=>"G00113"],["lon"=>112.591863,"lat"=>27.909452,"name"=>null,"unique_code"=>null],["lon"=>112.737172,"lat"=>27.941114,"name"=>null,"unique_code"=>null],["lon"=>112.897861,"lat"=>27.953368,"name"=>null,"unique_code"=>null],["lon"=>112.897861,"lat"=>27.953368,"name"=>"湘潭北","unique_code"=>"G00115"],["lon"=>112.970795,"lat"=>27.980231,"name"=>null,"unique_code"=>null],["lon"=>113.060194,"lat"=>28.052047,"name"=>null,"unique_code"=>null],["lon"=>113.066015,"lat"=>28.064543,"name"=>null,"unique_code"=>null],["lon"=>113.073418,"lat"=>28.113748,"name"=>null,"unique_code"=>null],["lon"=>113.074891,"lat"=>28.126714,"name"=>null,"unique_code"=>null],["lon"=>113.07295,"lat"=>28.14331,"name"=>null,"unique_code"=>null],["lon"=>113.072627,"lat"=>28.15312,"name"=>null,"unique_code"=>null],["lon"=>113.070543,"lat"=>28.165955,"name"=>null,"unique_code"=>null],["lon"=>113.070633,"lat"=>28.167547,"name"=>null,"unique_code"=>null],["lon"=>113.07304,"lat"=>28.171782,"name"=>null,"unique_code"=>null],["lon"=>113.078843,"lat"=>28.174361,"name"=>null,"unique_code"=>null],["lon"=>113.095857,"lat"=>28.177434,"name"=>null,"unique_code"=>null],["lon"=>113.106349,"lat"=>28.178039,"name"=>null,"unique_code"=>null]],
            [["lon"=>113.075025,"lat"=>27.797636,"name"=>"株洲西","unique_code"=>"G00183"],["lon"=>113.055765,"lat"=>27.99213,"name"=>null,"unique_code"=>null],["lon"=>113.060365,"lat"=>28.058328,"name"=>null,"unique_code"=>null],["lon"=>113.072977,"lat"=>28.114799,"name"=>null,"unique_code"=>null],["lon"=>113.07233,"lat"=>28.141272,"name"=>null,"unique_code"=>null],["lon"=>113.07233,"lat"=>28.141288,"name"=>null,"unique_code"=>null],["lon"=>113.071558,"lat"=>28.153407,"name"=>"长沙南站","unique_code"=>"G00180"],["lon"=>113.070138,"lat"=>28.185013,"name"=>null,"unique_code"=>null],["lon"=>113.077864,"lat"=>28.263436,"name"=>null,"unique_code"=>null],["lon"=>113.085122,"lat"=>28.304756,"name"=>null,"unique_code"=>null],["lon"=>113.08541,"lat"=>28.318112,"name"=>null,"unique_code"=>null],["lon"=>113.087134,"lat"=>28.443454,"name"=>null,"unique_code"=>null],["lon"=>113.154256,"lat"=>28.595416,"name"=>null,"unique_code"=>null],["lon"=>113.156268,"lat"=>28.71827,"name"=>null,"unique_code"=>null],["lon"=>113.150519,"lat"=>28.75349,"name"=>"汨罗东站","unique_code"=>"G00159"],["lon"=>113.14247,"lat"=>28.793002,"name"=>null,"unique_code"=>null],["lon"=>113.151094,"lat"=>29.22336,"name"=>null,"unique_code"=>null],["lon"=>113.214047,"lat"=>29.37304,"name"=>"岳阳东站","unique_code"=>"G00164"]],
            [["lon"=>112.978016,"lat"=>27.862933,"name"=>"湘潭东","unique_code"=>"G00283"],["lon"=>112.98186,"lat"=>27.862645,"name"=>null,"unique_code"=>null],["lon"=>112.982328,"lat"=>27.862422,"name"=>null,"unique_code"=>null],["lon"=>113.009901,"lat"=>27.859452,"name"=>null,"unique_code"=>null],["lon"=>113.022931,"lat"=>27.870374,"name"=>null,"unique_code"=>null],["lon"=>113.046429,"lat"=>27.885237,"name"=>"十里冲","unique_code"=>"G00210"],["lon"=>113.072229,"lat"=>27.890441,"name"=>null,"unique_code"=>null],["lon"=>113.085523,"lat"=>27.886322,"name"=>null,"unique_code"=>null],["lon"=>113.115563,"lat"=>27.888749,"name"=>null,"unique_code"=>null],["lon"=>113.133475,"lat"=>27.881134,"name"=>null,"unique_code"=>null],["lon"=>113.140464,"lat"=>27.866493,"name"=>null,"unique_code"=>null],["lon"=>113.149842,"lat"=>27.857646,"name"=>null,"unique_code"=>null],["lon"=>113.160334,"lat"=>27.849247,"name"=>null,"unique_code"=>null],["lon"=>113.163879,"lat"=>27.842038,"name"=>null,"unique_code"=>null],["lon"=>113.176056,"lat"=>27.831287,"name"=>null,"unique_code"=>null],["lon"=>113.188201,"lat"=>27.831319,"name"=>null,"unique_code"=>null],["lon"=>113.209976,"lat"=>27.814547,"name"=>null,"unique_code"=>null],["lon"=>113.237141,"lat"=>27.818317,"name"=>null,"unique_code"=>null],["lon"=>113.244256,"lat"=>27.817997,"name"=>null,"unique_code"=>null],["lon"=>113.267611,"lat"=>27.807517,"name"=>null,"unique_code"=>null],["lon"=>113.310443,"lat"=>27.793202,"name"=>null,"unique_code"=>null],["lon"=>113.334086,"lat"=>27.77946,"name"=>null,"unique_code"=>null],["lon"=>113.365167,"lat"=>27.74305,"name"=>null,"unique_code"=>null],["lon"=>113.382451,"lat"=>27.739629,"name"=>"东冲铺","unique_code"=>"G00247"],["lon"=>113.422156,"lat"=>27.736176,"name"=>null,"unique_code"=>null],["lon"=>113.452411,"lat"=>27.711682,"name"=>null,"unique_code"=>null],["lon"=>113.499482,"lat"=>27.693004,"name"=>null,"unique_code"=>null],["lon"=>113.519173,"lat"=>27.675474,"name"=>"醴陵","unique_code"=>"G00246"],["lon"=>113.525317,"lat"=>27.670163,"name"=>null,"unique_code"=>null],["lon"=>113.526144,"lat"=>27.663604,"name"=>null,"unique_code"=>null],["lon"=>113.531917,"lat"=>27.658104,"name"=>"醴陵南","unique_code"=>"G00249"],["lon"=>113.537966,"lat"=>27.646614,"name"=>null,"unique_code"=>null],["lon"=>113.531534,"lat"=>27.645334,"name"=>null,"unique_code"=>null],["lon"=>113.530995,"lat"=>27.645302,"name"=>null,"unique_code"=>null],["lon"=>113.522946,"lat"=>27.639317,"name"=>null,"unique_code"=>null],["lon"=>113.52194,"lat"=>27.632085,"name"=>null,"unique_code"=>null],["lon"=>113.511519,"lat"=>27.614481,"name"=>null,"unique_code"=>null],["lon"=>113.501854,"lat"=>27.576991,"name"=>null,"unique_code"=>null],["lon"=>113.501782,"lat"=>27.564247,"name"=>null,"unique_code"=>null],["lon"=>113.507782,"lat"=>27.548842,"name"=>null,"unique_code"=>null],["lon"=>113.498835,"lat"=>27.535677,"name"=>null,"unique_code"=>null],["lon"=>113.499338,"lat"=>27.505274,"name"=>null,"unique_code"=>null],["lon"=>113.508824,"lat"=>27.483451,"name"=>null,"unique_code"=>null],["lon"=>113.50825,"lat"=>27.466657,"name"=>null,"unique_code"=>null],["lon"=>113.499913,"lat"=>27.438222,"name"=>null,"unique_code"=>null],["lon"=>113.484498,"lat"=>27.415104,"name"=>null,"unique_code"=>null],["lon"=>113.483959,"lat"=>27.379759,"name"=>null,"unique_code"=>null],["lon"=>113.492942,"lat"=>27.369718,"name"=>null,"unique_code"=>null],["lon"=>113.492727,"lat"=>27.345911,"name"=>"皇图岭新","unique_code"=>"G00252"],["lon"=>113.474869,"lat"=>27.315904,"name"=>null,"unique_code"=>null],["lon"=>113.476485,"lat"=>27.295874,"name"=>null,"unique_code"=>null],["lon"=>113.46894,"lat"=>27.27494,"name"=>null,"unique_code"=>null],["lon"=>113.436421,"lat"=>27.232388,"name"=>null,"unique_code"=>null],["lon"=>113.436421,"lat"=>27.232388,"name"=>null,"unique_code"=>null],["lon"=>113.395674,"lat"=>27.19165,"name"=>null,"unique_code"=>null],["lon"=>113.382666,"lat"=>27.15379,"name"=>null,"unique_code"=>null],["lon"=>113.385685,"lat"=>27.123313,"name"=>null,"unique_code"=>null],["lon"=>113.366713,"lat"=>27.043802,"name"=>null,"unique_code"=>null],["lon"=>113.361251,"lat"=>27.017478,"name"=>"攸县新","unique_code"=>"G00251"]],
            [["lon"=>113.174794,"lat"=>27.775185,"name"=>"株洲南","unique_code"=>"G00233"],["lon"=>113.179682,"lat"=>27.799279,"name"=>null,"unique_code"=>null],["lon"=>113.178946,"lat"=>27.802858,"name"=>null,"unique_code"=>null],["lon"=>113.171319,"lat"=>27.814912,"name"=>null,"unique_code"=>null],["lon"=>113.169702,"lat"=>27.827212,"name"=>null,"unique_code"=>null],["lon"=>113.167223,"lat"=>27.835007,"name"=>null,"unique_code"=>null],["lon"=>113.164133,"lat"=>27.842226,"name"=>null,"unique_code"=>null],["lon"=>113.160737,"lat"=>27.849588,"name"=>null,"unique_code"=>null],["lon"=>113.159345,"lat"=>27.857293,"name"=>null,"unique_code"=>null],["lon"=>113.160764,"lat"=>27.863832,"name"=>null,"unique_code"=>null],["lon"=>113.163549,"lat"=>27.87576,"name"=>null,"unique_code"=>null],["lon"=>113.161536,"lat"=>27.885945,"name"=>null,"unique_code"=>null],["lon"=>113.15223,"lat"=>27.895236,"name"=>null,"unique_code"=>null],["lon"=>113.136204,"lat"=>27.902834,"name"=>"田心东站","unique_code"=>"G00097"],["lon"=>113.103326,"lat"=>27.911485,"name"=>null,"unique_code"=>null],["lon"=>113.100631,"lat"=>27.912794,"name"=>null,"unique_code"=>null],["lon"=>113.076377,"lat"=>27.931051,"name"=>null,"unique_code"=>null],["lon"=>113.06178,"lat"=>27.940769,"name"=>null,"unique_code"=>null],["lon"=>113.041316,"lat"=>27.956676,"name"=>null,"unique_code"=>null],["lon"=>113.0192,"lat"=>27.98775,"name"=>null,"unique_code"=>null],["lon"=>113.012786,"lat"=>27.996155,"name"=>"暮云站","unique_code"=>"G00222"],["lon"=>113.003012,"lat"=>28.011719,"name"=>null,"unique_code"=>null],["lon"=>113.001791,"lat"=>28.017395,"name"=>null,"unique_code"=>null],["lon"=>113.003084,"lat"=>28.05674,"name"=>null,"unique_code"=>null],["lon"=>112.996904,"lat"=>28.075068,"name"=>null,"unique_code"=>null],["lon"=>112.998305,"lat"=>28.079626,"name"=>null,"unique_code"=>null],["lon"=>113.009561,"lat"=>28.09454,"name"=>null,"unique_code"=>null],["lon"=>113.020808,"lat"=>28.101909,"name"=>"洞井站","unique_code"=>"G00223"],["lon"=>113.033627,"lat"=>28.113834,"name"=>null,"unique_code"=>null],["lon"=>113.039493,"lat"=>28.135696,"name"=>null,"unique_code"=>null],["lon"=>113.028982,"lat"=>28.158055,"name"=>null,"unique_code"=>null],["lon"=>113.020718,"lat"=>28.170276,"name"=>null,"unique_code"=>null],["lon"=>113.020332,"lat"=>28.178802,"name"=>null,"unique_code"=>null],["lon"=>113.019874,"lat"=>28.180593,"name"=>null,"unique_code"=>null],["lon"=>113.021347,"lat"=>28.199839,"name"=>null,"unique_code"=>null],["lon"=>113.02229,"lat"=>28.2125,"name"=>null,"unique_code"=>null],["lon"=>113.020467,"lat"=>28.217522,"name"=>null,"unique_code"=>null],["lon"=>113.008611,"lat"=>28.227078,"name"=>"丝茅冲","unique_code"=>"G00273"],["lon"=>113.00374,"lat"=>28.229091,"name"=>null,"unique_code"=>null],["lon"=>112.999302,"lat"=>28.229298,"name"=>null,"unique_code"=>null],["lon"=>112.984822,"lat"=>28.229123,"name"=>null,"unique_code"=>null],["lon"=>112.958214,"lat"=>28.235726,"name"=>null,"unique_code"=>null],["lon"=>112.94041,"lat"=>28.23649,"name"=>null,"unique_code"=>null],["lon"=>112.910712,"lat"=>28.236602,"name"=>null,"unique_code"=>null],["lon"=>112.902393,"lat"=>28.238559,"name"=>null,"unique_code"=>null],["lon"=>112.876576,"lat"=>28.252735,"name"=>null,"unique_code"=>null],["lon"=>112.842027,"lat"=>28.263537,"name"=>null,"unique_code"=>null],["lon"=>112.832397,"lat"=>28.267848,"name"=>"长沙西","unique_code"=>"G00220"],["lon"=>112.808538,"lat"=>28.27596,"name"=>null,"unique_code"=>null],["lon"=>112.808538,"lat"=>28.27596,"name"=>null,"unique_code"=>null],["lon"=>112.780942,"lat"=>28.310534,"name"=>null,"unique_code"=>null],["lon"=>112.778463,"lat"=>28.312061,"name"=>null,"unique_code"=>null],["lon"=>112.772965,"lat"=>28.313078,"name"=>null,"unique_code"=>null],["lon"=>112.756113,"lat"=>28.31125,"name"=>null,"unique_code"=>null],["lon"=>112.720864,"lat"=>28.306702,"name"=>null,"unique_code"=>null],["lon"=>112.699143,"lat"=>28.291674,"name"=>null,"unique_code"=>null],["lon"=>112.652431,"lat"=>28.296382,"name"=>null,"unique_code"=>null],["lon"=>112.624404,"lat"=>28.312029,"name"=>null,"unique_code"=>null],["lon"=>112.597239,"lat"=>28.296382,"name"=>null,"unique_code"=>null],["lon"=>112.586315,"lat"=>28.295109,"name"=>"宁乡","unique_code"=>"G00147"],["lon"=>112.586315,"lat"=>28.295109,"name"=>null,"unique_code"=>null],["lon"=>112.562169,"lat"=>28.308849,"name"=>null,"unique_code"=>null],["lon"=>112.521063,"lat"=>28.324113,"name"=>null,"unique_code"=>null],["lon"=>112.511289,"lat"=>28.326148,"name"=>null,"unique_code"=>null],["lon"=>112.485849,"lat"=>28.356287,"name"=>null,"unique_code"=>null],["lon"=>112.446467,"lat"=>28.432041,"name"=>null,"unique_code"=>null],["lon"=>112.425483,"lat"=>28.472819,"name"=>null,"unique_code"=>null],["lon"=>112.419159,"lat"=>28.476629,"name"=>null,"unique_code"=>null],["lon"=>112.412404,"lat"=>28.496567,"name"=>null,"unique_code"=>null],["lon"=>112.403073,"lat"=>28.505438,"name"=>null,"unique_code"=>null],["lon"=>112.397312,"lat"=>28.513263,"name"=>"益阳东","unique_code"=>"G00153"],["lon"=>112.387467,"lat"=>28.530719,"name"=>null,"unique_code"=>null],["lon"=>112.377909,"lat"=>28.532877,"name"=>null,"unique_code"=>null],["lon"=>112.373022,"lat"=>28.540049,"name"=>null,"unique_code"=>null],["lon"=>112.348803,"lat"=>28.544364,"name"=>"益阳","unique_code"=>"G00152"],["lon"=>112.330047,"lat"=>28.546331,"name"=>null,"unique_code"=>null],["lon"=>112.314309,"lat"=>28.540493,"name"=>null,"unique_code"=>null],["lon"=>112.267165,"lat"=>28.542777,"name"=>null,"unique_code"=>null],["lon"=>112.253942,"lat"=>28.548743,"name"=>"益阳西","unique_code"=>"G00155"],["lon"=>112.235258,"lat"=>28.555279,"name"=>null,"unique_code"=>null],["lon"=>112.235258,"lat"=>28.555279,"name"=>null,"unique_code"=>null],["lon"=>112.190342,"lat"=>28.567715,"name"=>null,"unique_code"=>null],["lon"=>112.126239,"lat"=>28.573044,"name"=>null,"unique_code"=>null],["lon"=>112.073922,"lat"=>28.62645,"name"=>null,"unique_code"=>null],["lon"=>112.008525,"lat"=>28.672984,"name"=>null,"unique_code"=>null],["lon"=>111.954062,"lat"=>28.775326,"name"=>"汉寿","unique_code"=>"G00144"],["lon"=>111.881756,"lat"=>28.806888,"name"=>null,"unique_code"=>null],["lon"=>111.784883,"lat"=>28.867892,"name"=>null,"unique_code"=>null],["lon"=>111.714456,"lat"=>28.95225,"name"=>null,"unique_code"=>null],["lon"=>111.726673,"lat"=>28.979427,"name"=>null,"unique_code"=>null],["lon"=>111.722649,"lat"=>28.994845,"name"=>null,"unique_code"=>null],["lon"=>111.725208,"lat"=>28.995122,"name"=>null,"unique_code"=>null],["lon"=>111.735269,"lat"=>29.049447,"name"=>null,"unique_code"=>null],["lon"=>111.721327,"lat"=>29.075083,"name"=>null,"unique_code"=>null],["lon"=>111.701169,"lat"=>29.07704,"name"=>"常德","unique_code"=>"G00088"],["lon"=>111.687048,"lat"=>29.083953,"name"=>null,"unique_code"=>null],["lon"=>111.684533,"lat"=>29.092002,"name"=>null,"unique_code"=>null],["lon"=>111.67228,"lat"=>29.11302,"name"=>null,"unique_code"=>null],["lon"=>111.623987,"lat"=>29.149366,"name"=>null,"unique_code"=>null],["lon"=>111.594451,"lat"=>29.167597,"name"=>null,"unique_code"=>null],["lon"=>111.623628,"lat"=>29.393416,"name"=>null,"unique_code"=>null],["lon"=>111.614141,"lat"=>29.440986,"name"=>"临澧","unique_code"=>"G00091"]],
            [["lon"=>112.535646,"lat"=>27.927119,"name"=>"韶山","unique_code"=>"G00135"],["lon"=>112.544578,"lat"=>27.917964,"name"=>null,"unique_code"=>null],["lon"=>112.553094,"lat"=>27.916209,"name"=>null,"unique_code"=>null],["lon"=>112.564916,"lat"=>27.908196,"name"=>null,"unique_code"=>null],["lon"=>112.577528,"lat"=>27.905132,"name"=>null,"unique_code"=>null],["lon"=>112.5859,"lat"=>27.897278,"name"=>null,"unique_code"=>null],["lon"=>112.599698,"lat"=>27.884029,"name"=>null,"unique_code"=>null],["lon"=>112.626216,"lat"=>27.867871,"name"=>null,"unique_code"=>null],["lon"=>112.637175,"lat"=>27.852925,"name"=>null,"unique_code"=>null],["lon"=>112.638721,"lat"=>27.812003,"name"=>null,"unique_code"=>null],["lon"=>112.638792,"lat"=>27.810789,"name"=>null,"unique_code"=>null],["lon"=>112.649644,"lat"=>27.793023,"name"=>null,"unique_code"=>null],["lon"=>112.65586,"lat"=>27.791681,"name"=>null,"unique_code"=>null],["lon"=>112.658555,"lat"=>27.791681,"name"=>"向韶","unique_code"=>"G00141"],["lon"=>112.670664,"lat"=>27.79101,"name"=>null,"unique_code"=>null],["lon"=>112.676378,"lat"=>27.793055,"name"=>null,"unique_code"=>null],["lon"=>112.702824,"lat"=>27.816061,"name"=>null,"unique_code"=>null],["lon"=>112.722083,"lat"=>27.825805,"name"=>"云湖桥","unique_code"=>"G00143"],["lon"=>112.756902,"lat"=>27.845898,"name"=>null,"unique_code"=>null],["lon"=>112.77706,"lat"=>27.850242,"name"=>null,"unique_code"=>null],["lon"=>112.782845,"lat"=>27.856438,"name"=>null,"unique_code"=>null],["lon"=>112.830563,"lat"=>27.865604,"name"=>null,"unique_code"=>null],["lon"=>112.878353,"lat"=>27.876716,"name"=>null,"unique_code"=>null],["lon"=>112.902643,"lat"=>27.88537,"name"=>null,"unique_code"=>null],["lon"=>112.919459,"lat"=>27.882304,"name"=>"湘潭","unique_code"=>"G00139"],["lon"=>112.939406,"lat"=>27.877165,"name"=>null,"unique_code"=>null],["lon"=>112.950922,"lat"=>27.863498,"name"=>null,"unique_code"=>null],["lon"=>112.969391,"lat"=>27.860895,"name"=>null,"unique_code"=>null],["lon"=>112.976533,"lat"=>27.86347,"name"=>null,"unique_code"=>null],["lon"=>112.985201,"lat"=>27.876934,"name"=>null,"unique_code"=>null],["lon"=>112.985201,"lat"=>27.876934,"name"=>"荷塘","unique_code"=>"G00225"],["lon"=>113.007335,"lat"=>27.900177,"name"=>null,"unique_code"=>null],["lon"=>113.034716,"lat"=>27.927565,"name"=>null,"unique_code"=>null],["lon"=>113.034069,"lat"=>27.970006,"name"=>null,"unique_code"=>null],["lon"=>113.012786,"lat"=>27.996155,"name"=>"暮云站","unique_code"=>"G00222"]],
            [["lon"=>111.828062,"lat"=>27.627249,"name"=>"杨市","unique_code"=>"G00110"],["lon"=>111.841918,"lat"=>27.623426,"name"=>null,"unique_code"=>null],["lon"=>111.864843,"lat"=>27.627683,"name"=>null,"unique_code"=>null],["lon"=>111.877922,"lat"=>27.643941,"name"=>null,"unique_code"=>null],["lon"=>111.903362,"lat"=>27.690268,"name"=>null,"unique_code"=>null],["lon"=>111.952805,"lat"=>27.716494,"name"=>"百亩井","unique_code"=>"G00101"],["lon"=>111.965054,"lat"=>27.721387,"name"=>null,"unique_code"=>null],["lon"=>111.969087,"lat"=>27.723405,"name"=>null,"unique_code"=>null],["lon"=>111.989191,"lat"=>27.732366,"name"=>null,"unique_code"=>null],["lon"=>111.996566,"lat"=>27.736491,"name"=>null,"unique_code"=>null],["lon"=>112.008675,"lat"=>27.742965,"name"=>null,"unique_code"=>null],["lon"=>112.012035,"lat"=>27.746003,"name"=>"娄底","unique_code"=>"G00105"],["lon"=>112.01875,"lat"=>27.753212,"name"=>null,"unique_code"=>null],["lon"=>112.022105,"lat"=>27.754922,"name"=>null,"unique_code"=>null],["lon"=>112.027943,"lat"=>27.755569,"name"=>null,"unique_code"=>null],["lon"=>112.03146,"lat"=>27.755433,"name"=>null,"unique_code"=>null],["lon"=>112.038467,"lat"=>27.757335,"name"=>null,"unique_code"=>null],["lon"=>112.039204,"lat"=>27.757295,"name"=>null,"unique_code"=>null],["lon"=>112.044647,"lat"=>27.756232,"name"=>null,"unique_code"=>null],["lon"=>112.045375,"lat"=>27.75628,"name"=>null,"unique_code"=>null],["lon"=>112.056415,"lat"=>27.76225,"name"=>null,"unique_code"=>null],["lon"=>112.063152,"lat"=>27.763457,"name"=>null,"unique_code"=>null],["lon"=>112.071511,"lat"=>27.768685,"name"=>null,"unique_code"=>null],["lon"=>112.09016,"lat"=>27.77332,"name"=>null,"unique_code"=>null],["lon"=>112.092729,"lat"=>27.773735,"name"=>null,"unique_code"=>null],["lon"=>112.104623,"lat"=>27.768653,"name"=>"胜昔桥","unique_code"=>"G00226"],["lon"=>112.149197,"lat"=>27.757929,"name"=>null,"unique_code"=>null],["lon"=>112.177655,"lat"=>27.748898,"name"=>null,"unique_code"=>null],["lon"=>112.183135,"lat"=>27.742919,"name"=>null,"unique_code"=>null],["lon"=>112.206118,"lat"=>27.737055,"name"=>"棋梓桥","unique_code"=>"G00134"],["lon"=>112.217203,"lat"=>27.734944,"name"=>null,"unique_code"=>null],["lon"=>112.231217,"lat"=>27.728485,"name"=>null,"unique_code"=>null],["lon"=>112.257196,"lat"=>27.725192,"name"=>null,"unique_code"=>null],["lon"=>112.259945,"lat"=>27.723545,"name"=>null,"unique_code"=>null],["lon"=>112.270689,"lat"=>27.723705,"name"=>"普安堂","unique_code"=>"G00133"],["lon"=>112.288726,"lat"=>27.724497,"name"=>null,"unique_code"=>null],["lon"=>112.318658,"lat"=>27.718166,"name"=>null,"unique_code"=>null],["lon"=>112.335295,"lat"=>27.717014,"name"=>null,"unique_code"=>null],["lon"=>112.318658,"lat"=>27.718166,"name"=>null,"unique_code"=>null],["lon"=>112.378018,"lat"=>27.743138,"name"=>null,"unique_code"=>null],["lon"=>112.430048,"lat"=>27.737831,"name"=>null,"unique_code"=>null],["lon"=>112.430048,"lat"=>27.737831,"name"=>null,"unique_code"=>null],["lon"=>112.521531,"lat"=>27.74787,"name"=>null,"unique_code"=>null],["lon"=>112.535697,"lat"=>27.750385,"name"=>"湘乡","unique_code"=>"G00140"],["lon"=>112.552361,"lat"=>27.754679,"name"=>null,"unique_code"=>null],["lon"=>112.559368,"lat"=>27.763278,"name"=>null,"unique_code"=>null],["lon"=>112.569968,"lat"=>27.76558,"name"=>null,"unique_code"=>null],["lon"=>112.641293,"lat"=>27.792139,"name"=>null,"unique_code"=>null],["lon"=>112.651929,"lat"=>27.791692,"name"=>null,"unique_code"=>null],["lon"=>112.658613,"lat"=>27.791596,"name"=>"向韶","unique_code"=>"G00141"],["lon"=>112.6718,"lat"=>27.791468,"name"=>null,"unique_code"=>null],["lon"=>112.703312,"lat"=>27.81588,"name"=>null,"unique_code"=>null],["lon"=>112.722069,"lat"=>27.825816,"name"=>"云湖桥","unique_code"=>"G00143"],["lon"=>112.757175,"lat"=>27.846005,"name"=>null,"unique_code"=>null],["lon"=>112.777189,"lat"=>27.85038,"name"=>null,"unique_code"=>null],["lon"=>112.794077,"lat"=>27.855586,"name"=>null,"unique_code"=>null],["lon"=>112.823039,"lat"=>27.866349,"name"=>null,"unique_code"=>null],["lon"=>112.866552,"lat"=>27.874907,"name"=>null,"unique_code"=>null],["lon"=>112.880171,"lat"=>27.877398,"name"=>null,"unique_code"=>null],["lon"=>112.919588,"lat"=>27.882315,"name"=>"湘潭","unique_code"=>"G00139"],["lon"=>112.939405,"lat"=>27.877206,"name"=>null,"unique_code"=>null],["lon"=>112.950814,"lat"=>27.863491,"name"=>null,"unique_code"=>null],["lon"=>112.976533,"lat"=>27.86347,"name"=>null,"unique_code"=>null],["lon"=>113.003382,"lat"=>27.858125,"name"=>null,"unique_code"=>null],["lon"=>113.072732,"lat"=>27.890249,"name"=>null,"unique_code"=>null],["lon"=>113.034536,"lat"=>27.877126,"name"=>null,"unique_code"=>null],["lon"=>113.046429,"lat"=>27.885237,"name"=>"十里冲","unique_code"=>"G00210"],["lon"=>113.072229,"lat"=>27.890441,"name"=>null,"unique_code"=>null],["lon"=>113.085523,"lat"=>27.886322,"name"=>null,"unique_code"=>null],["lon"=>113.115563,"lat"=>27.888749,"name"=>null,"unique_code"=>null],["lon"=>113.133475,"lat"=>27.881134,"name"=>null,"unique_code"=>null],["lon"=>113.140464,"lat"=>27.866493,"name"=>null,"unique_code"=>null],["lon"=>113.149842,"lat"=>27.857646,"name"=>null,"unique_code"=>null],["lon"=>113.160334,"lat"=>27.849247,"name"=>null,"unique_code"=>null],["lon"=>113.163879,"lat"=>27.842038,"name"=>null,"unique_code"=>null],["lon"=>113.176056,"lat"=>27.831287,"name"=>null,"unique_code"=>null],["lon"=>113.188201,"lat"=>27.831319,"name"=>null,"unique_code"=>null],["lon"=>113.209976,"lat"=>27.814547,"name"=>null,"unique_code"=>null],["lon"=>113.237141,"lat"=>27.818317,"name"=>null,"unique_code"=>null],["lon"=>113.244256,"lat"=>27.817997,"name"=>null,"unique_code"=>null],["lon"=>113.267611,"lat"=>27.807517,"name"=>null,"unique_code"=>null],["lon"=>113.310443,"lat"=>27.793202,"name"=>null,"unique_code"=>null],["lon"=>113.334086,"lat"=>27.77946,"name"=>null,"unique_code"=>null],["lon"=>113.365167,"lat"=>27.74305,"name"=>null,"unique_code"=>null],["lon"=>113.382451,"lat"=>27.739629,"name"=>"东冲铺","unique_code"=>"G00247"],["lon"=>113.422156,"lat"=>27.736176,"name"=>null,"unique_code"=>null],["lon"=>113.452411,"lat"=>27.711682,"name"=>null,"unique_code"=>null],["lon"=>113.499482,"lat"=>27.693004,"name"=>null,"unique_code"=>null],["lon"=>113.519173,"lat"=>27.675474,"name"=>"醴陵","unique_code"=>"G00246"],["lon"=>113.525317,"lat"=>27.670163,"name"=>null,"unique_code"=>null],["lon"=>113.526144,"lat"=>27.663604,"name"=>null,"unique_code"=>null],["lon"=>113.539978,"lat"=>27.650582,"name"=>"醴陵南","unique_code"=>"G00249"],["lon"=>113.537966,"lat"=>27.646614,"name"=>null,"unique_code"=>null],["lon"=>113.531534,"lat"=>27.645334,"name"=>null,"unique_code"=>null],["lon"=>113.530995,"lat"=>27.645302,"name"=>null,"unique_code"=>null],["lon"=>113.522946,"lat"=>27.639317,"name"=>null,"unique_code"=>null],["lon"=>113.52194,"lat"=>27.632085,"name"=>null,"unique_code"=>null],["lon"=>113.511519,"lat"=>27.614481,"name"=>null,"unique_code"=>null],["lon"=>113.501854,"lat"=>27.576991,"name"=>null,"unique_code"=>null],["lon"=>113.501782,"lat"=>27.564247,"name"=>null,"unique_code"=>null],["lon"=>113.507782,"lat"=>27.548842,"name"=>null,"unique_code"=>null],["lon"=>113.498835,"lat"=>27.535677,"name"=>null,"unique_code"=>null],["lon"=>113.499338,"lat"=>27.505274,"name"=>null,"unique_code"=>null],["lon"=>113.508824,"lat"=>27.483451,"name"=>null,"unique_code"=>null],["lon"=>113.50825,"lat"=>27.466657,"name"=>null,"unique_code"=>null],["lon"=>113.499913,"lat"=>27.438222,"name"=>null,"unique_code"=>null],["lon"=>113.484498,"lat"=>27.415104,"name"=>null,"unique_code"=>null],["lon"=>113.483959,"lat"=>27.379759,"name"=>null,"unique_code"=>null],["lon"=>113.492942,"lat"=>27.369718,"name"=>null,"unique_code"=>null],["lon"=>113.492727,"lat"=>27.345911,"name"=>"皇图岭新","unique_code"=>"G00252"],["lon"=>113.474869,"lat"=>27.315904,"name"=>null,"unique_code"=>null],["lon"=>113.476485,"lat"=>27.295874,"name"=>null,"unique_code"=>null],["lon"=>113.46894,"lat"=>27.27494,"name"=>null,"unique_code"=>null],["lon"=>113.436421,"lat"=>27.232388,"name"=>null,"unique_code"=>null],["lon"=>113.436421,"lat"=>27.232388,"name"=>null,"unique_code"=>null],["lon"=>113.395674,"lat"=>27.19165,"name"=>null,"unique_code"=>null],["lon"=>113.382666,"lat"=>27.15379,"name"=>null,"unique_code"=>null],["lon"=>113.385685,"lat"=>27.123313,"name"=>null,"unique_code"=>null],["lon"=>113.366713,"lat"=>27.043802,"name"=>null,"unique_code"=>null],["lon"=>113.361251,"lat"=>27.017478,"name"=>"攸县新","unique_code"=>"G00251"]],
            [["lon"=>111.828062,"lat"=>27.627249,"name"=>"杨市","unique_code"=>"G00110"],["lon"=>111.841918,"lat"=>27.623426,"name"=>null,"unique_code"=>null],["lon"=>111.864843,"lat"=>27.627683,"name"=>null,"unique_code"=>null],["lon"=>111.877922,"lat"=>27.643941,"name"=>null,"unique_code"=>null],["lon"=>111.903362,"lat"=>27.690268,"name"=>null,"unique_code"=>null],["lon"=>111.952805,"lat"=>27.716494,"name"=>"百亩井","unique_code"=>"G00101"],["lon"=>111.965087,"lat"=>27.7214,"name"=>null,"unique_code"=>null],["lon"=>111.972021,"lat"=>27.724854,"name"=>null,"unique_code"=>null],["lon"=>111.976531,"lat"=>27.726996,"name"=>null,"unique_code"=>null],["lon"=>111.980825,"lat"=>27.728819,"name"=>null,"unique_code"=>null],["lon"=>111.992,"lat"=>27.73451,"name"=>null,"unique_code"=>null],["lon"=>111.99845,"lat"=>27.739227,"name"=>null,"unique_code"=>null],["lon"=>112.003597,"lat"=>27.740473,"name"=>null,"unique_code"=>null],["lon"=>112.005941,"lat"=>27.741748,"name"=>null,"unique_code"=>null],["lon"=>112.014192,"lat"=>27.749197,"name"=>null,"unique_code"=>null],["lon"=>112.019344,"lat"=>27.753817,"name"=>null,"unique_code"=>null],["lon"=>112.022012,"lat"=>27.75504,"name"=>null,"unique_code"=>null],["lon"=>112.032266,"lat"=>27.755655,"name"=>null,"unique_code"=>null],["lon"=>112.03877,"lat"=>27.757589,"name"=>null,"unique_code"=>null],["lon"=>112.044618,"lat"=>27.75651,"name"=>null,"unique_code"=>null],["lon"=>112.055883,"lat"=>27.762312,"name"=>null,"unique_code"=>null],["lon"=>112.071073,"lat"=>27.768873,"name"=>null,"unique_code"=>null],["lon"=>112.076777,"lat"=>27.770279,"name"=>null,"unique_code"=>null],["lon"=>112.087916,"lat"=>27.779069,"name"=>null,"unique_code"=>null],["lon"=>112.087359,"lat"=>27.786707,"name"=>null,"unique_code"=>null],["lon"=>112.069465,"lat"=>27.823836,"name"=>null,"unique_code"=>null],["lon"=>112.069465,"lat"=>27.823836,"name"=>"壶天","unique_code"=>"G00103"],["lon"=>112.061093,"lat"=>27.8492,"name"=>null,"unique_code"=>null],["lon"=>112.065225,"lat"=>27.873822,"name"=>null,"unique_code"=>null],["lon"=>112.0635,"lat"=>27.928285,"name"=>null,"unique_code"=>null],["lon"=>112.081323,"lat"=>27.941434,"name"=>null,"unique_code"=>null],["lon"=>112.136587,"lat"=>28.001221,"name"=>null,"unique_code"=>null],["lon"=>112.220955,"lat"=>28.069581,"name"=>null,"unique_code"=>null],["lon"=>112.23023,"lat"=>28.082981,"name"=>"老粮仓","unique_code"=>"G00104"],["lon"=>112.235903,"lat"=>28.100813,"name"=>null,"unique_code"=>null],["lon"=>112.234286,"lat"=>28.108205,"name"=>null,"unique_code"=>null],["lon"=>112.239029,"lat"=>28.144618,"name"=>null,"unique_code"=>null],["lon"=>112.235149,"lat"=>28.154746,"name"=>"横市","unique_code"=>"G00102"],["lon"=>112.231304,"lat"=>28.164841,"name"=>null,"unique_code"=>null],["lon"=>112.223111,"lat"=>28.21314,"name"=>null,"unique_code"=>null],["lon"=>112.232993,"lat"=>28.254703,"name"=>null,"unique_code"=>null],["lon"=>112.232993,"lat"=>28.254703,"name"=>null,"unique_code"=>null],["lon"=>112.255702,"lat"=>28.291542,"name"=>null,"unique_code"=>null],["lon"=>112.257606,"lat"=>28.294913,"name"=>null,"unique_code"=>null],["lon"=>112.25828,"lat"=>28.297879,"name"=>"灰山港","unique_code"=>"G00145"],["lon"=>112.262637,"lat"=>28.336126,"name"=>null,"unique_code"=>null],["lon"=>112.263319,"lat"=>28.358824,"name"=>null,"unique_code"=>null],["lon"=>112.276291,"lat"=>28.369854,"name"=>null,"unique_code"=>null],["lon"=>112.307804,"lat"=>28.408686,"name"=>"泥江口","unique_code"=>"G00146"],["lon"=>112.322805,"lat"=>28.427612,"name"=>null,"unique_code"=>null],["lon"=>112.335561,"lat"=>28.431265,"name"=>null,"unique_code"=>null],["lon"=>112.349898,"lat"=>28.45334,"name"=>null,"unique_code"=>null],["lon"=>112.370631,"lat"=>28.48449,"name"=>null,"unique_code"=>null],["lon"=>112.390897,"lat"=>28.492109,"name"=>null,"unique_code"=>null],["lon"=>112.403042,"lat"=>28.499792,"name"=>null,"unique_code"=>null],["lon"=>112.403073,"lat"=>28.505438,"name"=>null,"unique_code"=>null],["lon"=>112.397312,"lat"=>28.513263,"name"=>"益阳东","unique_code"=>"G00153"],["lon"=>112.387467,"lat"=>28.530719,"name"=>null,"unique_code"=>null],["lon"=>112.377909,"lat"=>28.532877,"name"=>null,"unique_code"=>null],["lon"=>112.373022,"lat"=>28.540049,"name"=>null,"unique_code"=>null],["lon"=>112.348803,"lat"=>28.544364,"name"=>"益阳","unique_code"=>"G00152"],["lon"=>112.330047,"lat"=>28.546331,"name"=>null,"unique_code"=>null],["lon"=>112.314309,"lat"=>28.540493,"name"=>null,"unique_code"=>null],["lon"=>112.267165,"lat"=>28.542777,"name"=>null,"unique_code"=>null],["lon"=>112.253942,"lat"=>28.548743,"name"=>"益阳西","unique_code"=>"G00155"],["lon"=>112.235258,"lat"=>28.555279,"name"=>null,"unique_code"=>null],["lon"=>112.235258,"lat"=>28.555279,"name"=>null,"unique_code"=>null],["lon"=>112.190342,"lat"=>28.567715,"name"=>null,"unique_code"=>null],["lon"=>112.126239,"lat"=>28.573044,"name"=>null,"unique_code"=>null],["lon"=>112.073922,"lat"=>28.62645,"name"=>null,"unique_code"=>null],["lon"=>112.008525,"lat"=>28.672984,"name"=>null,"unique_code"=>null],["lon"=>111.954062,"lat"=>28.775326,"name"=>"汉寿","unique_code"=>"G00144"],["lon"=>111.881756,"lat"=>28.806888,"name"=>null,"unique_code"=>null],["lon"=>111.784883,"lat"=>28.867892,"name"=>null,"unique_code"=>null],["lon"=>111.714456,"lat"=>28.95225,"name"=>null,"unique_code"=>null],["lon"=>111.726673,"lat"=>28.979427,"name"=>null,"unique_code"=>null],["lon"=>111.722649,"lat"=>28.994845,"name"=>null,"unique_code"=>null],["lon"=>111.725208,"lat"=>28.995122,"name"=>null,"unique_code"=>null],["lon"=>111.735269,"lat"=>29.049447,"name"=>null,"unique_code"=>null],["lon"=>111.721327,"lat"=>29.075083,"name"=>null,"unique_code"=>null],["lon"=>111.701169,"lat"=>29.07704,"name"=>"常德","unique_code"=>"G00088"],["lon"=>111.687048,"lat"=>29.083953,"name"=>null,"unique_code"=>null],["lon"=>111.684533,"lat"=>29.092002,"name"=>null,"unique_code"=>null],["lon"=>111.67228,"lat"=>29.11302,"name"=>null,"unique_code"=>null],["lon"=>111.623987,"lat"=>29.149366,"name"=>null,"unique_code"=>null],["lon"=>111.594451,"lat"=>29.167597,"name"=>null,"unique_code"=>null],["lon"=>111.623628,"lat"=>29.393416,"name"=>null,"unique_code"=>null],["lon"=>111.614141,"lat"=>29.440986,"name"=>"临澧","unique_code"=>"G00091"]],
            [["lon"=>111.963816,"lat"=>27.692277,"name"=>"娄底西","unique_code"=>"G00287"],["lon"=>111.966185,"lat"=>27.706336,"name"=>null,"unique_code"=>null],["lon"=>111.968233,"lat"=>27.717988,"name"=>null,"unique_code"=>null],["lon"=>111.970102,"lat"=>27.721506,"name"=>null,"unique_code"=>null],["lon"=>111.973704,"lat"=>27.723824,"name"=>null,"unique_code"=>null],["lon"=>111.976228,"lat"=>27.725471,"name"=>null,"unique_code"=>null],["lon"=>111.979668,"lat"=>27.727885,"name"=>null,"unique_code"=>null],["lon"=>111.990754,"lat"=>27.73261,"name"=>null,"unique_code"=>null],["lon"=>111.992757,"lat"=>27.734041,"name"=>null,"unique_code"=>null],["lon"=>112.009483,"lat"=>27.743001,"name"=>null,"unique_code"=>null],["lon"=>112.015466,"lat"=>27.748644,"name"=>null,"unique_code"=>null],["lon"=>112.01875,"lat"=>27.753212,"name"=>null,"unique_code"=>null],["lon"=>112.022105,"lat"=>27.754922,"name"=>null,"unique_code"=>null],["lon"=>112.027943,"lat"=>27.755569,"name"=>null,"unique_code"=>null],["lon"=>112.03146,"lat"=>27.755433,"name"=>null,"unique_code"=>null],["lon"=>112.038467,"lat"=>27.757335,"name"=>null,"unique_code"=>null],["lon"=>112.039204,"lat"=>27.757295,"name"=>null,"unique_code"=>null],["lon"=>112.044647,"lat"=>27.756232,"name"=>null,"unique_code"=>null],["lon"=>112.045375,"lat"=>27.75628,"name"=>null,"unique_code"=>null],["lon"=>112.056415,"lat"=>27.76225,"name"=>null,"unique_code"=>null],["lon"=>112.063152,"lat"=>27.763457,"name"=>null,"unique_code"=>null],["lon"=>112.071511,"lat"=>27.768685,"name"=>null,"unique_code"=>null],["lon"=>112.09016,"lat"=>27.77332,"name"=>null,"unique_code"=>null],["lon"=>112.092729,"lat"=>27.773735,"name"=>null,"unique_code"=>null],["lon"=>112.104623,"lat"=>27.768653,"name"=>"胜昔桥","unique_code"=>"G00226"],["lon"=>112.149197,"lat"=>27.757929,"name"=>null,"unique_code"=>null],["lon"=>112.177655,"lat"=>27.748898,"name"=>null,"unique_code"=>null],["lon"=>112.183135,"lat"=>27.742919,"name"=>null,"unique_code"=>null],["lon"=>112.206118,"lat"=>27.737055,"name"=>"棋梓桥","unique_code"=>"G00134"],["lon"=>112.217203,"lat"=>27.734944,"name"=>null,"unique_code"=>null],["lon"=>112.231217,"lat"=>27.728485,"name"=>null,"unique_code"=>null],["lon"=>112.257196,"lat"=>27.725192,"name"=>null,"unique_code"=>null],["lon"=>112.259945,"lat"=>27.723545,"name"=>null,"unique_code"=>null],["lon"=>112.270689,"lat"=>27.723705,"name"=>"普安堂","unique_code"=>"G00133"],["lon"=>112.288726,"lat"=>27.724497,"name"=>null,"unique_code"=>null],["lon"=>112.318658,"lat"=>27.718166,"name"=>null,"unique_code"=>null],["lon"=>112.335295,"lat"=>27.717014,"name"=>null,"unique_code"=>null],["lon"=>112.318658,"lat"=>27.718166,"name"=>null,"unique_code"=>null],["lon"=>112.378018,"lat"=>27.743138,"name"=>null,"unique_code"=>null],["lon"=>112.430048,"lat"=>27.737831,"name"=>null,"unique_code"=>null],["lon"=>112.430048,"lat"=>27.737831,"name"=>null,"unique_code"=>null],["lon"=>112.521531,"lat"=>27.74787,"name"=>null,"unique_code"=>null],["lon"=>112.535697,"lat"=>27.750385,"name"=>"湘乡","unique_code"=>"G00140"],["lon"=>112.552361,"lat"=>27.754679,"name"=>null,"unique_code"=>null],["lon"=>112.559368,"lat"=>27.763278,"name"=>null,"unique_code"=>null],["lon"=>112.569968,"lat"=>27.76558,"name"=>null,"unique_code"=>null],["lon"=>112.641293,"lat"=>27.792139,"name"=>null,"unique_code"=>null],["lon"=>112.651929,"lat"=>27.791692,"name"=>null,"unique_code"=>null],["lon"=>112.658613,"lat"=>27.791596,"name"=>"向韶","unique_code"=>"G00141"],["lon"=>112.6718,"lat"=>27.791468,"name"=>null,"unique_code"=>null],["lon"=>112.703312,"lat"=>27.81588,"name"=>null,"unique_code"=>null],["lon"=>112.722069,"lat"=>27.825816,"name"=>"云湖桥","unique_code"=>"G00143"],["lon"=>112.757175,"lat"=>27.846005,"name"=>null,"unique_code"=>null],["lon"=>112.777189,"lat"=>27.85038,"name"=>null,"unique_code"=>null],["lon"=>112.794077,"lat"=>27.855586,"name"=>null,"unique_code"=>null],["lon"=>112.823039,"lat"=>27.866349,"name"=>null,"unique_code"=>null],["lon"=>112.866552,"lat"=>27.874907,"name"=>null,"unique_code"=>null],["lon"=>112.880171,"lat"=>27.877398,"name"=>null,"unique_code"=>null],["lon"=>112.919588,"lat"=>27.882315,"name"=>"湘潭","unique_code"=>"G00139"],["lon"=>112.939405,"lat"=>27.877206,"name"=>null,"unique_code"=>null],["lon"=>112.950814,"lat"=>27.863491,"name"=>null,"unique_code"=>null],["lon"=>112.976533,"lat"=>27.86347,"name"=>null,"unique_code"=>null],["lon"=>113.009901,"lat"=>27.859452,"name"=>null,"unique_code"=>null],["lon"=>113.072732,"lat"=>27.890249,"name"=>null,"unique_code"=>null],["lon"=>113.034536,"lat"=>27.877126,"name"=>null,"unique_code"=>null],["lon"=>113.046429,"lat"=>27.885237,"name"=>"十里冲","unique_code"=>"G00210"],["lon"=>113.072229,"lat"=>27.890441,"name"=>null,"unique_code"=>null],["lon"=>113.085523,"lat"=>27.886322,"name"=>null,"unique_code"=>null],["lon"=>113.115563,"lat"=>27.888749,"name"=>null,"unique_code"=>null],["lon"=>113.133475,"lat"=>27.881134,"name"=>null,"unique_code"=>null],["lon"=>113.140464,"lat"=>27.866493,"name"=>null,"unique_code"=>null],["lon"=>113.149842,"lat"=>27.857646,"name"=>null,"unique_code"=>null],["lon"=>113.160334,"lat"=>27.849247,"name"=>null,"unique_code"=>null],["lon"=>113.163879,"lat"=>27.842038,"name"=>null,"unique_code"=>null],["lon"=>113.176056,"lat"=>27.831287,"name"=>null,"unique_code"=>null],["lon"=>113.188201,"lat"=>27.831319,"name"=>null,"unique_code"=>null],["lon"=>113.209976,"lat"=>27.814547,"name"=>null,"unique_code"=>null],["lon"=>113.237141,"lat"=>27.818317,"name"=>null,"unique_code"=>null],["lon"=>113.244256,"lat"=>27.817997,"name"=>null,"unique_code"=>null],["lon"=>113.267611,"lat"=>27.807517,"name"=>null,"unique_code"=>null],["lon"=>113.310443,"lat"=>27.793202,"name"=>null,"unique_code"=>null],["lon"=>113.334086,"lat"=>27.77946,"name"=>null,"unique_code"=>null],["lon"=>113.365167,"lat"=>27.74305,"name"=>null,"unique_code"=>null],["lon"=>113.382451,"lat"=>27.739629,"name"=>"东冲铺","unique_code"=>"G00247"],["lon"=>113.422156,"lat"=>27.736176,"name"=>null,"unique_code"=>null],["lon"=>113.452411,"lat"=>27.711682,"name"=>null,"unique_code"=>null],["lon"=>113.499482,"lat"=>27.693004,"name"=>null,"unique_code"=>null],["lon"=>113.519173,"lat"=>27.675474,"name"=>"醴陵","unique_code"=>"G00246"],["lon"=>113.525317,"lat"=>27.670163,"name"=>null,"unique_code"=>null],["lon"=>113.526144,"lat"=>27.663604,"name"=>null,"unique_code"=>null],["lon"=>113.531917,"lat"=>27.658104,"name"=>"醴陵南","unique_code"=>"G00249"],["lon"=>113.537966,"lat"=>27.646614,"name"=>null,"unique_code"=>null],["lon"=>113.531534,"lat"=>27.645334,"name"=>null,"unique_code"=>null],["lon"=>113.530995,"lat"=>27.645302,"name"=>null,"unique_code"=>null],["lon"=>113.522946,"lat"=>27.639317,"name"=>null,"unique_code"=>null],["lon"=>113.52194,"lat"=>27.632085,"name"=>null,"unique_code"=>null],["lon"=>113.511519,"lat"=>27.614481,"name"=>null,"unique_code"=>null],["lon"=>113.501854,"lat"=>27.576991,"name"=>null,"unique_code"=>null],["lon"=>113.501782,"lat"=>27.564247,"name"=>null,"unique_code"=>null],["lon"=>113.507782,"lat"=>27.548842,"name"=>null,"unique_code"=>null],["lon"=>113.498835,"lat"=>27.535677,"name"=>null,"unique_code"=>null],["lon"=>113.499338,"lat"=>27.505274,"name"=>null,"unique_code"=>null],["lon"=>113.508824,"lat"=>27.483451,"name"=>null,"unique_code"=>null],["lon"=>113.50825,"lat"=>27.466657,"name"=>null,"unique_code"=>null],["lon"=>113.499913,"lat"=>27.438222,"name"=>null,"unique_code"=>null],["lon"=>113.484498,"lat"=>27.415104,"name"=>null,"unique_code"=>null],["lon"=>113.483959,"lat"=>27.379759,"name"=>null,"unique_code"=>null],["lon"=>113.492942,"lat"=>27.369718,"name"=>null,"unique_code"=>null],["lon"=>113.492727,"lat"=>27.345911,"name"=>"皇图岭新","unique_code"=>"G00252"],["lon"=>113.474869,"lat"=>27.315904,"name"=>null,"unique_code"=>null],["lon"=>113.476485,"lat"=>27.295874,"name"=>null,"unique_code"=>null],["lon"=>113.46894,"lat"=>27.27494,"name"=>null,"unique_code"=>null],["lon"=>113.436421,"lat"=>27.232388,"name"=>null,"unique_code"=>null],["lon"=>113.436421,"lat"=>27.232388,"name"=>null,"unique_code"=>null],["lon"=>113.395674,"lat"=>27.19165,"name"=>null,"unique_code"=>null],["lon"=>113.382666,"lat"=>27.15379,"name"=>null,"unique_code"=>null],["lon"=>113.385685,"lat"=>27.123313,"name"=>null,"unique_code"=>null],["lon"=>113.366713,"lat"=>27.043802,"name"=>null,"unique_code"=>null],["lon"=>113.361251,"lat"=>27.017478,"name"=>"攸县新","unique_code"=>"G00251"]]
        ];
        $b = [];
        
        foreach ($a as $line) {
            $line_tmp = [];
            foreach ($line as $point){
                echo($point['name']);
                if ($point['name']  == null){
                    $line_tmp[] = [
                        "lon" => $point["lon"],
                        "lat" => $point["lat"],
                        "name" => null,
                        "unique_code" => null,
                        "scene_workshop_unique_code" => null
                    ];
                }else {
                    $maintain = DB::table('maintains')->where('type', 'STATION')->where('name', $point['name'])->first();
                    if (empty($maintain)) {
                        $line_tmp[] = [
                            "lon" => $point["lon"],
                            "lat" => $point["lat"],
                            "name" => null,
                            "unique_code" => null,
                            "scene_workshop_unique_code" => null
                        ];
                    } else {
                        $line_tmp[] = [
                            "lon" => $point["lon"],
                            "lat" => $point["lat"],
                            "name" => $point["name"],
                            "unique_code" => $maintain->unique_code,
                            "scene_workshop_unique_code" => $maintain->parent_unique_code
                        ];
                    }
                }
            }
            $b[]=$line_tmp;
        }
        file_put_contents(storage_path('app/test.json'), json_encode($b, 256));
//        $file = FileSystem::init(storage_path('app'));
//        $file->join('test.json')->write(TextHelper::toJson($b));

        return 'ok';
    }
}
