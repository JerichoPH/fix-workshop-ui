<?php

namespace App\Console\Commands;

use App\Facades\KindsFacade;
use App\Model\EntireModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class KindInputQCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kinds-input:q {operator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '批量增加器材类型、型号';

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
     */
    public function handle(): void
    {
        $operator = $this->argument('operator');
        $this->$operator();
    }

    /**
     * 增加种类型
     * @param string $category_unique_code
     * @param array $models
     */
    private function pushModels(string $category_unique_code, array $models): void
    {
        collect($models)
            ->each(function ($sub_models, $entire_model_name) use ($category_unique_code) {
                $entire_model = EntireModel::with([])
                    ->where('category_unique_code', $category_unique_code)
                    ->where('name', $entire_model_name)
                    ->first();
                if (!$entire_model) {
                    $entire_model = EntireModel::with([])->create([
                        'unique_code' => EntireModel::generateEntireModelUniqueCode($category_unique_code),
                        'name' => $entire_model_name,
                        'category_unique_code' => $category_unique_code,
                        'is_sub_model' => false,
                    ]);
                    $this->info("创建新类型：{$entire_model->name} {$entire_model->unique_code}");
                }

                collect($sub_models)->each(function ($sub_model_name) use ($entire_model, $category_unique_code) {
                    if (!EntireModel::with([])
                        ->where('name', $sub_model_name)
                        ->where('category_unique_code', $category_unique_code)
                        ->where('parent_unique_code', $entire_model->unique_code)
                        ->where('is_sub_model', true)
                        ->exists()) {
                        $sub_model = EntireModel::with([])->create([
                            'unique_code' => EntireModel::generateSubModelUniqueCode($entire_model->unique_code),
                            'name' => $sub_model_name,
                            'category_unique_code' => $category_unique_code,
                            'parent_unique_code' => $entire_model->unique_code,
                            'is_sub_model' => true,
                        ]);
                        $this->info("创建新型号：{$sub_model->name} {$sub_model->unique_code}");
                    } else {
                        $this->comment("跳过型号：{$sub_model_name}");
                    }
                });
            });
    }

    /**
     * 转辙机、转换锁闭器、自动开闭器改为器材
     */
    public function s_to_q()
    {
        // 转辙机
        DB::table("categories")->where("unique_code", "S03")->update(["name" => "转辙机（旧码）"]);
        DB::table("categories")->insert(["created_at" => now(), "updated_at" => now(), "name" => "转辙机", "unique_code" => "Q42",]);
        $models = [
            "ZD6" => [
                "ZD6-A",
                "ZD6-B",
                "ZD6-D",
                "ZD6-E",
                "ZD6-F",
                "ZD6-G",
                "ZD6-H",
                "ZD6-J",
                "ZDG-III",
                "ZD6-K",
                "ZD6-D-F",
                "ZD6-F-F",
                "ZD6-G-F",
                "ZD6-J-F",
                "DZG",
                "DZ1",
                "ZD6-G",
                "ZD6-D",
                "ZD6-F",
                "DZ1-A",
            ],
            "ZD9" => [
                "ZD9",
                "ZD9-A",
                "ZD9-B",
                "ZD9-C",
                "ZD9-D",
                "ZD(J)9",
            ],
            "ZY(J)" => [
                "ZY-4",
                "ZY-6",
                "ZY-7",
                "ZYJ-2",
                "ZYJ-3",
                "ZYJ-4",
                "ZYJ-5",
                "ZYJ-6",
                "ZYJ7",
                "ZYJ7-A",
                "ZYJ7-J",
                "ZYJ7-K",
                "ZY4交流电液",
                "ZY4直流电液",
                "ZY7-F",
                "ZY7-N",
                "ZYJ7-1",
                "ZYJ7-B",
                "ZYJ7-C",
                "ZYJ7-D",
                "ZYJ7-F",
                "ZYJ7-H",
                "ZYJ7-M",
                "ZYJ7-N",
                "ZYJ7-P",
                "ZYJ7-Q",
                "ZYJ7-R",
                "ZYJ7-R1",
                "ZYJ7-R2",
                "ZYJ7S",
                "ZYJ7-SS",
                "ZYJ7-U",
                "ZYJ7-W",
                "ZYS7",
                "ZYJ7",
                "ZY4",
                "ZY6",
                "YJ1电机",
            ],
            "S700K" => [
                "S700K-A10",
                "S700K-A13",
                "S700K-A14",
                "S700K-A15",
                "S700K-A16",
                "S700K-A17",
                "S700K-A18",
                "S700K-A19",
                "S700K-A20",
                "S700K-A21",
                "S700K-A22",
                "S700K-A29",
                "S700K-A30",
                "S700K-A33",
                "S700K-A27",
                "S700K-A28",
                "S700K-A13G",
                "S700K-A14G",
                "S700K-A17G",
                "S700K-A18G",
                "S700K-A35G",
                "S700K-A36G",
                "S700K-A47G",
                "S700K-A48G",
                "S700K-A49G",
                "S700K-A50G",
                "S700K-A91",
                "S700K-A92",
                "S700K-A93",
                "S700K-A94",
                "S700K-A95",
                "S700K-A97",
                "S700K-A98",
            ],
            "ZK" => [
                "ZK-3A",
                "ZK-4",
            ],
            "ZD7" => [
                "ZD7-A",
                "ZD7-C",
            ],
        ];

        $this->pushModels("Q42", $models);

        // 转换锁闭器
        DB::table("categories")->where("unique_code", "S07")->update(["name" => "转换锁闭器（旧码）"]);
        DB::table("categories")->insert(["created_at" => now(), "updated_at" => now(), "unique_code" => "Q45", "name" => "转换锁闭器",]);
        $models = [
            "SH5系列" => [],
            "SH6系列" => ["SH6", "SH6-A", "SH6-B", "SH6-E1",],
        ];

        $this->pushModels("Q45", $models);

        // 自动开闭器
        $category_unique_code = 'Q44';
        $models = [
            'SH5' => [
                'SH5交流电液转辙机',
                'SH5直流电液转辙机',
            ],
            'SH6' => [
                'SH6-B',
                'SH6-D',
                'SH6-E',
                'SH6-E1',
                'SH6-E2',
                'SH6-F2',
                'SH6-H',
                'SH6-J',
                'SH6-J1',
                'SH6-JSS',
                'SH6-M',
                'SH6-M1交流电液转辙机',
                'SH6-M1直流电液转辙机',
                'SH6S',
                'SHS6',
            ],
        ];


        $this->pushModels($category_unique_code, $models);
    }

    /**
     * 惠州-仪器仪表
     */
    private function hz_yqyb()
    {
        DB::table("categories")->insert(["created_at" => now(), "updated_at" => now(), "name" => "仪器仪表", "unique_code" => "Q17",]);

        $models = [
            "电磁类" => [
                "UT71C数字表",
                "UT56数字表",
                "UA0599218数字表",
                "UT54数字表",
                "UA78A数字表",
                "UT33A数字表",
                "UT58E数字表",
                "UT33B数字表",
                "VICITOR 86E数字表",
                "VC980数字表",
                "VICTOR17数字表",
                "VNI-T数字表",
                "VR8905A数字表",
                "VC8302数字表",
                "FLUKE-1508数字绝缘电阻测量仪",
                "HSZD-30兆欧表",
                "JB/T9290兆欧表",
                "KD2671P数字绝缘电阻表",
                "ME200I绝缘电阻测试仪",
                "PC27-3H数字绝缘电阻表",
                "UT501数字绝缘电阻表",
                "DER2571A地线表",
                "KXGR-3000地线表",
                "MIS100地线表",
                "UNI-T UT522地线表",
                "VICTOR4105A地线表",
                "VICTOR6412地线表",
                "ZC29B-2地线表",
                "2MB/S数字数据性能分析仪",
                "OTDR光时域反射仪",
                "驻波比",
                "CD96-3S移频参数在线测试表",
                "CD718-D移频在线综合测试仪",
                "GD718-D移频在线综合测试仪",
                "XZY-Ⅱ移频参数在线测试表",
                "TC2000D移频综合测试仪",
                "SLE721移频参数在线测试表",
                "ME2000H移频在线测试仪",
                "KD2571B地线表",
                "XG2128 2M误码测试仪",
                "电缆验测仪 Fluke Micros canner2",
                "电脑网络电缆测试仪 能手",
                "多路数据记录仪 TP700",
                "光功率计 DS3023",
                "光功率计 胜为OM-608",
                "红外测温仪 红外测温仪BM200",
                "示波器 Hantek DSO1202B",
                "手持式光功率计 OFT-1135",
                "手持式光功率计 OFT-1145",
                "数据误码测试仪 HCT-BERT/C",
                "数字万用表 数字万用表117C",
                "数字万用表 数字万用表15B+",
                "数字万用表 数字万用表17B+",
                "兆欧表 ZC25B-3型",
                "应答器位置测量仪 YDQC-2",
            ],
            "力学类" => [
                "0-1MPA压力表",
                "乙炔表",
                "油压表",
                "精密压力表",
                "0-16MPA压力表",
                "0-25MPA压力表",
                "液压表",
            ],
            "检测监测装置类" => [
                "天馈线测试仪",
                "应答器参数及故障检测仪",
                "GDJY-B轨道绝缘在线测试仪",
                "防雷元件测试仪",
                "SPD综合测试仪",
                "轨道电路故障诊断仪TAGZ-3",
                "应答器报文读取测试仪 TC2000Y型",
                "报文读写器 LKY.BEPT2-TH",
                "报文读写器 蓝信TDBY-B",
                "应答器报文读取测试仪 T.BR",
                "缺口图像视频仪 AN255",
                "报文读写器 SIEMENS TPG-Eurobalise V2",
                "报文读写器 Workabout Pro4",
                "电源测试器 ATX电源测试器",
                "移动智能终端 X3-3081",
                "应答器报文读取测试仪 CT390",
                "主板诊断卡",
                "应答器报文读取器 LKY·D-th",
            ],
        ];

        $this->pushModels("Q17", $models);
    }

    /**
     * 海口断路器
     */
    final private function hk_dlq()
    {
        $models = [
            "保险" => [
                "NCBX1-Z4-F-DC24/80V-0.5A",
                "NCBX1-Z4-F-DC24/80V-3A",
                "NCBX1-Z4-F-DC24/80V-5A",
                "QA-1(13)415V-5A",
                "QA-1(13)415V-10A",
                "SF2-G3-10A",
                "SF2-H3-20A",
                "SF2-G3-30A",
                "SF2-G3-70A",
                "SA2-G0-60A",
            ],
        ];
        KindsFacade::pushModels("Q30", $models);
    }

    /**
     * 广州电源屏
     */
    private function gzdyp()
    {
        $models = [
            '防雷模块' => [
                '2R600TA',
                '3R250TA',
                '3R470TA',
                '3R600TA',
                '3R350TA',
                '3R90TB',
                '2R90TB',
                'B8H60R',
                '25D621K',
                '25D471K',
                '25D911K',
                'MYL1-5-68',
                'S14K50',
                'MYL2-560/10',
                'MYL3-10/820',
                'MYL3-5/100',
                'MYL2-470/10',
                'MYL24-100/5',
                'MYL3-10-470',
                'MYL1-5-300',
                'MYL3-5-56',
                'MYL3-10-370',
                'MYL2-100/10',
                'V20-C-280',
                'V20-C-75',
                'V20-C-320',
                'V25-B-385',
                'C25-B+C',
                'V20-C-385',
                'DG MOD 275',
                'DG MOD 385',
                'DGP C MOD',
                'DGA F 1.6',
                'DG TT385',
                'T385',
                'T275',
                'SFLM-C',
                'SFLM-60(L-L)',
                'SFLM-60(L-PE)',
                'SFLM-120(L-PE)',
                'SFLM-220(L-L)',
                'SFLM-220(L-PE)',
                'SFLM-380(L-L)',
                'SFLM-380(L-PE)',
                'SFLM-800(L-L)',
                'SFLP-385',
                'SFLP-420VB',
                'SSLP-075VBT',
                'SSLP-075VB',
                'SSLP-130VB',
                'SSLP-275VB',
                'SSLP-385',
                'SSTO-075VB',
                'SSTO-275VB',
                'SSTO-385VB',
                'FLM-120',
                'FLM WJ-Coax',
                'FLP-385',
                'GD-36',
                'GD-380',
                'TC20-75（全模）',
                'TC20-75（横向）',
                'TC20-275(全模）',
                'TC20-320(纵向）',
                'TC20-385',
                'TC75-20K/MS',
                'TC110-20K/MS',
                'TC220-40K/MS',
                'TC275-20K/MS',
                'TC380-40K/MS',
                'TC380-100K/MS',
                'MZCR-S110',
                'MZCR-S220',
                'MZCR-S380',
                'MZCR-P220',
                'ZFTW-VII/6D-J-C',
                'ZFTW-V/6D-J-Q',
                'ZFTW-V/6D-J-C',
                'ZFTW-V/6D-J',
                'ZFTW-VI/6D-J-Q',
                'ZFTW-IV/6D-J （大）',
                'ZFTW-VI/6D-J',
                'ZFTW-IV/6D-J （小）',
                'LQ-24LEU',
                'LQ 48XH',
                'LQ 110XH',
                'LQ 220XH',
                'LQ 380XH',
                'LQ 600XH',
                'ZFDF-220',
                'HTDY-220/20',
                'TEK FC75/2D',
                'TEK FC275/2D',
                'TEK FC320/40D',
                'TEK C460-40D',
                'SPD DXH06-F 385V',
                'SPD DXH06-F 255V',
                'SPD ZGG40-385(1+1)',
                'DXH01-F-385V',
                'HPD C 20G/385',
                'HPD C 40G/420',
                'RT18-32(32A)',
                'LD VA/220',
                'LD VA/275',
                'LD VA/385',
                'LYD1-C40/385',
                'LYD2-C40',
                'DEHN BSP M2 BD 24',
                'VAL-MS 230 ST',
                'ZGXL-2J-12',
                'BSP M2 BE 24',
                'BSP M2 BE 12',
                'BCT MOD ME 12',
                'UGKF BNC',
                'TVS2TL-24',
                'F1.6/5.6',
                'JDQB V1.0/C 3316.05A.00',
                'HENGSTLEP',
                'ZFTW-422/W',
                'TFT-BNC',
                'FLM WJ-24',
                'FLM WJ-VD15',
                'FLM-WJ-RJ 45/11',
                'ZFD-220(A)',
                'ZFQ-Z/M(B)',
                'ULQ1B220',
                'ZFJ-H18/H62',
                'FLM-XH-220-P',
                'FLM-DY-120-P',
                'FLM-DY-130-P',
                'FLM-DY-220-P',
                'FLM-GD-220-P',
                'FLM-380',
                'ASP AMZ-40',
                'IPRU GN',
                'IPRU 40',
            ],
        ];

        $this->pushModels('Q07', $models);
    }

    private function gz()
    {
        $category_unique_code = 'Q13';
        $models = [
            '阻容盒设备' => [
                // 'GDJ',
                // 'TFCS',
                // 'U A',
                // 'QT',
                // 'USU',
                // 'C1',
                // 'C2',
                // 'DY',
                // 'TDF0',
                // 'FXZ(11)',
                // 'LU',
                // 'usu-1',
                // 'usu-2',
                // 'usu-3',
                // 'USU-4',
                // 'USU-5位',
                // 'USU 9位',
                // 'USU 10位',
                // 'USU-4'
                // 'TDF',
                // 'TDF-1',
                // 'TDF-2',
                // 'TDF1(11位)',
                // 'TDF0-10位',
                // 'TDF0-11位',
                // 'TDF1-A',
                // 'TDF2-A',
                // 'TDF4-A',
                // 'TDF2-11位',
                // 'TDF4-11位',
                // 'TDF1',
                'L1',
            ],
        ];

        $this->pushModels('Q13', $models);
    }
}
