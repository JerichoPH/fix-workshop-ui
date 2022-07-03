<?php

namespace App\Console\Commands;

use App\Exceptions\ExcelInException;
use App\Facades\CodeFacade;
use App\Facades\CommonFacade;
use App\Facades\FixWorkflowFacade;
use App\Facades\TextFacade;
use App\Model\Account;
use App\Model\Category;
use App\Model\EntireInstance;
use App\Model\EntireInstanceCount;
use App\Model\EntireInstanceExcelTaggingIdentityCode;
use App\Model\EntireInstanceExcelTaggingReport;
use App\Model\EntireModel;
use App\Model\Factory;
use App\Model\Install\InstallPosition;
use App\Model\Install\InstallShelf;
use App\Model\Maintain;
use App\Model\PartCategory;
use App\Model\PartModel;
use App\Model\WorkArea;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jericho\Excel\ExcelReadHelper;
use Jericho\Excel\ExcelWriteHelper;
use Jericho\FileSystem;
use Jericho\Model\Log;
use Throwable;

class InitDataCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:data {function_name}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private $_functions = [];

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
     * 供应商
     */
    // final private function f1()
    // {
    //     $factories = [
    //         '中国铁道科学研究院' => 'P0001',
    //         '北京全路通信信号研究设计院集团有限公司' => 'P0002',
    //         '北京市华铁信息技术开发总公司' => 'P0003',
    //         '通号(北京)轨道工业集团有限公司' => 'P0004',
    //         '河南辉煌科技股份有限公司' => 'P0005',
    //         '上海铁大电信科技股份有限公司' => 'P0006',
    //         '卡斯柯信号有限公司' => 'P0007',
    //         '北京和利时系统工程有限公司' => 'P0008',
    //         '西门子股份公司' => 'P0009',
    //         '沈阳铁路信号有限责任公司' => 'P0011',
    //         '北京安达路通铁路信号技术有限公司' => 'P0014',
    //         '北京安润通电子技术开发有限公司' => 'P0015',
    //         '北京北信丰元铁路电子设备有限公司' => 'P0016',
    //         '北京国铁路阳技术有限公司' => 'P0017',
    //         '北京交大微联科技有限公司' => 'P0018',
    //         '北京交通铁路技术研究所有限公司' => 'P0019',
    //         '北京津宇嘉信科技股份有限公司' => 'P0020',
    //         '北京全路通铁路专用器材工厂' => 'P0021',
    //         '北京全路通信信号研究设计院有限公司' => 'P0022',
    //         '北京铁路局太原电务器材厂' => 'P0023',
    //         '北京铁路信号有限公司' => 'P0024',
    //         '成都铁路通信仪器仪表厂' => 'P0025',
    //         '大连电机厂' => 'P0026',
    //         '丹东中天照明电器有限公司' => 'P0027',
    //         '固安北信铁路信号有限公司' => 'P0028',
    //         '固安通号铁路器材有限公司' => 'P0029',
    //         '固安信通信号技术股份有限公司' => 'P0030',
    //         '哈尔滨复盛铁路工电器材有限公司' => 'P0031',
    //         '哈尔滨铁晶铁路通信信号器材厂' => 'P0032',
    //         '哈尔滨铁路局直属机关通信信号器材厂' => 'P0033',
    //         '合肥市中铁电务有限责任公司' => 'P0034',
    //         '河北德凯铁路信号器材有限公司' => 'P0035',
    //         '河北冀胜轨道科技股份有限公司' => 'P0036',
    //         '河北南皮铁路器材有限责任公司' => 'P0037',
    //         '黑龙江瑞兴科技股份有限公司' => 'P0038',
    //         '济南三鼎电气有限责任公司' => 'P0039',
    //         '锦州长青铁路器材厂' => 'P0040',
    //         '洛阳通号铁路器材有限公司' => 'P0041',
    //         '南昌铁路电务设备厂' => 'P0042',
    //         '宁波鸿钢铁路信号设备厂' => 'P0043',
    //         '饶阳铁建电务器材有限公司' => 'P0044',
    //         '厦门科华恒盛股份有限公司' => 'P0045',
    //         '山特电子(深圳）有限公司' => 'P0046',
    //         '陕西赛福特铁路器材有限公司' => 'P0047',
    //         '陕西省咸阳市国营七九五厂' => 'P0048',
    //         '上海电捷工贸有限公司' => 'P0049',
    //         '上海铁路通信有限公司' => 'P0050',
    //         '上海铁路信号器材有限公司' => 'P0051',
    //         '深圳科安达电子科技股份有限公司' => 'P0052',
    //         '深圳市恒毅兴实业有限公司' => 'P0053',
    //         '深圳市铁创科技发展有限公司' => 'P0054',
    //         '沈阳宏达电机制造有限公司' => 'P0055',
    //         '沈阳铁路器材厂' => 'P0056',
    //         '四川浩铁仪器仪表有限公司' => 'P0057',
    //         '天津赛德电气设备有限公司' => 'P0058',
    //         '天津铁路信号有限责任公司' => 'P0059',
    //         '天水佳信铁路电气有限公司' => 'P0060',
    //         '天水铁路器材厂' => 'P0061',
    //         '天水铁路信号工厂' => 'P0062',
    //         '温州凯信电气有限公司' => 'P0063',
    //         '西安嘉信铁路器材有限公司' => 'P0064',
    //         '西安全路通号器材研究有限公司' => 'P0065',
    //         '西安铁路信号有限责任公司' => 'P0066',
    //         '西安无线电二厂' => 'P0067',
    //         '西安信通博瑞特铁路信号有限公司' => 'P0068',
    //         '西安宇通铁路器材有限公司' => 'P0069',
    //         '西安中凯铁路电气有限责任公司' => 'P0070',
    //         '偃师市泰达电务设备有限公司' => 'P0071',
    //         '浙江金华铁路信号器材有限公司' => 'P0072',
    //         '郑州世创电子科技有限公司' => 'P0073',
    //         '中国铁路通信信号集团有限公司' => 'P0074',
    //         'CSEE公司' => 'P0075',
    //         'GE公司' => 'P0076',
    //         '艾佩斯电力设施有限公司' => 'P0077',
    //         '安萨尔多' => 'P0078',
    //         '北京阿尔卡特' => 'P0079',
    //         '北京从兴科技有限公司' => 'P0080',
    //         '北京电务器材厂' => 'P0081',
    //         '北京国正信安系统控制技术有限公司' => 'P0082',
    //         '北京黄土坡信号厂' => 'P0083',
    //         '北京锦鸿希电信息技术股份有限公司' => 'P0084',
    //         '北京联能科技有限公司' => 'P0085',
    //         '北京联泰信科铁路信通技术有限公司' => 'P0086',
    //         '北京全路通号器材研究有限公司' => 'P0087',
    //         '北京世纪东方国铁科技股份有限公司' => 'P0088',
    //         '北京铁路分局西直门电务段' => 'P0089',
    //         '北京铁通康达铁路通信信号设备有限公司' => 'P0090',
    //         '北京兆唐有限公司' => 'P0091',
    //         '长沙南车电气设备有限公司' => 'P0092',
    //         '成都铁路通信设备有限责任公司' => 'P0093',
    //         '丹东东明铁路灯泡厂' => 'P0094',
    //         '奉化市皓盛铁路电务器材有限公司' => 'P0095',
    //         '广州华炜科技有限公司' => 'P0096',
    //         '广州铁路电务工厂' => 'P0097',
    //         '哈尔滨路通科技开发有限公司' => 'P0098',
    //         '哈尔滨市科佳通用机电有限公司' => 'P0099',
    //         '哈尔滨铁路通信信号器材厂' => 'P0100',
    //         '哈铁信号器材厂' => 'P0101',
    //         '杭州创联电子技术有限公司' => 'P0102',
    //         '鹤壁博大电子科技有限公司' => 'P0104',
    //         '湖南湘依铁路机车电器股份有限公司' => 'P0105',
    //         '兰州大成铁路信号有限责任公司' => 'P0106',
    //         '兰州铁路电务器材有限公司' => 'P0107',
    //         '柳州辰天科技有限责任公司' => 'P0108',
    //         '牡丹江电缆厂' => 'P0109',
    //         '南非断路器有限公司' => 'P0110',
    //         '南京电子管厂' => 'P0111',
    //         '南京圣明科技有限公司' => 'P0112',
    //         '宁波思高软件科技有限公司' => 'P0113',
    //         '齐齐哈尔电务器材厂' => 'P0114',
    //         '青岛四机易捷铁路器材有限公司' => 'P0115',
    //         '绕阳铁建电务器材有限公司' => 'P0116',
    //         '陕西众信铁路设备有限公司' => 'P0117',
    //         '上海电务工厂' => 'P0118',
    //         '上海瑞信电气有限公司' => 'P0119',
    //         '上海友邦电气股份有限公司' => 'P0120',
    //         '深圳长龙铁路电子工程有限公司' => 'P0121',
    //         '沈阳电务器材厂' => 'P0122',
    //         '施耐德电气信息技术（中国）有限公司' => 'P0123',
    //         '太原市京丰铁路电务器材制造有限公司' => 'P0124',
    //         '天水铁路电缆有限责任公司' => 'P0125',
    //         '天水铁路信号灯泡有限公司' => 'P0126',
    //         '万可电子（天津）有限公司' => 'P0127',
    //         '乌鲁木齐铁信公司' => 'P0128',
    //         '无锡同心铁路器材有限公司' => 'P0129',
    //         '西安大正信号有限公司' => 'P0130',
    //         '西安电务器材厂' => 'P0131',
    //         '西安东鑫瑞利德电子有限责任公司' => 'P0132',
    //         '西安开源仪表研究所' => 'P0133',
    //         '西安凯士信控制显示技术有限公司' => 'P0134',
    //         '西安天元铁路器材责任有限公司' => 'P0135',
    //         '西安通达电务器材厂' => 'P0136',
    //         '西安西电光电缆有限公司' => 'P0137',
    //         '西安西门子信号有限公司' => 'P0138',
    //         '西安信达铁路专用器材开发有限公司' => 'P0139',
    //         '襄樊电务器材厂' => 'P0140',
    //         '新铁德奥道岔有限公司' => 'P0141',
    //         '扬中市新华电务配件厂' => 'P0142',
    //         '郑州二七科达铁路器材厂' => 'P0143',
    //         '郑州华容电器科技有限公司' => 'P0144',
    //         '郑州铁路通号电务器材厂' => 'P0145',
    //         '中国铁道科学研究院通信信号研究所' => 'P0146',
    //         '重庆森威电子有限公司' => '	P0147',
    //         '株洲南车时代电气股份有限公司' => 'P0148',
    //         '北京怡蔚丰达电子技术有限公司' => 'P0149',
    //         '常州东方铁路器材有限公司' => 'P0150',
    //         'ABB（中国）有限公司' => 'P0151',
    //         '美国电力转换公司(APC）' => 'P0152',
    //         '施耐德' => 'P0153',
    //         'PORYAN' => 'P0154',
    //         '西安持信铁路器材有限公司' => 'P0155',
    //         '埃伯斯电子（上海）有限公司' => 'P0156',
    //         'Emerson(美国艾默生电气公司）' => 'P0157',
    //         '广东保顺能源股份有限公司' => 'P0158',
    //         '宝胜科技创新股份有限公司' => 'P0159',
    //         '北方交通大学信号抗干扰实验站' => 'P0160',
    //         '北京安英特技术开发公司' => 'P0161',
    //         '沧州铁路信号厂' => 'P0162',
    //         '北京大地同丰科技有限公司' => 'P0163',
    //         '北京丰台铁路电器元件厂' => 'P0164',
    //         '北京冠九州铁路器材有限公司' => 'P0165',
    //         '北京市交大路通科技有限公司' => 'P0167',
    //         '北京康迪森交通控制技术有限责任公司' => 'P0168',
    //         '北京六联信息技术研究所' => 'P0169',
    //         '北京施维格科技有限公司' => 'P0170',
    //         '北京世纪瑞尔技术股份有限公司' => 'P0171',
    //         '北京市丰台铁路电气元件厂' => 'P0172',
    //         '北京泰雷兹交通自动化控制系统有限公司' => 'P0173',
    //         '铁科院(北京)工程咨询有限公司' => 'P0174',
    //         '北京西南交大盛阳科技有限公司' => 'P0175',
    //         '朝阳电源有限公司' => 'P0176',
    //         '戴尔（Dell）' => 'P0177',
    //         '丹东铁路通达保安器件有限公司' => 'P0178',
    //         '德州津铁物资有限公司' => 'P0179',
    //         '天水长信铁路信号设备有限公司' => 'P0180',
    //         '天水铁路信号电缆厂' => 'P0181',
    //         '广州舰铭铁路设备有限公司' => 'P0182',
    //         '广州铁路（集团）公司电务工厂' => 'P0183',
    //         '通号通信信息集团有限公司广州分公司' => 'P0184',
    //         '广州忘平信息科技有限公司' => 'P0185',
    //         '杭州慧景科技股份有限公司' => 'P0186',
    //         '合肥中交电气有限公司' => 'P0187',
    //         '鹤壁市华研电子科技有限公司' => 'P0188',
    //         '湖北洪乐电缆股份有限公司' => 'P0189',
    //         '湖南中车时代通信信号有限公司' => 'P0190',
    //         '华为技术股份有限公司' => 'P0191',
    //         '惠普（HP）' => 'P0192',
    //         '济南瑞通铁路电务有限责任公司' => 'P0193',
    //         '江苏亨通电力电缆有限公司' => 'P0194',
    //         '江苏今创安达交通信息技术公司' => 'P0195',
    //         '焦作铁路电缆有限责任公司' => 'P0196',
    //         '上海良信电器股份有限公司' => 'P0197',
    //         '凌华科技(中国)有限公司' => 'P0198',
    //         '庞巴迪公司（Bombardier Inc.）' => 'P0199',
    //         '日本京三(KYOSAN)' => 'P0200',
    //         '瑞网数据通信设备（北京）有限公司' => 'P0201',
    //         '山西润泽丰科技开发有限公司' => 'P0202',
    //         '陕西通号铁路器材有限公司' => 'P0203',
    //         '西北铁道电子股份有限公司' => 'P0204',
    //         '上海德意达电子电器设备有限公司' => 'P0205',
    //         '上海慧轩电气科技有限公司' => 'P0206',
    //         '上海新干通通信设备有限公司' => 'P0207',
    //         '金华铁路通信信号器材厂' => 'P0208',
    //         '苏州飞利浦消费电子有限公司' => 'P0209',
    //         '武汉瑞控电气工程有限公司' => 'P0210',
    //         '天津七一二通信广播有限公司' => 'P0211',
    //         '天津海斯特电机有限公司' => 'P0212',
    //         '天津精达铁路器材有限公司' => 'P0213',
    //         '天水广信铁路信号公司' => 'P0214',
    //         '武汉贝通科技有限公司' => 'P0215',
    //         '西安盛达铁路电器有限公司' => 'P0216',
    //         '西安铁通科技开发实业公司' => 'P0217',
    //         '西安唯迅监控设备有限公司' => 'P0218',
    //         '西安铁路信号工厂' => 'P0219',
    //         '西安一信铁路器材有限公司' => 'P0220',
    //         '研华科技股份有限公司' => 'P0221',
    //         '扬州长城铁路器材有限公司' => 'P0222',
    //         '英沃思科技(北京)有限公司' => 'P0223',
    //         '宁波市皓盛铁路电务器材有限公司' => 'P0224',
    //         '郑州铁路专用器材有限公司' => 'P0225',
    //         '中车株洲电力机车研究所有限公司' => 'P0226',
    //         '中达电通股份有限公司' => 'P0227',
    //         '中国铁路通信信号股份有限公司(中国通号CRSC)' => 'P0228',
    //         '中利科技集团股份有限公司' => 'P0229',
    //         '中兴通讯股份有限公司' => 'P0230',
    //         '株洲中车时代电气股份有限公司' => 'P0231',
    //         'COMLAB（北京）通信系统设备有限公司' => 'P0232',
    //         '北京博飞电子技术有限责任公司' => 'P0233',
    //         '北京鼎汉技术集团股份有限公司' => 'P0235',
    //         '北京华铁信息技术有限公司' => 'P0236',
    //         '北京交大思诺科技股份有限公司' => 'P0237',
    //         '北京全路通信信号研究设计院集团有限公司广州分公司' => 'P0238',
    //         '北京信达环宇安全网络技术有限公司' => 'P0239',
    //         '北京英诺威尔科技股份有限公司' => 'P0240',
    //         '北京智讯天成技术有限公司' => 'P0241',
    //         '北京中智润邦科技有限公司' => 'P0242',
    //         '长沙飞波通信技术有限公司' => 'P0243',
    //         '长沙斯耐沃机电有限公司' => 'P0244',
    //         '长沙铁路建设有限公司' => 'P0245',
    //         '长沙智创机电设备有限公司' => 'P0246',
    //         '郴州长治建筑有限公司' => 'P0247',
    //         '楚天龙股份有限公司' => 'P0248',
    //         '东方腾大工程维修服务有限公司' => 'P0249',
    //         '高新兴创联科技有限公司' => 'P0250',
    //         '广东省肇庆市燊荣建筑安装装饰工程有限公司' => 'P0251',
    //         '广东永达建筑有限公司' => 'P0252',
    //         '广宁县第二建筑工程有限公司' => 'P0253',
    //         '广州里程通信设备有限公司' => 'P0254',
    //         '广州赛力迪软件科技有限公司' => 'P0255',
    //         '广州盛佳建业科技有限责任公司' => 'P0256',
    //         '广州市大周电子科技有限公司' => 'P0257',
    //         '广州市广源电子科技有限公司' => 'P0258',
    //         '广州昊明通信设备有限公司' => 'P0259',
    //         '海口思宏电子工程有限公司' => 'P0260',
    //         '海南国鑫实业有限公司' => 'P0261',
    //         '海南海岸网络科技有限公司' => 'P0262',
    //         '海南海口建筑集团有限公司' => 'P0263',
    //         '海南华联安视智能工程有限公司' => 'P0264',
    //         '海南建祥瑞建筑工程有限公司' => 'P0265',
    //         '海南中弘建设工程有限公司' => 'P0266',
    //         '海南寰宇华强网络科技有限公司' => 'P0267',
    //         '海南鑫泰隆水电工程有限公司' => 'P0268',
    //         '杭州慧景科技有限公司' => 'P0269',
    //         '河南蓝信科技有限责任公司' => 'P0270',
    //         '河南思维自动化设备股份有限公司' => 'P0271',
    //         '湖南长铁装备制造有限公司' => 'P0272',
    //         '湖南飞波工程有限公司' => 'P0273',
    //         '湖南省石柱建筑工程有限公司' => 'P0274',
    //         '湖南中车时代通信信号有限公司（株洲中车时代电气股份有限公司）' => 'P0275',
    //         '怀化铁路工程有限公司' => 'P0276',
    //         '怀化铁路工程总公司' => 'P0277',
    //         '江苏理士电池有限公司' => 'P0278',
    //         '江苏万华通信科技有限公司' => 'P0279',
    //         '南京盛佳建业科技有限责任公司' => 'P0280',
    //         '南京泰通科技股份有限公司' => 'P0281',
    //         '宁津南铁重工设备有限公司' => 'P0282',
    //         '饶阳县路胜铁路信号器材有限公司' => 'P0283',
    //         '陕西西北铁道电子股份有限公司' => 'P0284',
    //         '上海仁昊电子科技有限公司' => 'P0285',
    //         '深圳市速普瑞科技有限公司' => 'P0286',
    //         '深圳市英维克科技有限公司' => 'P0287',
    //         '通号（长沙）轨道交通控制技术有限公司' => 'P0288',
    //         '通号工程局集团有限公司' => 'P0289',
    //         '维谛技术有限公司' => 'P0290',
    //         '武汉佳和电气有限公司' => 'P0291',
    //         '西安博优铁路机电有限责任公司' => 'P0292',
    //         '浙江友诚铁路设备科技有限公司' => 'P0293',
    //         '中国海底电缆建设有限公司' => 'P0294',
    //         '中国铁道科学研究院集团有限公司通信信号研究所' => 'P0295',
    //         '中国铁建电气化局集团有限公司' => 'P0296',
    //         '中国铁路通信信号股份有限公司' => 'P0297',
    //         '中国铁路通信信号上海工程局集团有限公司' => 'P0298',
    //         '中山市德全建设工程有限公司' => 'P0299',
    //         '中铁电气化局第一工程有限公司' => 'P0300',
    //         '中铁电气化局集团第三工程有限公司' => 'P0301',
    //         '中铁电气化局集团有限公司' => 'P0302',
    //         '中铁二十五局集团电务工程有限公司' => 'P0303',
    //         '中铁建电气化局集团第四工程有限公司' => 'P0304',
    //         '中铁四局集团电气化工程有限公司' => 'P0305',
    //         '中铁武汉电气化局集团第一工程有限公司' => 'P0306',
    //         '中铁武汉电气化局集团有限公司' => 'P0308',
    //         '中移建设有限公司' => 'P0309',
    //         '珠海朗电气有限公司' => 'P0310',
    //         '株洲市亿辉贸易有限公司' => 'P0311',
    //         '湖南长铁工业开发有限公司' => 'P0312',
    //         '大连嘉诺机械制造有限公司' => 'P0313',
    //         '天津宝力电源有限公司' => 'P0314',
    //         '广东广特电气股份有限公司' => 'P0315',
    //         '沈阳希尔科技发展有限公司' => 'P0316',
    //     ];
    //     DB::table('factories')->truncate();
    //     foreach ($factories as $factory_name => $factory_unique_code) {
    //         $factory = Factory::with([])->create([
    //             'name' => $factory_name,
    //             'unique_code' => $factory_unique_code
    //         ]);
    //         $this->info($factory->name, $factory->unique_code);
    //     }
    //
    //     // $change_factories = [
    //     //     '北京交大斯诺科技公司' => '北京交大思诺科技股份有限公司',
    //     //     '北京鼎汉技术有限公司' => '北京鼎汉技术集团股份有限公司',
    //     //     '河南蓝信科技股份有限公司' => '河南蓝信科技有限责任公司',
    //     //     '北京局太原电务器材厂' => '北京铁路局太原电务器材厂',
    //     //     '太原电务器材厂' => '北京铁路局太原电务器材厂',
    //     //     '广州电务工厂' => '广州铁路（集团）公司电务工厂',
    //     //     '济南三鼎' => '济南三鼎电气有限责任公司',
    //     //     '青岛四机' => '青岛四机易捷铁路器材有限公司',
    //     //     '北京铁路信号工厂' => '北京铁路信号有限公司',
    //     //     '天津信号工厂' => '天津铁路信号有限责任公司',
    //     //     '沈阳信号工厂' => '沈阳铁路信号有限责任公司',
    //     // ];
    //     //
    //     // foreach ($change_factories as $old => $new) {
    //     //     EntireInstance::with([])->where('factory_name', $old)->update(['factory_name' => $new]);
    //     //     $this->info("{$old} => {$new}");
    //     // }
    // }

    /**
     * 工区
     */
    final private function f2()
    {
        DB::table('work_areas')->truncate();
        DB::statement("alter table work_areas modify type enum('', 'pointSwitch', 'relay', 'synthesize', 'scene', 'powerSupplyPanel1') default '' not null comment 'pointSwitch：转辙机工区
reply：继电器工区
synthesize：综合工区
scene：现场工区
powerSupplyPanel：电源屏工区'");

        $this->info("工区 => 人员 开始");
        $origin_time = time();
        $now = now();
        $workshop_unique_code = env('ORGANIZATION_LOCATION_CODE');
        $paragraph_unique_code = env('ORGANIZATION_CODE');
        switch (env('ORGANIZATION_CODE')) {
            case 'B049':
                // 长沙没有电源屏工区
                $work_areas = [
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D01",
                        'name' => '转辙机工区',
                        'type' => 'pointSwitch',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ], [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D02",
                        'name' => '继电器工区',
                        'type' => 'relay',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ], [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D03",
                        'name' => '综合工区',
                        'type' => 'synthesize',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ],
                ];
                break;
            default:
                $work_areas = [
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D01",
                        'name' => '转辙机工区',
                        'type' => 'pointSwitch',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ], [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D02",
                        'name' => '继电器工区',
                        'type' => 'relay',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ], [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D03",
                        'name' => '综合工区',
                        'type' => 'synthesize',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ], [
                        'created_at' => $now,
                        'updated_at' => $now,
                        'workshop_unique_code' => $workshop_unique_code,
                        'unique_code' => "{$paragraph_unique_code}D03",
                        'name' => '电源屏工区',
                        'type' => 'synthesize',
                        'paragraph_unique_code' => $paragraph_unique_code,
                    ],
                ];
                break;
        }
        foreach ($work_areas as $work_area) {
            if (!WorkArea::with([])->where('unique_code', $work_area['unique_code'])->exists()) {
                WorkArea::with([])->create($work_area);
            }
        }

        // 人员所属工区刷库
        $work_areas = [
            '无' => '',
            '转辙机工区' => "{$paragraph_unique_code}D01",
            '继电器工区' => "{$paragraph_unique_code}D02",
            '综合工区' => "{$paragraph_unique_code}D03",
            '电源屏工区' => "{$paragraph_unique_code}D04",
        ];
        Account::with([])->each(function (Account $account) use ($work_areas) {
            $account->fill([
                'workshop_unique_code' => env('ORGANIZATION_CODE'),
                'work_area_unique_code' => $work_areas[$account->work_area]
            ])->saveOrFail();
        });
        $used_time = ceil(time() - $origin_time);
        $this->info("工区 => 人员 结束：{$used_time}秒");

        // 设备刷库 todo: 设备所属工区刷库
        EntireInstance::with([])->where('category_unique_code', 'S03')->update(['work_area_unique_code' => "{$paragraph_unique_code}D01"]);
        EntireInstance::with([])->where('category_unique_code', 'Q01')->update(['work_area_unique_code' => "{$paragraph_unique_code}D02"]);
        switch (env('ORGANIZATION_CODE')) {
            case 'B049':
                // 长沙段没有电源屏工区
                EntireInstance::with([])->whereNotIn('category_unique_code', ['S03', 'Q01'])->update(['work_area_unique_code' => "{$paragraph_unique_code}D03"]);
                break;
            default:
                // 如果是其他段，则Q07归到电源屏工区
                EntireInstance::with([])->whereNotIn('category_unique_code', ['S03', 'Q01', 'Q07'])->update(['work_area_unique_code' => "{$paragraph_unique_code}D03"]);
                EntireInstance::with([])->where('category_unique_code', 'Q07')->update(['work_area_unique_code' => "{$paragraph_unique_code}D04"]);
                break;
        }
        $used_time = ceil(time() - $origin_time);
        $this->info("工区 => 设备 结束：{$used_time}秒");
    }

    /**
     * 部件型号
     */
    final private function f3()
    {
        $origin_time = time();
        $this->info('f3 开始');
        // 纠正错误名称
        EntireModel::with([])->where('unique_code', 'Q0403')->update(['name' => '移位接触器']);
        EntireModel::with([])
            ->where('unique_code', 'Q0409')
            ->where('name', '油泵')
            ->where('is_sub_model', false)
            ->where('category_unique_code', 'Q04')
            ->firstOrCreate([
                'unique_code' => 'Q0409',
                'name' => '油泵',
                'category_unique_code' => 'Q04',
                'is_sub_model' => false,
            ]);
        PartCategory::with([])->where('id', 5)->update(['name' => '自动开闭器']);
        PartCategory::with([])->where('category_unique_code', 'S03')->where('name', '摩擦连接器')->firstOrCreate([
            'category_unique_code' => 'S03',
            'name' => '摩擦连接器',
            'is_main' => false,
        ]);
        PartCategory::with([])->where('id', 1)->update(['entire_model_unique_code' => 'Q0405']);
        PartCategory::with([])->where('id', 2)->update(['entire_model_unique_code' => 'Q0403']);
        PartCategory::with([])->where('id', 3)->update(['entire_model_unique_code' => 'Q0406']);
        PartCategory::with([])->where('id', 4)->update(['entire_model_unique_code' => 'Q0409']);
        PartCategory::with([])->where('id', 5)->update(['entire_model_unique_code' => 'Q0401']);
        PartCategory::with([])->where('id', 6)->update(['entire_model_unique_code' => 'Q0402']);

        // 加入电机型号
        $dianji_models = [
            'ZD6-A', 'ZD6-B', 'ZD6-D', 'ZD6-E', 'ZD6-F', 'ZD6-G', 'ZD6-H', 'ZD6-J', 'ZDG-III',
            'ZD9', 'ZD9-A', 'ZD9-B', 'ZD9-C', 'ZD9-D', 'ZD(J)9', 'ZY-4', 'ZY-6', 'ZY-7', 'ZYJ-2', 'ZYJ-3', 'ZYJ-4', 'ZYJ-5',
            'ZYJ-6', 'ZYJ7', 'ZYJ7-A', 'ZYJ7-J', 'ZYJ7-K', 'S700K-A10', 'S700K-A13', 'S700K-A14', 'S700K-A15', 'S700K-A16',
            'S700K-A17', 'S700K-A18', 'S700K-A19', 'S700K-A20', 'S700K-A21', 'S700K-A22',
            'S700K-A29', 'S700K-A30', 'S700K-A33', 'ZK-3A', 'ZK-4', 'ZD7-A', 'ZD7-C', 'S700K',
            'ZD6-K', 'S700K-A27', 'S700K-A28', 'WB', 'SBQ', 'BSQ',
        ];
        foreach ($dianji_models as $item) {
            $entire_model = EntireModel::with([])->where('parent_unique_code', 'Q0405')->where('is_sub_model', true)->orderByDesc('id')->first();
            $max_unique_code = $entire_model ? substr($entire_model->unique_code, 5, 3) : '00';
            $max_unique_code = TextFacade::from36($max_unique_code);
            $new_unique_code = 'Q0405' . str_pad(TextFacade::to36(++$max_unique_code), 2, '0', 0);
            $new_entire_model = EntireModel::with([])->create([
                'name' => $item,
                'unique_code' => $new_unique_code,
                'category_unique_code' => 'Q04',
                'fix_cycle_unit' => 'YEAR',
                'fix_cycle_value' => 0,
                'is_sub_model' => true,
                'parent_unique_code' => 'Q0405',
            ]);
        }

        $used_time = time() - $origin_time;
        $this->info("f3 执行完毕，用时：{$used_time}");
    }

    /**
     * 子类改为36进制
     */
    final private function f4()
    {
        $this->info("子类改为36进制 开始");
        $origin_time = time();
        $sub_models = EntireModel::with([])
            ->where('is_sub_model', true)
            ->get();

        foreach ($sub_models as $sub_model) {
            if (strlen($sub_model->unique_code) <= 7) continue;
            $old_unique_code = $sub_model->unique_code;
            $first = substr($old_unique_code, 0, 5);
            $unique_code = substr($old_unique_code, 5);
            $new_unique_code = $first . str_pad(TextFacade::to36(intval($unique_code)), 2, '0', 0);
            $sub_model->fill(['unique_code' => $new_unique_code])->saveOrFail();
            DB::table('entire_instances as ei')->where('ei.model_unique_code', $old_unique_code)->update(['model_unique_code' => $new_unique_code, 'entire_model_unique_code' => $new_unique_code]);
        }
        $used_time = ceil(time() - $origin_time);
        $this->info("子类改为36进制 结束：{$used_time}秒");
    }

    /**
     * 计算设备总数
     */
    final private function f5()
    {
        $this->info('更新设备总数开始');
        $origin_time = time();
        DB::beginTransaction();
        $entire_model_unique_codes = DB::table('entire_instances as ei')
            ->selectRaw('ei.entire_model_unique_code')
            ->groupBy(['ei.entire_model_unique_code'])
            ->pluck('ei.entire_model_unique_code')
            ->toArray();

        DB::table('entire_instance_counts')->truncate();
        foreach ($entire_model_unique_codes as $entire_model_unique_code) {
            $entire_instance = EntireInstance::with([])->select('identity_code')->where('entire_model_unique_code', $entire_model_unique_code)->orderByDesc('id')->first();
            if ($entire_instance) {
                $pos = strpos($entire_instance->identity_code, env('ORGANIZATION_CODE'));
                $max = intval(substr($entire_instance->identity_code, $pos + 4));
                EntireInstanceCount::with([])->where('entire_model_unique_code', $entire_model_unique_code)->updateOrCreate([
                    'entire_model_unique_code' => $entire_model_unique_code,
                    'count' => $max,
                ]);
                dump("{$entire_model_unique_code} => {$max}");
            }
        }
        DB::commit();
        $run_time = time() - $origin_time;
        $this->info("更新设备总是完成：{$run_time}秒");
    }

    /**
     * 刷新人员所属电务段
     */
    final private function f6()
    {
        $this->info('人员所属段标识 开始');
        $origin_time = time();
        $ret = boolval(Account::with([])->update(['workshop_code' => env('ORGANIZATION_CODE')]));
        Account::with([])->where('account', 'admin')->update(['nickname' => '管理员(' . env('ORGANIZATION_NAME') . ')']);
        $used_time = ceil(time() - $origin_time);
        $this->info("人员所属段标识 结束：{$used_time}秒");
    }

    /**
     * 更新数据库，所有表，所有表子段的字符集和字符集排序
     */
    final private function f7()
    {
        $db_name = env('DB_DATABASE');
        $table_names = array_pluck(DB::select("SELECT TB.TABLE_NAME FROM INFORMATION_SCHEMA.TABLES TB WHERE TB.TABLE_SCHEMA = '{$db_name}'"), 'TABLE_NAME');

        foreach ($table_names as $table_name) {
            dump($table_name);
            // 修改表默认字符集和排序
            $statement_result = DB::statement("alter table `{$table_name}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            dump("{$table_name}执行结果:{$statement_result}");
        }
    }

    /**
     * 刷库：entire_instance_counts表中不正确的数字(根据实际设备数字)
     */
    final private function f8()
    {
        $this->info('刷新：entire_instance_counts 开始');

        DB::table('entire_instance_counts')->truncate();  // 清空表

        // 获取当前所有设备器材已经导入的型号
        DB::table('entire_instances as ei')
            ->select(['ei.entire_model_unique_code'])
            ->groupBy(['ei.entire_model_unique_code'])
            ->orderByDesc('ei.identity_code')
            ->chunk(50, function (Collection $entire_instances) {
                $entire_instances->each(function ($entire_instance) {
                    $v = DB::table('entire_instances as ei')
                        ->select(['ei.identity_code', 'ei.entire_model_unique_code as eu'])
                        ->where('ei.entire_model_unique_code', $entire_instance->entire_model_unique_code)
                        ->orderByDesc('ei.identity_code')
                        ->first();
                    $last_code = substr($v->identity_code, (strlen($v->eu) + 4));
                    $last_code = intval(rtrim($last_code, 'H'));

                    EntireInstanceCount::with([])->updateOrCreate([
                        'entire_model_unique_code' => $v->eu,
                    ], [
                        'entire_model_unique_code' => $v->eu,
                        'count' => $last_code,
                    ]);
                    $this->info("{$v->eu}:{$last_code}");
                });
            });

        $this->info('刷新：entire_instance_counts 完成');
    }

    /**
     * 刷库：重新计算报废日期
     */
    final private function f9()
    {
        $this->comment('重新计算报废日期');
        $count = 0;

        DB::table('entire_instances as ei')
            ->update(['scarping_at' => null]);

        DB::table('entire_instances as ei')
            ->select(['ei.id', 'ei.identity_code', 'ei.made_at', 'em.life_year',])
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
            ->whereNotNull('ei.made_at')
            ->orderBy('ei.id')
            ->where('em.life_year', '>', 0)
            ->chunk(100000, function ($entire_instances) use (&$count) {
                $entire_instances->each(function ($entire_instance) use (&$count) {
                    $count++;
                    $scarping_at = Carbon::parse($entire_instance->made_at)->addYearsNoOverflow($entire_instance->life_year)->format('Y-m-d');
                    DB::table('entire_instances as ei')
                        ->where('ei.id', $entire_instance->id)
                        ->update(['scarping_at' => $scarping_at,]);
                    $this->info("第{$count}: 唯一编号{$entire_instance->identity_code} 生产日期{$entire_instance->made_at} 寿命{$entire_instance->life_year}年 报废日期{$scarping_at}");
                });
            });

        $this->comment('从新计算报废日期：完成');
    }

    /**
     * 重算周期修
     */
    final private function f10()
    {
        $this->comment('重新计算周期修');

        $count = 0;
        DB::table('entire_instances as ei')
            ->update([
                'next_fixing_time' => 0,
                'next_fixing_day' => null,
                'next_fixing_month' => null,
            ]);

        DB::table('entire_instances as ei')
            ->select(['ei.id', 'ei.identity_code', 'ei.last_out_at', 'em.fix_cycle_value', 'ei.scarping_at',])
            ->join(DB::raw('entire_models em'), 'em.unique_code', '=', 'ei.entire_model_unique_code')
            ->whereNull('ei.deleted_at')
            ->whereNotNull('ei.last_out_at')
            ->orderBy('ei.id')
            ->where('em.fix_cycle_value', '>', 0)
            ->chunk(100000, function ($entire_instances) use (&$count) {
                $count++;
                $entire_instances->each(function ($entire_instance) use (&$count) {
                    $count++;
                    $next = Carbon::parse($entire_instance->last_out_at)->addYears($entire_instance->fix_cycle_value);

                    if ($entire_instance->scarping_at) {
                        $scarping_at = Carbon::parse($entire_instance->scarping_at);
                        if ($next->timestamp > $scarping_at->timestamp) $next = $scarping_at;
                    }

                    $next_fixing_time = $next->timestamp;
                    $next_fixing_day = $next->format('Y-m-d');
                    $next_fixing_month = $next->format('Y-m-01');
                    DB::table('entire_instances as ei')
                        ->where('ei.id', $entire_instance->id)
                        ->update([
                            'next_fixing_time' => $next_fixing_time,
                            'next_fixing_day' => $next_fixing_day,
                            'next_fixing_month' => $next_fixing_month,
                        ]);

                    $this->info("第{$count}: 唯一编号{$entire_instance->identity_code} 出所日期{$entire_instance->last_out_at} 周期修{$entire_instance->fix_cycle_value}年 下次周期修{$next_fixing_day}");
                });
            });
        $this->comment('重新计算周期修 完成');
    }

    /**
     * 刷库：赋码日志
     */
    final private function f11()
    {
        $this->comment('重刷赋码日志');

        $count = 0;

        DB::table('entire_instance_logs as eil')
            ->select([
                'eil.id',
                'eil.entire_instance_identity_code',
                'ei.made_at',
                'ei.created_at',
                'ei.scarping_at',
            ])
            ->join(DB::raw('entire_instances ei'), 'ei.identity_code', '=', 'eil.entire_instance_identity_code')
            ->where('name', 'like', '%赋码%')
            ->where('type', 0)
            ->orderBy('eil.id')
            ->chunk(100000, function ($entire_instance_logs) use (&$count) {
                $entire_instance_logs->each(function ($entire_instance_log) use (&$count) {
                    $count++;
                    if ($entire_instance_log->made_at) {
                        [
                            'scarping_at' => $scarping_at,
                            'made_at' => $made_at,
                        ] = (array)$entire_instance_log;

                        if ($made_at) $made_at = Carbon::parse($made_at)->format('Y-m-d');
                        if ($scarping_at) $scarping_at = Carbon::parse($scarping_at)->format('Y-m-d');

                        $description = $made_at ? "出厂日期：{$made_at}；" : '';
                        $description .= $scarping_at ? "报废日期：{$scarping_at}；" : '';

                        DB::table('entire_instance_logs')
                            ->where('id', $entire_instance_log->id)
                            ->update(['name' => '赋码', 'description' => $description,]);
                        $this->info("第{$count}: {$entire_instance_log->entire_instance_identity_code} {$description}");
                    }
                });
            });

        $this->comment('重刷赋码日志 完成');
    }

    /**
     * 从广州同步寿命和周期修年
     */
    final private function f12()
    {
        DB::beginTransaction();
        try {
            // 初始化周期修和寿命
            DB::connection('b053')
                ->table('entire_models')
                ->update([
                    'fix_cycle_value' => 0,
                    'life_year' => 15,
                ]);

            // 同步周期修
            DB::connection('b048')
                ->table('entire_models')
                ->where('fix_cycle_value', '>', 0)
                ->get()
                ->each(function ($entire_model) {
                    DB::connection('b053')
                        ->table('entire_models')
                        ->where('name', $entire_model->name)
                        ->update(['fix_cycle_value' => $entire_model->fix_cycle_value]);
                    $this->info("同步周期修(型号)：{$entire_model->name} {$entire_model->unique_code} {$entire_model->fix_cycle_value}");
                });

            DB::connection('b048')
                ->table('entire_models')
                ->where('life_year', '!=', 15)
                ->get()
                ->each(function ($entire_model) {
                    DB::connection('b053')
                        ->table('entire_models')
                        ->where('name', $entire_model->name)
                        ->update(['life_year' => $entire_model->life_year]);
                    $this->info("同步寿命(型号)：{$entire_model->name} {$entire_model->unique_code} {$entire_model->life_year}");
                });

            // 电源屏和
            DB::connection('b053')->table('entire_models')->whereIn('category_unique_code', ['Q06', 'Q07',])->update(['fix_cycle_value' => 0,]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * 同步怀化人员
     */
    final public function f13()
    {
        $a = DB::connection('pro_b050')
            ->table('accounts')
            ->select([
                'created_at',
                'updated_at',
                'deleted_at',
                'account',
                'password',
                'status_id',
                'organization_id',
                'nickname',
                'supervision',
                'identity_code',
            ])
            ->whereNotIn('account', ['admin', '8d',])
            ->get();

        DB::beginTransaction();
        foreach ($a->toArray() as $key => $item) {
            dump($key, $item->account, $item->nickname);
            DB::connection('pro_b052')->table('accounts')->insert((array)$item);
        }
        DB::commit();
    }

    /**
     * 同步车站经纬度和联系人
     */
    final public function f14()
    {
        $huizhou_warehouse_stations = DB::connection('huizhou_warehouse')->table('stations')->get();

        DB::beginTransaction();
        try {
            $huizhou_warehouse_stations->each(function ($huizhou_warehouse_station) {
                $ret = DB::connection('pro_b052')
                    ->table('maintains')
                    ->where('type', 'STATION')
                    ->where('name', $huizhou_warehouse_station->name)
                    ->update([
                        'lon' => $huizhou_warehouse_station->lon,
                        'lat' => $huizhou_warehouse_station->lat,
                        'contact' => $huizhou_warehouse_station->contact,
                        'contact_phone' => $huizhou_warehouse_station->contact_phone,
                        'contact_address' => $huizhou_warehouse_station->contact_address,
                    ]);

                dump($huizhou_warehouse_station->name, $ret, $huizhou_warehouse_station->lon, $huizhou_warehouse_station->lat);
            });
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
        }
    }

    /**
     * 根据车站找回现场车间
     */
    final public function f15()
    {
        DB::beginTransaction();
        try {
            $stations = DB::table('maintains as s')
                ->selectRaw(implode(',', ['s.name as station_name', 'sc.name as scene_workshop_name',]))
                ->join(DB::raw('maintains as sc'), 'sc.unique_code', '=', 's.parent_unique_code')
                ->get()
                ->pluck('scene_workshop_name', 'station_name');

            foreach ($stations as $station_name => $scene_workshop_name) {
                $ret = DB::table('entire_instances as ei')
                    ->where('ei.maintain_station_name', $station_name)
                    ->update(['ei.maintain_workshop_name' => $scene_workshop_name,]);
                $this->info("{$station_name} updated: {$ret}");
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * 修改已经绑定上道位置的器材状态为上道
     */
    final public function f16()
    {
        DB::beginTransaction();
        try {
            DB::table('entire_instances')->where('maintain_location_code', 'like', 'W1%')->where('maintain_station_name', '<>', '')->update(['status' => 'INSTALLED',]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }
    }

    /**
     * 导入辉煌种类
     */
    final public function hh1()
    {
        DB::beginTransaction();
        try {
            $device_kinds = collect(json_decode(file_get_contents(storage_path('1-6/设备种类型.json')))->content);

            DB::table('categories')
                ->updateOrInsert([
                    'name' => '轨道电路',
                    'unique_code' => 'S12',
                    'race_unique_code' => 1,
                ], [
                    'created_at' => now(),
                    'updated_at' => now(),
                    'name' => '轨道电路',
                    'unique_code' => 'S12',
                    'race_unique_code' => 1,
                ]);
            DB::table('entire_models')->whereIn('category_unique_code', ['S09', 'S12'])->delete();

            $target_category_unique_codes = ['S09', 'S12',];

            $insert_data = collect([]);

            $device_kinds->each(function ($device_kind) use ($target_category_unique_codes, &$insert_data) {
                if (in_array(Str::substr($device_kind->vcCode, 0, 3), $target_category_unique_codes)) {
                    if (Str::length($device_kind->vcCode) <= 5) {
                        dump($device_kind->vcCode);
                        $insert_data->push([
                            'unique_code' => $device_kind->vcCode,
                            'name' => $device_kind->vcName,
                            'category_unique_code' => Str::substr($device_kind->vcCode, 0, 3),
                            'parent_unique_code' => $device_kind->vcParCode,
                            'is_sub_model' => false,
                            'life_year' => 15,
                            'fix_cycle_unit' => 'YEAR',
                            'fix_cycle_value' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

            $ret = DB::table('entire_models')->insert($insert_data->toArray());

            DB::commit();
            dd("ok:{$ret}");
        } catch (Throwable $e) {
            DB::rollBack();
            dd($e->getMessage());
        }
    }

    /**
     * 导入辉煌设备数据
     */
    final public function hh2()
    {
        // DB::beginTransaction();
        try {
            /*
             * dtEquUseDate 上道日期
             * dtFactoryDate 出厂日期
             * nKmmark 公里标
             * vcAltitude 海拔
             * vcAssetsOwnership 资产归属
             * vcBelongDk 所属道岔
             * vcBuildCompany 施工单位
             * vcCode 唯一编号
             * vcDesignCompany 设计单位
             * vcEquUsePlace 上道位置
             * vcFactoryName 厂家名称
             * vcFactoryNumber 出厂编号
             * vcInstallationLocation1 安装位置1
             * vcInstallationLocation2 安装位置2
             * vcLatitude 纬度
             * vcLevel1RepairCycle 1级修周期
             * vcLevel2RepairCycle 2级修周期
             * vcLevel3RepairCycle 3级修周期
             * vcLevel4RepairCycle 4级修周期
             * vcLevel5RepairCycle 5级修周期
             * vcLongitude 经度
             * vcName 设备名称
             * vcOrgCode 工区编码
             * vcOrgName 工区名称
             * vcRepairType 其他修程
             * vcStationCode 车站代码
             * vcStationName 车站名称
             * vcType 设备种类代码
             */
            $devices = collect(json_decode(file_get_contents(storage_path('1-6/设备信息.json')))->content);

            $target_category_unique_codes = ['S01', 'S09', 'S12',];

            $insert_data = collect([]);

            $devices
                ->groupBy('vcType')
                ->each(function ($devices, $entire_model_unique_code)
                use (
                    $target_category_unique_codes,
                    &$insert_data
                ) {
                    if (in_array(Str::substr($entire_model_unique_code, 0, 3), $target_category_unique_codes)) {
                        $category_unique_code = Str::substr($entire_model_unique_code, 0, 3);
                        $category = Category::with([])->where('unique_code', $category_unique_code)->first();
                        if (!$category) dd("种类：{$category_unique_code} 不存在");
                        $entire_model = EntireModel::with([])->where('is_sub_model', false)->where('unique_code', $entire_model_unique_code)->first();
                        if (!$entire_model) dd("类型：{$entire_model_unique_code}不存在");
                        $devices
                            ->each(function ($device)
                            use (
                                $target_category_unique_codes,
                                $category,
                                $entire_model,
                                &$insert_data
                            ) {
                                if (in_array($category->unique_code, $target_category_unique_codes)) {
                                    if (Str::length($entire_model->unique_code) <= 5) {
                                        // 唯一编号
                                        $identity_code = $device->vcCode;
                                        if (!$identity_code) dd("唯一编号不存在");
                                        // 出厂日期
                                        $made_at = null;
                                        if ($device->dtFactoryDate) {
                                            try {
                                                $made_at = Carbon::parse($device->dtFactoryDate);
                                            } catch (Exception $e) {
                                                dd("{$identity_code} 出厂日期格式错误");
                                            }
                                        }
                                        // 上道时间
                                        $last_installed_at = null;
                                        if ($device->dtEquUseDate) {
                                            try {
                                                $last_installed_at = Carbon::parse($device->dtEquUseDate);
                                            } catch (Exception $e) {
                                                dd("{$identity_code} 上道时期格式错误");
                                            }
                                        }
                                        // 车站名称
                                        $maintain_station_name = $device->vcStationName;
                                        $station = null;
                                        $scene_workshop = null;
                                        if ($maintain_station_name) {
                                            $station = Maintain::with(['Parent'])->where('name', $maintain_station_name)->first();
                                            if (!$station) dd("{$identity_code} 车站：{$maintain_station_name}不存在");
                                            if (!$station->Parent) dd("{$identity_code} 车站：{$maintain_station_name}没有找到对应的现场车间");
                                            $scene_workshop = $station->Parent;
                                        }
                                        // 厂家名称
                                        $factory_name = @$device->vcFactoryName ?: '';
                                        // 出厂编号
                                        $factory_device_code = @$device->vcFactoryNumber ?: '';

                                        $insert_data->push([
                                            'created_at' => now()->format('Y-m-d H:i:s'),  // 新建时间
                                            'updated_at' => now()->format('Y-m-d H:i:s'),  // 修改时间
                                            'identity_code' => $identity_code,  // 唯一编号
                                            'category_unique_code' => $category->unique_code,  // 种类代码
                                            'category_name' => $category->name,  // 种类名称
                                            'entire_model_unique_code' => $entire_model->unique_code,  // 类型代码
                                            'model_unique_code' => $entire_model->unique_code,  // 型号代码
                                            'model_name' => $entire_model->name,  // 型号名称
                                            'status' => 'INSTALLED',  // 状态
                                            'factory_name' => $factory_name,  // 厂家名称
                                            'factory_device_code' => $factory_device_code,  // 出厂编号
                                            'maintain_station_name' => $station->name,  // 所属车站
                                            'maintain_workshop_name' => $scene_workshop->name,  // 所属现场车间
                                            'is_part' => false,  // 是否是部件
                                            'bind_device_code' => '',  // 绑定设备编号
                                            'last_installed_at' => $last_installed_at ? $last_installed_at->format('Y-m-d H:i:s') : null,
                                            'made_at' => $made_at ? $made_at->format('Y-m-d H:i:s') : null,
                                        ]);
                                    }
                                }
                            });
                    }
                });


            file_put_contents(storage_path('1-6/input_devices.json'), $insert_data->toJson(256));

            // DB::commit();
        } catch (Exception $e) {
            // DB::rollBack();
            dd($e->getMessage());
        }
    }

    /**
     * 写入辉煌设备数据
     */
    final public function hh3()
    {
        try {
            $devices = collect(json_decode(file_get_contents(storage_path('1-6/input_devices.json')), true));

            $count = 0;
            foreach ($devices as $device) {
                $count += 1;
                $ret = DB::table('entire_instances')->insert($device);
                dump($count, $ret, $device['identity_code']);
            }

            dd("finish count:{$count}");
        } catch (Throwable $e) {
            DB::rollBack();
            dd($e->getMessage());
        }
    }

    /**
     * 导入转辙机部件赋码并绑定
     */
    final public function zzj()
    {
        try {
            $fix_workshop = Maintain::with([])->where('unique_code', env('CURRENT_WORKSHOP_UNIQUE_CODE'))->first();
            if (!$fix_workshop) return back()->with('danger', '配置错误：没有找到检修车间');
            $excel_errors = [];
            $statuses = [
                '上道使用' => 'INSTALLED',
                '现场备品' => 'INSTALLING',
                '所内备品' => 'FIXED',
                '待修' => 'FIXING',
                '入所在途' => 'TRANSFER_IN',
                '出所在途' => 'TRANSFER_OUT',
                '报废' => 'SCRAP',
            ];
            $work_area_unique_code = 'B050D01';

            $excel = ExcelReadHelper::FROM_STORAGE(storage_path('1-6/zzj.xls'))
                ->originRow(3)
                ->withSheetIndex(0);
            $current_row = 3;

            // 转辙机工区
            $new_entire_instances = [];
            $excel_error = [];
            // 数据验证
            foreach ($excel['success'] as $row_datum) {
                if (empty(array_filter($row_datum, function ($item) {
                    return !empty($item);
                }))) continue;

                try {
                    list(
                        $om_serial_number,  // 所编号 A
                        $om_category_name,  // 种类 B
                        $om_entire_model_name,  // 类型 C
                        $om_status_name,  // 状态 D
                        $o_factory_device_code,  // 厂编号 E
                        $om_factory_name,  // 厂家 F
                        $o_made_at,  // 生产日期 G
                        $o_station_name,  // 车站 H
                        $o_crossroad_number,  // 道岔号 I
                        $o_last_out_at,  // 出所日期 G
                        $o_last_installed_time,  // 上道日期 K
                        $o_fixer_name,  // 检修人 L
                        $o_fixed_at,  // 检修时间 M
                        $o_checker_name,  // 验收人 N
                        $o_checked_at,  // 验收时间 O
                        $o_spot_checker_name,  // 抽验人 P
                        $o_spot_checked_at,  // 抽验时间 Q
                        $o_source_type_name,  // 来源类型 R
                        $o_source_name,  // 来源名称 S
                        $o_open_direction,  // 开向 T
                        $o_line_name,  // 线制 U
                        $o_said_rod,  // 表示干特征 V
                        $o_crossroad_type,  // 道岔类型 W
                        $o_extrusion_protect,  // 防挤压保护罩 X
                        $o_traction,  // 牵引 Y
                        $o_note,  // 备注 Z
                        $dj_serial_number,  // 电机 所编号 AA
                        $dj_factory_name,  // 电机 厂家 AB
                        $dj_factory_device_code,  // 电机 厂编号 AC
                        $dj_model_name,  // 电机 型号 AD
                        $dj_made_at,  // 电机 生产日期 AE
                        $dj_life_year,  // 电机 寿命(年) AF
                        $ywjcq_serial_number_l,  // 移位接触器(左) 所编号 AG
                        $ywjcq_factory_name_l,  // 移位接触器(左) 厂家 AH
                        $ywjcq_factory_device_code_l,  // 移位接触器(左) 厂编号 AI
                        $ywjcq_model_name_l,  // 移位接触器(左) 型号 AJ
                        $ywjcq_made_at_l,  // 移位接触器(左) 生产日期 AK
                        $ywjcq_life_year_l,  // 移位接触器(左) 寿命(年) AL
                        $ywjcq_serial_number_r,  // 移位接触器(右) 所编号 AM
                        $ywjcq_factory_name_r,  // 移位接触器(右) 厂家 AN
                        $ywjcq_factory_device_code_r,  // 移位接触器(右) 厂编号 AO
                        $ywjcq_model_name_r,  // 移位接触器(右) 型号 AP
                        $ywjcq_made_at_r,  // 移位接触器(右) 生产日期 AQ
                        $ywjcq_life_year_r,  // 移位接触器(右) 寿命(年) AR
                        $jsq_serial_number,  // 减速器 所编号 AS
                        $jsq_factory_name,  // 减速器 厂家 AT
                        $jsq_factory_device_code,  // 减速器 厂编号 AU
                        $jsq_model_name,  // 减速器 型号 AV
                        $jsq_made_at,  // 减速器 生产日期 AW
                        $jsq_life_year,  // 减速器 寿命(年) AX
                        $yb_serial_number,  // 油泵 所编号 AY
                        $yb_factory_name,  // 油泵 厂家 AZ
                        $yb_factory_device_code,  // 油泵 厂编号 BA
                        $yb_model_name,  // 油泵 型号 BB
                        $yb_made_at,  // 油泵 生产日期 BC
                        $yb_life_year,  // 油泵 寿命(年) BD
                        $zdkbq_serial_number,  // 自动开闭器 所编号 BE
                        $zdkbq_factory_name,  // 自动开闭器 厂家 BF
                        $zdkbq_factory_device_code,  // 自动开闭器 厂编号 BG
                        $zdkbq_model_name,  // 自动开闭器 型号 BH
                        $zdkbq_made_at,  // 自动开闭器 生产日期 BI
                        $zdkbq_life_year,  // 自动开闭器 寿命(年) BJ
                        $mcljq_serial_number,  // 摩擦连接器 所编号 BK
                        $mcljq_factory_name,  // 摩擦连接器 厂家 BL
                        $mcljq_factory_device_code,  // 摩擦连接器 厂编号 BM
                        $mcljq_model_name,  // 摩擦连接器 型号 BN
                        $mcljq_made_at,  // 摩擦连接器 生产日期 BO
                        $mcljq_life_year  // 摩擦连接器 寿命(年) BP
                        ) = $row_datum;
                } catch (Exception $e) {
                    $pattern = '/Undefined offset: /';
                    $offset = preg_replace($pattern, '', $e->getMessage());
                    $column_name = ExcelWriteHelper::int2Excel($offset);
                    throw new ExcelInException("读取：{$column_name}列失败。");
                }

                // 以下是严重错误，不允许通过
                // 验证种类
                if (!$om_category_name) throw new ExcelInException("第{$current_row}行，种类不能为空");
                $category = Category::with([])->where('name', $om_category_name)->first();
                if (!$category) throw new ExcelInException("第{$current_row}行，种类：{$om_category_name}不存在");
                // 验证类型
                if (!$om_entire_model_name) throw new ExcelInException("第{$current_row}行，类型不能为空");
                $em = EntireModel::with([])->where('is_sub_model', false)->where('category_unique_code', $category->unique_code)->where('name', $om_entire_model_name)->first();
                if (!$em) throw new ExcelInException("第{$current_row}行，类型：{$category->name} > {$om_entire_model_name}不存在");
                // 验证所编号唯一
                $entire_instance_identity_code = '';
                if ($om_serial_number) {
                    $entire_instances = EntireInstance::with([])->select(['identity_code'])
                        ->where('serial_number', $om_serial_number)
                        ->where('status', '<>', 'SCRAP')
                        ->where('entire_model_unique_code', $em->unique_code)
                        ->limit(2)
                        ->get();
                    // if ($om_serial_number == '203759') {
                    // $sql = Log::sqlLanguage(function()use($om_serial_number,$em){
                    //     $entire_instances = EntireInstance::with([])->select(['identity_code'])
                    //         ->where('serial_number', $om_serial_number)
                    //         ->where('status', '<>', 'SCRAP')
                    //         ->where('entire_model_unique_code', $em->unique_code)
                    //         ->limit(2)
                    //         ->get();
                    // });
                    // dd($sql);
                    // };
                    if ($entire_instances->isEmpty()) throw new ExcelInException("第{$current_row}行，所编号：{$om_serial_number}不存在({$category->name} {$em->name})");
                    if ($entire_instances->count() > 1) throw new ExcelInException("第{$current_row}行，所编号存在多个");
                    $entire_instance_identity_code = @$entire_instances->first()['identity_code'] ?? '';
                }
                if (!$entire_instance_identity_code) throw new ExcelInException ("第{$current_row}行，唯一编号不存在。(所编号：{$om_serial_number})");

                // 验证状态
                $status = $statuses[$om_status_name] ?? '';
                if (!$status) throw new ExcelInException("第{$current_row}行，状态错误：{$om_status_name}");

                /**
                 * 电机 所编号AB
                 * 电机 厂家AC
                 * 电机 厂编号AD
                 * 电机 型号AE
                 * 电机 生产日期AF
                 * 电机 寿命(年)AG
                 * @return array|null
                 * @throws ExcelInException
                 */
                $check_dj = function () use ($current_row, &$dj_serial_number, &$dj_factory_name, &$dj_factory_device_code, &$dj_model_name, &$dj_made_at, &$dj_life_year, &$excel_error, $work_area_unique_code, $status, $entire_instance_identity_code) {
                    // 验证厂家
                    // if ($dj_factory_name) {
                    //     $dj_factory = Factory::with([])->where('name', $dj_factory_name)->first();
                    //     if (!$dj_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(电机)：{$dj_factory_name}");
                    //     // if (!$dj_factory) {
                    //     //     $excel_error['AA'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂家', $dj_factory_name, "没有找到厂家：{$dj_factory_name}");
                    //     // }
                    // } else {
                    //     $excel_error['AB'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂家', $dj_factory_name, '没有填写电机厂家');
                    //     $dj_factory_name = '';
                    // }
                    // 验证厂编号AD
                    if (!$dj_factory_device_code) {
                        $excel_error['AD'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂编号', $dj_factory_device_code, '没有填写电机编号');
                        $dj_factory_device_code = '';
                    }
                    // 验证型号AE
                    $dj_model = null;
                    $dj_part_model = null;
                    if ($dj_serial_number && $dj_model_name) {
                        $dj_model = EntireModel::with(['Category', 'Parent',])->where('is_sub_model', true)->where('name', $dj_model_name)->first();
                        if (!$dj_model) throw new ExcelInException("第{$current_row}行，没有找到电机型号(器材)：{$dj_model_name}");
                        $dj_part_model = PartModel::with([])->where('name', $dj_model_name)->first();
                        if (!$dj_part_model) throw new ExcelInException("第{$current_row}行，没有找到电机型号(设备)：{$dj_model_name}");
                    }
                    // 验证生产日期AF
                    if ($dj_made_at) {
                        try {
                            $dj_made_at = ExcelWriteHelper::getExcelDate($dj_made_at);
                        } catch (\Exception $e) {
                            $excel_error['AF'] = ExcelWriteHelper::makeErrorResult($current_row, '电机生产日期', $dj_made_at, $e->getMessage());
                            $dj_made_at = null;
                        }
                    } else {
                        $excel_error['AF'] = ExcelWriteHelper::makeErrorResult($current_row, '电机生产日期', $dj_made_at, '没有填写电机生产日期');
                        $dj_made_at = null;
                    }
                    // 验证寿命AG
                    if (is_numeric($dj_life_year)) {
                        if ($dj_life_year < 0) {
                            $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '电机寿命(年)', $dj_life_year, '电机寿命必须填写正整数');
                            $dj_scraping_at = null;
                        } else {
                            $dj_scraping_at = Carbon::parse($dj_made_at)->addYears($dj_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '电机寿命(年)', $dj_life_year, '电机寿命必须填写正整数');
                        $dj_scraping_at = null;
                    }

                    return ($dj_serial_number && $dj_model) ? [
                        'identity_code' => '',
                        'serial_number' => $dj_serial_number,
                        'category_unique_code' => $dj_model->category_unique_code,
                        'category_name' => $dj_model->Category->name,
                        'entire_model_unique_code' => $dj_model->parent_unique_code,
                        'model_unique_code' => $dj_model->unique_code,
                        'model_name' => $dj_model->name,
                        'status' => $status,
                        'factory_name' => $dj_factory_name,
                        'factory_device_code' => $dj_factory_device_code,
                        'made_at' => $dj_made_at,
                        'scarping_at' => $dj_scraping_at,
                        'work_area_unique_code' => $work_area_unique_code,
                        'is_part' => true,
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                        'part_model_unique_code' => $dj_part_model->unique_code,
                        'part_model_name' => $dj_part_model->name,
                        'part_category_id' => $dj_part_model->part_category_id,
                    ] : null;
                };

                /**
                 * 移位接触器(左) 所编号AH
                 * 移位接触器(左) 厂家AI
                 * 移位接触器(左) 厂编号AJ
                 * 移位接触器(左) 型号AK
                 * 移位接触器(左) 生产日期AL
                 * 移位接触器(左) 寿命(年)AM
                 * @return array|null
                 * @throws ExcelInException
                 */
                $check_ywjcq_l = function () use ($current_row, &$ywjcq_serial_number_l, &$ywjcq_factory_name_l, &$ywjcq_factory_device_code_l, &$ywjcq_model_name_l, &$ywjcq_made_at_l, &$ywjcq_life_year_l, &$excel_error, $work_area_unique_code, $status, $entire_instance_identity_code) {
                    // 验证厂家
                    // if ($ywjcq_factory_name_l) {
                    //     $ywjcq_factory = Factory::with([])->where('name', $ywjcq_factory_name_l)->first();
                    //     if (!$ywjcq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(移位接触器(左))：{$ywjcq_factory_name_l}");
                    //     // if (!$ywjcq_factory) {
                    //     //     $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器厂家', $ywjcq_factory_name, "没有找到厂家：{$ywjcq_factory_name}");
                    //     // }
                    // } else {
                    //     $excel_error['AH'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)厂家', $ywjcq_factory_name_l, '没有填写移位接触器(左)厂家');
                    //     $ywjcq_factory_name_l = '';
                    // }
                    // 验证厂编号AJ
                    if (!$ywjcq_factory_device_code_l) {
                        $excel_error['AJ'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)厂编号', $ywjcq_factory_device_code_l, '没有填写移位接触器(左)厂编号');
                        $ywjcq_factory_device_code_l = '';
                    }
                    // 验证型号AK
                    $ywjcq_model = null;
                    $ywjcq_part_model_l = null;
                    if ($ywjcq_serial_number_l && $ywjcq_model_name_l) {
                        $ywjcq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $ywjcq_model_name_l)->first();
                        if (!$ywjcq_model) throw new ExcelInException("第{$current_row}行，没有找到移位接触器(左)型号(器材)：{$ywjcq_model_name_l}");
                        $ywjcq_part_model_l = PartModel::with([])->where('name', $ywjcq_model_name_l)->first();
                        if (!$ywjcq_part_model_l) throw new ExcelInException("第{$current_row}行，没有找到移位接触器(左)型号(设备)：{$ywjcq_model_name_l}");
                    }
                    // 验证生产日期AL
                    if ($ywjcq_made_at_l) {
                        try {
                            $ywjcq_made_at_l = ExcelWriteHelper::getExcelDate($ywjcq_made_at_l);
                        } catch (\Exception $e) {
                            $excel_error['AL'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)生产日期', $ywjcq_made_at_l, $e->getMessage());
                            $ywjcq_made_at_l = null;
                        }
                    } else {
                        $excel_error['AL'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)生产日期', $ywjcq_made_at_l, '没有填写移位接触器(左)生产日期');
                        $ywjcq_made_at_l = null;
                    }
                    $ywjcq_scraping_at = null;
                    // 验证寿命AM
                    if (is_numeric($ywjcq_life_year_l)) {
                        if ($ywjcq_life_year_l < 0) {
                            $excel_error['AM'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)寿命(年)', $ywjcq_life_year_l, '移位接触器(左)寿命必须填写正整数');
                            $ywjcq_scraping_at = null;
                        } else {
                            $ywjcq_scraping_at = Carbon::parse($ywjcq_made_at_l)->addYears($ywjcq_life_year_l)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['AM'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(左)寿命(年)', $ywjcq_life_year_l, '移位接触器(左)寿命必须填写正整数');
                        $ywjcq_scraping_at = null;
                    }

                    // return ($ywjcq_serial_number_l && $ywjcq_model) ? [
                    //     'part_model_unique_code' => $ywjcq_part_model_l->unique_code,
                    //     'part_model_name' => $ywjcq_part_model_l->name,
                    //     'entire_instance_identity_code' => '',
                    //     'status' => 'BUY_IN',
                    //     'factory_name' => $ywjcq_factory_name_l,
                    //     'factory_device_code' => $ywjcq_factory_device_code_l,
                    //     'identity_code' => '',
                    //     'entire_instance_serial_number' => '',
                    //     'category_unique_code' => $ywjcq_part_model_l->category_unique_code,
                    //     'entire_model_unique_code' => $ywjcq_part_model_l->entire_model_unique_code,
                    //     'part_category_id' => $ywjcq_part_model_l->part_category_id,
                    //     'made_at' => $ywjcq_made_at_l,
                    //     'scraping_at' => $ywjcq_scraping_at,
                    //     'device_model_unique_code' => $ywjcq_model->unique_code,
                    //     'serial_number' => $ywjcq_serial_number_l,
                    //     'work_area_unique_code' => $work_area_unique_code,
                    // ] : null;
                    return ($ywjcq_serial_number_l && $ywjcq_model) ? [
                        'identity_code' => '',
                        'serial_number' => $ywjcq_serial_number_l,
                        'category_unique_code' => $ywjcq_model->category_unique_code,
                        'category_name' => $ywjcq_model->Category->name,
                        'entire_model_unique_code' => $ywjcq_model->parent_unique_code,
                        'model_unique_code' => $ywjcq_model->unique_code,
                        'model_name' => $ywjcq_model->name,
                        'status' => $status,
                        'factory_name' => $ywjcq_factory_name_l,
                        'factory_device_code' => $ywjcq_factory_device_code_l,
                        'made_at' => $ywjcq_made_at_l,
                        'scarping_at' => $ywjcq_scraping_at,
                        'work_area_unique_code' => $work_area_unique_code,
                        'is_part' => true,
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                        'part_model_unique_code' => $ywjcq_part_model_l->unique_code,
                        'part_model_name' => $ywjcq_part_model_l->name,
                        'part_category_id' => $ywjcq_part_model_l->part_category_id,
                    ] : null;
                };

                /**
                 * 移位接触器(右) 所编号AN
                 * 移位接触器(右) 厂家AO
                 * 移位接触器(右) 厂编号AP
                 * 移位接触器(右) 型号AQ
                 * 移位接触器(右) 生产日期AR
                 * 移位接触器(右) 寿命(年)AS
                 * @return array|null
                 * @throws ExcelInException
                 */
                $check_ywjcq_r = function () use ($current_row, &$ywjcq_serial_number_r, &$ywjcq_factory_name_r, &$ywjcq_factory_device_code_r, &$ywjcq_model_name_r, &$ywjcq_made_at_r, &$ywjcq_life_year_r, &$excel_error, $work_area_unique_code, $status, $entire_instance_identity_code) {
                    // 验证厂家
                    // if ($ywjcq_factory_name_r) {
                    //     $ywjcq_factory = Factory::with([])->where('name', $ywjcq_factory_name_r)->first();
                    //     if (!$ywjcq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(移位接触器(右))：{$ywjcq_factory_name_r}");
                    //     // if (!$ywjcq_factory) {
                    //     //     $excel_error['AG'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器厂家', $ywjcq_factory_name, "没有找到厂家：{$ywjcq_factory_name}");
                    //     // }
                    // } else {
                    //     $excel_error['AN'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)厂家', $ywjcq_factory_name_r, '没有填写移位接触器(右)厂家');
                    //     $ywjcq_factory_name_r = '';
                    // }
                    // 验证厂编号AP
                    if (!$ywjcq_factory_device_code_r) {
                        $excel_error['AP'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)厂编号', $ywjcq_factory_device_code_r, '没有填写移位接触器(右)厂编号');
                        $ywjcq_factory_device_code_r = '';
                    }
                    // 验证型号AQ
                    $ywjcq_model = null;
                    $ywjcq_part_model_r = null;
                    if ($ywjcq_serial_number_r && $ywjcq_model_name_r) {
                        $ywjcq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $ywjcq_model_name_r)->first();
                        if (!$ywjcq_model) throw new ExcelInException("第{$current_row}行，没有找到移位接触器(右)型号(器材)：{$ywjcq_model_name_r}");
                        $ywjcq_part_model_r = PartModel::with([])->where('name', $ywjcq_model_name_r)->first();
                        if (!$ywjcq_part_model_r) throw new ExcelInException("第{$current_row}行，没有找到移位接触器(右)型号(设备)：{$ywjcq_model_name_r}");
                    }
                    // 验证生产日期AR
                    if ($ywjcq_made_at_r) {
                        try {
                            $ywjcq_made_at_r = ExcelWriteHelper::getExcelDate($ywjcq_made_at_r);
                        } catch (\Exception $e) {
                            $excel_error['AR'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)生产日期', $ywjcq_made_at_r, $e->getMessage());
                            $ywjcq_made_at_r = null;
                        }
                    } else {
                        $excel_error['AR'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)生产日期', $ywjcq_made_at_r, '没有填写移位接触器(右)生产日期');
                        $ywjcq_made_at_r = null;
                    }
                    $ywjcq_scraping_at = null;
                    // 验证寿命AS
                    if (is_numeric($ywjcq_life_year_r)) {
                        if ($ywjcq_life_year_r < 0) {
                            $excel_error['AS'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)寿命(年)', $ywjcq_life_year_r, '移位接触器(右)寿命必须填写正整数');
                            $ywjcq_scraping_at = null;
                        } else {
                            $ywjcq_scraping_at = Carbon::parse($ywjcq_made_at_r)->addYears($ywjcq_life_year_r)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['AS'] = ExcelWriteHelper::makeErrorResult($current_row, '移位接触器(右)寿命(年)', $ywjcq_life_year_r, '移位接触器(右)寿命必须填写正整数');
                        $ywjcq_scraping_at = null;
                    }

                    // return ($ywjcq_serial_number_r && $ywjcq_model) ? [
                    //     'part_model_unique_code' => $ywjcq_part_model_r->unique_code,
                    //     'part_model_name' => $ywjcq_part_model_r->name,
                    //     'entire_instance_identity_code' => '',
                    //     'status' => 'BUY_IN',
                    //     'factory_name' => $ywjcq_factory_name_r,
                    //     'factory_device_code' => $ywjcq_factory_device_code_r,
                    //     'identity_code' => '',
                    //     'entire_instance_serial_number' => '',
                    //     'category_unique_code' => $ywjcq_part_model_r->category_unique_code,
                    //     'entire_model_unique_code' => $ywjcq_part_model_r->entire_model_unique_code,
                    //     'part_category_id' => $ywjcq_part_model_r->part_category_id,
                    //     'made_at' => $ywjcq_made_at_r,
                    //     'scraping_at' => $ywjcq_scraping_at,
                    //     'device_model_unique_code' => $ywjcq_model->unique_code,
                    //     'serial_number' => $ywjcq_serial_number_r,
                    //     'work_area_unique_code' => $work_area_unique_code,
                    // ] : null;
                    return ($ywjcq_serial_number_r && $ywjcq_model) ? [
                        'identity_code' => '',
                        'serial_number' => $ywjcq_serial_number_r,
                        'category_unique_code' => $ywjcq_model->category_unique_code,
                        'category_name' => $ywjcq_model->Category->name,
                        'entire_model_unique_code' => $ywjcq_model->parent_unique_code,
                        'model_unique_code' => $ywjcq_model->unique_code,
                        'model_name' => $ywjcq_model->name,
                        'status' => $status,
                        'factory_name' => $ywjcq_factory_name_r,
                        'factory_device_code' => $ywjcq_factory_device_code_r,
                        'made_at' => $ywjcq_made_at_r,
                        'scarping_at' => $ywjcq_scraping_at,
                        'work_area_unique_code' => $work_area_unique_code,
                        'is_part' => true,
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                        'part_model_unique_code' => $ywjcq_part_model_r->unique_code,
                        'part_model_name' => $ywjcq_part_model_r->name,
                        'part_category_id' => $ywjcq_part_model_r->part_category_id,
                    ] : null;
                };

                /**
                 * 减速器 所编号AT
                 * 减速器 厂家AU
                 * 减速器 厂编号AV
                 * 减速器 型号AW
                 * 减速器 生产日期AX
                 * 减速器 寿命(年)AY
                 * @return array|null
                 * @throws ExcelInException
                 */
                $check_jsq = function () use ($current_row, &$jsq_serial_number, &$jsq_factory_name, &$jsq_factory_device_code, &$jsq_model_name, &$jsq_made_at, &$jsq_life_year, &$excel_error, $work_area_unique_code, $status, $entire_instance_identity_code) {
                    // 验证厂家
                    // if ($jsq_factory_name) {
                    //     $jsq_factory = Factory::with([])->where('name', $jsq_factory_name)->first();
                    //     if (!$jsq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(减速器)：{$jsq_factory_name}");
                    //     // if (!$jsq_factory) {
                    //     //     $excel_error['AM'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器厂家', $jsq_factory_name, "没有找到厂家：{$jsq_factory_name}");
                    //     // }
                    // } else {
                    //     $excel_error['AT'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器厂家', $jsq_factory_name, '没有填写减速器厂家');
                    //     $jsq_factory_name = '';
                    // }
                    // 验证厂编号AV
                    if (!$jsq_factory_device_code) {
                        $excel_error['AV'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器厂编号', $jsq_factory_device_code, '没有填写减速器编号');
                        $jsq_factory_device_code = '';
                    }
                    // 验证型号AW
                    $jsq_model = null;
                    $jsq_part_model = null;
                    if ($jsq_serial_number && $jsq_model_name) {
                        $jsq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $jsq_model_name)->first();
                        if (!$jsq_model) throw new ExcelInException("第{$current_row}行，没有找到减速器型号(器材)：{$jsq_model_name}");
                        $jsq_part_model = PartModel::with([])->where('name', $jsq_model_name)->first();
                        if (!$jsq_part_model) throw new ExcelInException("第{$current_row}行，没有找到减速器型号(设备)：{$jsq_model_name}");
                    }
                    // 验证生产日期AX
                    if ($jsq_made_at) {
                        try {
                            $jsq_made_at = ExcelWriteHelper::getExcelDate($jsq_made_at);
                        } catch (\Exception $e) {
                            $excel_error['AX'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器生产日期', $jsq_made_at, $e->getMessage());
                            $jsq_made_at = null;
                        }
                    } else {
                        $excel_error['AX'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器生产日期', $jsq_made_at, '没有填写减速器生产日期');
                        $jsq_made_at = null;
                    }
                    $jsq_scraping_at = null;
                    // 验证寿命AY
                    if (is_numeric($jsq_life_year)) {
                        if ($jsq_life_year < 0) {
                            $excel_error['AY'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器寿命(年)', $jsq_life_year, '减速器寿命必须填写正整数');
                            $jsq_scraping_at = null;
                        } else {
                            $jsq_scraping_at = Carbon::parse($jsq_made_at)->addYears($jsq_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['AY'] = ExcelWriteHelper::makeErrorResult($current_row, '减速器寿命(年)', $jsq_life_year, '减速器寿命必须填写正整数');
                        $jsq_scraping_at = null;
                    }

                    // return ($jsq_serial_number && $jsq_model) ? [
                    //     'part_model_unique_code' => $jsq_part_model->unique_code,
                    //     'part_model_name' => $jsq_part_model->name,
                    //     'entire_instance_identity_code' => '',
                    //     'status' => 'BUY_IN',
                    //     'factory_name' => $jsq_factory_name,
                    //     'factory_device_code' => $jsq_factory_device_code,
                    //     'identity_code' => '',
                    //     'entire_instance_serial_number' => '',
                    //     'category_unique_code' => $jsq_part_model->category_unique_code,
                    //     'entire_model_unique_code' => $jsq_part_model->entire_model_unique_code,
                    //     'part_category_id' => $jsq_part_model->part_category_id,
                    //     'made_at' => $jsq_made_at,
                    //     'scraping_at' => $jsq_scraping_at,
                    //     'device_model_unique_code' => $jsq_model->unique_code,
                    //     'serial_number' => $jsq_serial_number,
                    //     'work_area_unique_code' => $work_area_unique_code,
                    // ] : null;

                    return ($jsq_serial_number && $jsq_model) ? [
                        'identity_code' => '',
                        'serial_number' => $jsq_serial_number,
                        'category_unique_code' => $jsq_model->category_unique_code,
                        'category_name' => $jsq_model->Category->name,
                        'entire_model_unique_code' => $jsq_model->parent_unique_code,
                        'model_unique_code' => $jsq_model->unique_code,
                        'model_name' => $jsq_model->name,
                        'status' => $status,
                        'factory_name' => $jsq_factory_name,
                        'factory_device_code' => $jsq_factory_device_code,
                        'made_at' => $jsq_made_at,
                        'scarping_at' => $jsq_scraping_at,
                        'work_area_unique_code' => $work_area_unique_code,
                        'is_part' => true,
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                        'part_model_unique_code' => $jsq_part_model->unique_code,
                        'part_model_name' => $jsq_part_model->name,
                        'part_category_id' => $jsq_part_model->part_category_id,
                    ] : null;
                };

                /**
                 * 油泵 所编号AZ
                 * 油泵 厂家BA
                 * 油泵 厂编号BB
                 * 油泵 型号BC
                 * 油泵 生产日期BD
                 * 油泵 寿命(年)BE
                 * @return array|null
                 * @throws ExcelInException
                 */
                $check_yb = function () use ($current_row, &$yb_serial_number, &$yb_factory_name, &$yb_factory_device_code, &$yb_model_name, &$yb_made_at, &$yb_life_year, &$excel_error, $work_area_unique_code, $status, $entire_instance_identity_code) {
                    // 验证厂家
                    // if ($yb_factory_name) {
                    //     $yb_factory = Factory::with([])->where('name', $yb_factory_name)->first();
                    //     if (!$yb_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(油泵)：{$yb_factory_name}");
                    //     // if (!$yb_factory) {
                    //     //     $excel_error['AS'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵厂家', $yb_factory_name, "没有找到厂家：{$yb_factory_name}");
                    //     // }
                    // } else {
                    //     $excel_error['AZ'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵厂家', $yb_factory_name, '没有填写油泵厂家');
                    //     $yb_factory_name = '';
                    // }
                    // 验证厂编号BB
                    if (!$yb_factory_device_code) {
                        $excel_error['BB'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵厂编号', $yb_factory_device_code, '没有填写油泵编号');
                        $yb_factory_device_code = '';
                    }
                    // 验证型号BC
                    $yb_model = null;
                    $yb_part_model = null;
                    if ($yb_serial_number && $yb_model_name) {
                        $yb_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $yb_model_name)->first();
                        if (!$yb_model) throw new ExcelInException("第{$current_row}行，没有找到油泵型号(器材)：{$yb_model_name}");
                        $yb_part_model = PartModel::with([])->where('name', $yb_model_name)->first();
                        if (!$yb_part_model) throw new ExcelInException("第{$current_row}行，没有找到油泵型号(型号)：{$yb_model_name}");
                    }
                    // 验证生产日期BD
                    if ($yb_made_at) {
                        try {
                            $yb_made_at = ExcelWriteHelper::getExcelDate($yb_made_at);
                        } catch (\Exception $e) {
                            $excel_error['BD'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵生产日期', $yb_made_at, $e->getMessage());
                            $yb_made_at = null;
                        }
                    } else {
                        $excel_error['BD'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵生产日期', $yb_made_at, '没有填写油泵生产日期');
                        $yb_made_at = null;
                    }
                    $yb_scraping_at = null;
                    // 验证寿命BE
                    if (is_numeric($yb_life_year)) {
                        if ($yb_life_year < 0) {
                            $excel_error['BE'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵寿命(年)', $yb_life_year, '油泵寿命必须填写正整数');
                            $yb_scraping_at = null;
                        } else {
                            $yb_scraping_at = Carbon::parse($yb_made_at)->addYears($yb_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['BE'] = ExcelWriteHelper::makeErrorResult($current_row, '油泵寿命(年)', $yb_life_year, '油泵寿命必须填写正整数');
                        $yb_scraping_at = null;
                    }

                    // return ($yb_serial_number && $yb_model) ? [
                    //     'part_model_unique_code' => $yb_part_model->unique_code,
                    //     'part_model_name' => $yb_part_model->name,
                    //     'entire_instance_identity_code' => '',
                    //     'status' => 'BUY_IN',
                    //     'factory_name' => $yb_factory_name,
                    //     'factory_device_code' => $yb_factory_device_code,
                    //     'identity_code' => '',
                    //     'entire_instance_serial_number' => '',
                    //     'category_unique_code' => $yb_part_model->category_unique_code,
                    //     'entire_model_unique_code' => $yb_part_model->entire_model_unique_code,
                    //     'part_category_id' => $yb_part_model->part_category_id,
                    //     'made_at' => $yb_made_at,
                    //     'scraping_at' => $yb_scraping_at,
                    //     'device_model_unique_code' => $yb_model->unique_code,
                    //     'serial_number' => $yb_serial_number,
                    //     'work_area_unique_code' => $work_area_unique_code,
                    // ] : null;

                    return ($yb_serial_number && $yb_model) ? [
                        'identity_code' => '',
                        'serial_number' => $yb_serial_number,
                        'category_unique_code' => $yb_model->category_unique_code,
                        'category_name' => $yb_model->Category->name,
                        'entire_model_unique_code' => $yb_model->parent_unique_code,
                        'model_unique_code' => $yb_model->unique_code,
                        'model_name' => $yb_model->name,
                        'status' => $status,
                        'factory_name' => $yb_factory_name,
                        'factory_device_code' => $yb_factory_device_code,
                        'made_at' => $yb_made_at,
                        'scarping_at' => $yb_scraping_at,
                        'work_area_unique_code' => $work_area_unique_code,
                        'is_part' => true,
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                        'part_model_unique_code' => $yb_part_model->unique_code,
                        'part_model_name' => $yb_part_model->name,
                        'part_category_id' => $yb_part_model->part_category_id,
                    ] : null;
                };

                /**
                 * 自动开闭器 所编号BF
                 * 自动开闭器 厂家BG
                 * 自动开闭器 厂编号BH
                 * 自动开闭器 型号BI
                 * 自动开闭器 生产日期BJ
                 * 自动开闭器 寿命(年)BK
                 * @return array|null
                 * @throws ExcelInException
                 */
                $check_zdkbq = function () use ($current_row, &$zdkbq_serial_number, &$zdkbq_factory_name, &$zdkbq_factory_device_code, &$zdkbq_model_name, &$zdkbq_made_at, &$zdkbq_life_year, &$excel_error, $work_area_unique_code, $status, $entire_instance_identity_code) {
                    // 验证厂家
                    // if ($zdkbq_factory_name) {
                    //     $zdkbq_factory = Factory::with([])->where('name', $zdkbq_factory_name)->first();
                    //     if (!$zdkbq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(自动开闭器)：{$zdkbq_factory_name}");
                    //     // if (!$zdkbq_factory) {
                    //     //     $excel_error['AY'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器厂家', $zdkbq_factory_name, "没有找到厂家：{$zdkbq_factory_name}");
                    //     // }
                    // } else {
                    //     $excel_error['BF'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器厂家', $zdkbq_factory_name, '没有填写自动开闭器厂家');
                    //     $zdkbq_factory_name = '';
                    // }
                    // 验证厂编号BH
                    if (!$zdkbq_factory_device_code) {
                        $excel_error['BH'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器厂编号', $zdkbq_factory_device_code, '没有填写自动开闭器编号');
                        $zdkbq_factory_device_code = '';
                    }
                    // 验证型号BI
                    $zdkbq_model = null;
                    $zdkbq_part_model = null;
                    if ($zdkbq_serial_number && $zdkbq_model_name) {
                        $zdkbq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $zdkbq_model_name)->first();
                        if (!$zdkbq_model) throw new ExcelInException("第{$current_row}行，没有找到自动开闭器型号(器材)：{$zdkbq_model_name}");
                        $zdkbq_part_model = PartModel::with([])->where('name', $zdkbq_model_name)->first();
                        if (!$zdkbq_part_model) throw new ExcelInException("第{$current_row}行，没有找到自动开闭器型号(设备)：{$zdkbq_model_name}");
                    }
                    // 验证生产日期BJ
                    if ($zdkbq_made_at) {
                        try {
                            $zdkbq_made_at = ExcelWriteHelper::getExcelDate($zdkbq_made_at);
                        } catch (\Exception $e) {
                            $excel_error['BJ'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器生产日期', $zdkbq_made_at, $e->getMessage());
                            $zdkbq_made_at = null;
                        }
                    } else {
                        $excel_error['BJ'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器生产日期', $zdkbq_made_at, '没有填写自动开闭器生产日期');
                        $zdkbq_made_at = null;
                    }
                    $zdkbq_scraping_at = null;
                    // 验证寿命BK
                    if (is_numeric($zdkbq_life_year)) {
                        if ($zdkbq_life_year < 0) {
                            $excel_error['BK'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器寿命(年)', $zdkbq_life_year, '自动开闭器寿命必须填写正整数');
                            $zdkbq_scraping_at = null;
                        } else {
                            $zdkbq_scraping_at = Carbon::parse($zdkbq_made_at)->addYears($zdkbq_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['BK'] = ExcelWriteHelper::makeErrorResult($current_row, '自动开闭器寿命(年)', $zdkbq_life_year, '自动开闭器寿命必须填写正整数');
                        $zdkbq_scraping_at = null;
                    }

                    // return ($zdkbq_serial_number && $zdkbq_model) ? [
                    //     'part_model_unique_code' => $zdkbq_part_model->unique_code,
                    //     'part_model_name' => $zdkbq_part_model->name,
                    //     'entire_instance_identity_code' => '',
                    //     'status' => 'BUY_IN',
                    //     'factory_name' => $zdkbq_factory_name,
                    //     'factory_device_code' => $zdkbq_factory_device_code,
                    //     'identity_code' => '',
                    //     'entire_instance_serial_number' => '',
                    //     'category_unique_code' => $zdkbq_part_model->category_unique_code,
                    //     'entire_model_unique_code' => $zdkbq_part_model->entire_model_unique_code,
                    //     'part_category_id' => $zdkbq_part_model->part_category_id,
                    //     'made_at' => $zdkbq_made_at,
                    //     'scraping_at' => $zdkbq_scraping_at,
                    //     'device_model_unique_code' => $zdkbq_model->unique_code,
                    //     'serial_number' => $zdkbq_serial_number,
                    //     'work_area_unique_code' => $work_area_unique_code,
                    // ] : null;

                    return ($zdkbq_serial_number && $zdkbq_model) ? [
                        'identity_code' => '',
                        'serial_number' => $zdkbq_serial_number,
                        'category_unique_code' => $zdkbq_model->category_unique_code,
                        'category_name' => $zdkbq_model->Category->name,
                        'entire_model_unique_code' => $zdkbq_model->parent_unique_code,
                        'model_unique_code' => $zdkbq_model->unique_code,
                        'model_name' => $zdkbq_model->name,
                        'status' => $status,
                        'factory_name' => $zdkbq_factory_name,
                        'factory_device_code' => $zdkbq_factory_device_code,
                        'made_at' => $zdkbq_made_at,
                        'scarping_at' => $zdkbq_scraping_at,
                        'work_area_unique_code' => $work_area_unique_code,
                        'is_part' => true,
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                        'part_model_unique_code' => $zdkbq_part_model->unique_code,
                        'part_model_name' => $zdkbq_part_model->name,
                        'part_category_id' => $zdkbq_part_model->part_category_id,
                    ] : null;
                };

                /**
                 * 摩擦连接器 所编号BL
                 * 摩擦连接器 厂家BM
                 * 摩擦连接器 厂编号BN
                 * 摩擦连接器 型号BO
                 * 摩擦连接器 生产日期BP
                 * 摩擦连接器 寿命(年)BQ
                 * @return array|null
                 * @throws ExcelInException
                 */
                $check_mcljq = function () use ($current_row, &$mcljq_serial_number, &$mcljq_factory_name, &$mcljq_factory_device_code, &$mcljq_model_name, &$mcljq_made_at, &$mcljq_life_year, &$excel_error, $work_area_unique_code, $status, $entire_instance_identity_code) {
                    // 验证厂家
                    // if ($mcljq_factory_name) {
                    //     $mcljq_factory = Factory::with([])->where('name', $mcljq_factory_name)->first();
                    //     if (!$mcljq_factory) throw new ExcelInException("第{$current_row}行，没有找到厂家(摩擦连接器)：{$mcljq_factory_name}");
                    //     // if (!$mcljq_factory) {
                    //     //     $excel_error['BE'] = ExcelWriteHelper::makeErrorResult($current_row, '电机厂家', $mcljq_factory_name, "没有找到厂家：{$mcljq_factory_name}");
                    //     // }
                    // } else {
                    //     $excel_error['BL'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器厂家', $mcljq_factory_name, '没有填写摩擦连接器厂家');
                    //     $mcljq_factory_name = '';
                    // }
                    // 验证厂编号BN
                    if (!$mcljq_factory_device_code) {
                        $excel_error['BN'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器厂编号', $mcljq_factory_device_code, '没有填写摩擦连接器编号');
                        $mcljq_factory_device_code = '';
                    }
                    // 验证型号BO
                    $mcljq_model = null;
                    $mcljq_part_model = null;
                    if ($mcljq_serial_number && $mcljq_model_name) {
                        $mcljq_model = EntireModel::with([])->where('is_sub_model', true)->where('name', $mcljq_model_name)->first();
                        if (!$mcljq_model) throw new ExcelInException("第{$current_row}行，没有找到摩擦连接器型号(器材)：{$mcljq_model_name}");
                        $mcljq_part_model = PartModel::with([])->where('name', $mcljq_model_name)->first();
                        if (!$mcljq_part_model) throw new ExcelInException("第{$current_row}行，没有找到摩擦连接器型号(型号)：{$mcljq_model_name}");
                    }
                    // 验证所编号
                    // if ($mcljq_serial_number && $mcljq_model_name) {
                    //     $pi = PartInstance::with([])->where('serial_number', $mcljq_serial_number)->where('device_model_unique_code', $mcljq_model->unique_code)->first();
                    //     if ($pi) throw new ExcelInException("第{$current_row}行，摩擦连接器：{$mcljq_serial_number}所编号重复");
                    // }
                    // 验证生产日期BP
                    if ($mcljq_made_at) {
                        try {
                            $mcljq_made_at = ExcelWriteHelper::getExcelDate($mcljq_made_at);
                        } catch (\Exception $e) {
                            $excel_error['BP'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器生产日期', $mcljq_made_at, $e->getMessage());
                            $mcljq_made_at = null;
                        }
                    } else {
                        $excel_error['BP'] = ExcelWriteHelper::makeErrorResult($current_row, '电机生产日期', $mcljq_made_at, '没有填写电机生产日期');
                        $mcljq_made_at = null;
                    }
                    // 验证寿命BQ
                    $mcljq_scraping_at = null;
                    if (is_numeric($mcljq_life_year)) {
                        if ($mcljq_life_year < 0) {
                            $excel_error['BQ'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器寿命(年)', $mcljq_life_year, '摩擦连接器寿命必须填写正整数');
                            $mcljq_scraping_at = null;
                        } else {
                            $mcljq_scraping_at = Carbon::parse($mcljq_made_at)->addYears($mcljq_life_year)->format('Y-m-d');
                        }
                    } else {
                        $excel_error['BQ'] = ExcelWriteHelper::makeErrorResult($current_row, '摩擦连接器寿命(年)', $mcljq_life_year, '摩擦连接器寿命必须填写正整数');
                        $mcljq_scraping_at = null;
                    }

                    // return ($mcljq_serial_number && $mcljq_model) ? [
                    //     'part_model_unique_code' => $mcljq_part_model->unique_code,
                    //     'part_model_name' => $mcljq_part_model->name,
                    //     'entire_instance_identity_code' => '',
                    //     'status' => 'BUY_IN',
                    //     'factory_name' => $mcljq_factory_name,
                    //     'factory_device_code' => $mcljq_factory_device_code,
                    //     'identity_code' => '',
                    //     'entire_instance_serial_number' => '',
                    //     'category_unique_code' => $mcljq_part_model->category_unique_code,
                    //     'entire_model_unique_code' => $mcljq_part_model->entire_model_unique_code,
                    //     'part_category_id' => $mcljq_part_model->part_category_id,
                    //     'made_at' => $mcljq_made_at,
                    //     'scraping_at' => $mcljq_scraping_at,
                    //     'device_model_unique_code' => $mcljq_model->unique_code,
                    //     'serial_number' => $mcljq_serial_number,
                    //     'work_area_unique_code' => $work_area_unique_code,
                    // ] : null;

                    return ($mcljq_serial_number && $mcljq_model) ? [
                        'identity_code' => '',
                        'serial_number' => $mcljq_serial_number,
                        'category_unique_code' => $mcljq_model->category_unique_code,
                        'category_name' => $mcljq_model->Category->name,
                        'entire_model_unique_code' => $mcljq_model->parent_unique_code,
                        'model_unique_code' => $mcljq_model->unique_code,
                        'model_name' => $mcljq_model->name,
                        'status' => $status,
                        'factory_name' => $mcljq_factory_name,
                        'factory_device_code' => $mcljq_factory_device_code,
                        'made_at' => $mcljq_made_at,
                        'scarping_at' => $mcljq_scraping_at,
                        'work_area_unique_code' => $work_area_unique_code,
                        'is_part' => true,
                        'entire_instance_identity_code' => $entire_instance_identity_code,
                        'part_model_unique_code' => $mcljq_part_model->unique_code,
                        'part_model_name' => $mcljq_part_model->name,
                        'part_category_id' => $mcljq_part_model->part_category_id,
                    ] : null;
                };

                // 写入待插入数据
                $new_entire_instances[] = [
                    'serial_number' => $om_serial_number,
                    'part_instances' => [
                        'dj' => $check_dj(),  // 电机
                        'ywjcq_l' => $check_ywjcq_l(),  // 移位接触器(左)
                        'ywjcq_r' => $check_ywjcq_r(),  // 移位接触器(右)
                        'jsq' => $check_jsq(),  // 减速器
                        'yb' => $check_yb(),  // 油泵
                        'zdkbq' => $check_zdkbq(),  // 自动开闭器
                        'mcljq' => $check_mcljq(),  // 摩擦连接器
                    ],
                ];
                // 错误数据统计
                if (!empty($excel_error)) $excel_errors[] = $excel_error;

                $current_row++;
            }

            // 按照型号进行分组
            $new_entire_instances_by_entire_model_unique_code = collect($new_entire_instances)->groupBy('entire_model_unique_code')->toArray();
            // 获取设备对应型号总数
            $entire_instance_counts = EntireInstanceCount::with([])->pluck('count', 'entire_model_unique_code');

            // 赋码
            foreach ($new_entire_instances_by_entire_model_unique_code as $entire_model_unique_code => $neis) {
                // 部件赋码
                foreach ($new_entire_instances_by_entire_model_unique_code[$entire_model_unique_code] as $k => $ei) {
                    foreach ($ei['part_instances'] as $pk => $part_instance) {
                        $pic = $entire_instance_counts->get($part_instance['model_unique_code'], 0);
                        $part_instance_identity_code = $part_instance['model_unique_code'] . env('ORGANIZATION_CODE') . str_pad(++$pic, 7, '0', STR_PAD_LEFT) . 'H';
                        if ($part_instance) {
                            $new_entire_instances_by_entire_model_unique_code[$entire_model_unique_code][$k]['part_instances'][$pk]['identity_code'] = $part_instance_identity_code;
                            $entire_instance_counts[$part_instance['model_unique_code']] = $pic;
                        }
                    }
                }
            }

            // 写入数据库
            DB::begintransaction();

            // 添加新设备器材
            $current_row_for_fix_workflow = 3;
            $inserted_count = 0;
            $new_entire_instances = array_collapse($new_entire_instances_by_entire_model_unique_code);
            $new_entire_instance_logs = [];
            unset($new_entire_instance);
            foreach ($new_entire_instances as $new_entire_instance) {
                // 添加部件
                foreach ($new_entire_instance['part_instances'] as $part_instance) {
                    if ($part_instance) {
                        EntireInstance::with([])->create($part_instance);

                        // 生成部件赋码日志
                        $new_entire_instance_logs[] = [
                            'created_at' => now()->format('Y-m-d'),
                            'updated_at' => now()->format('Y-m-d'),
                            'name' => '赋码',
                            'description' => $part_instance['made_at'] ? ($part_instance['scarping_at'] ? "出厂日期：{$part_instance['made_at']}；报废日期：{$part_instance['scarping_at']}；" : "出厂日期：{$part_instance['made_at']}；") : '',
                            'entire_instance_identity_code' => $part_instance['identity_code'],
                            'type' => 0,
                            'url' => '',
                            'material_type' => 'ENTIRE',
                            'operator_id' => 1,
                            'station_unique_code' => '',
                        ];
                    }
                }
            }

            DB::table('entire_instance_logs')->insert($new_entire_instance_logs);

            // 更新该型号下的所有设备器材总数
            EntireInstanceCount::updates($entire_instance_counts->toArray());
            DB::commit();

            dd("成功导入：" . count($new_entire_instances));
        } catch (Throwable $e) {
            dd($e->getMessage(), $e->getFile(), $e->getLine());
            return CommonFacade::ddExceptionWithAppDebug($e);
        }
    }

    /**
     * 备份旧种类型归属关系
     */
    final public function k1()
    {
        try {
            // 增加字段 备份旧种类型字段
            DB::connection('b053')->statement("alter table entire_instances add old_model_unique_code varchar(50) default '' not null comment '旧型号编码'");
            DB::connection('b053')->statement("alter table entire_instances add old_entire_model_unique_code varchar(50) default '' not null comment '旧类型编码'");
            DB::connection('b053')->statement("alter table entire_instances add old_category_unique_code varchar(50) default '' not null comment '旧种类编码'");

            DB::connection('b053')->select("update entire_instances set old_model_unique_code = model_unique_code where true");
            DB::connection('b053')->select("update entire_instances set old_entire_model_unique_code = entire_model_unique_code where true");
            DB::connection('b053')->select("update entire_instances set old_category_unique_code = category_unique_code where true");
        } catch (Exception $e) {
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * 将设备型号字段内容改为类型
     */
    final public function k2()
    {
        try {
            // 将设备型号字段内容改为类型
            DB::connection('b053')->select("update entire_instances set model_unique_code = entire_model_unique_code where category_unique_code like 'S%'");
        } catch (Exception $e) {
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    final public function comboStation()
    {
        DB::beginTransaction();
        try {
            /*
             * breakdown_logs.maintain_station_name
             * collect_device_order_entire_instances.maintain_station_name
             * entire_instances.maintain_station_name
             * entire_instances.last_maintain_station_name
             * entire_instances.bind_station_name
             * fix_workflows.maintain_station_name
             * print_new_location_and_old_entire_instances.old_maintain_station_name
             * repair_base_breakdown_order_entire_instances.maintain_station_name
             * repair_base_plan_out_cycle_fix_bills.station_name
             * repair_base_plan_out_cycle_fix_entire_instances.station_name
             * station_install_location_records.maintain_station_name
             * station_locations.maintain_station_name
             * temp_station_eis.maintain_station_name
             * temp_station_position.maintain_station_name
             * v250_workshop_in_entire_instances.maintain_station_name
             * v250_workshop_out_entire_instances.maintain_station_name
             * warehouse_in_batch_reports.maintain_station_name
             * warehouse_reports.station_name
             * warehouse_report_entire_instances.maintain_station_name
             * warehouse_storage_batch_reports.maintain_station_name
             */

            $stations = [
                '龙形村线路所' => '龙形村所',
                '张吉怀中继13' => '张吉怀中继13站',
                '张吉怀中继14' => '张吉怀中继14站',
                '沙堤线路所' => '沙堤所',
                '张吉怀中继1' => '张吉怀中继1站',
                '张吉怀中继2' => '张吉怀中继2站',
                '张吉怀中继3' => '张吉怀中继3站',
                '张吉怀中继4' => '张吉怀中继4站',
                '张吉怀中继5' => '张吉怀中继5站',
                '张吉怀中继6' => '张吉怀中继6站',
                '张吉怀中继7' => '张吉怀中继7站',
                '张吉怀中继8' => '张吉怀中继8站',
                '张吉怀中继9' => '张吉怀中继9站',
                '吉首东' => '吉首东站',
                '张吉怀中继10' => '张吉怀中继10站',
                '凤凰古城' => '凤凰古城站',
                '张吉怀中继11' => '张吉怀中继11站',
                '麻阳西' => '麻阳西站',
                '张吉怀中继12' => '张吉怀中继12',
            ];

            foreach ($stations as $old_name => $new_name) {
                DB::table('maintains')->where('name', $old_name)->update(['name' => $new_name]);
                DB::table('breakdown_logs')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('collect_device_order_entire_instances')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('entire_instances')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('entire_instances')->where('last_maintain_station_name', $old_name)->update(['last_maintain_station_name' => $new_name]);
                DB::table('entire_instances')->where('bind_station_name', $old_name)->update(['bind_station_name' => $new_name]);
                DB::table('fix_workflows')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('print_new_location_and_old_entire_instances')->where('old_maintain_station_name', $old_name)->update(['old_maintain_station_name' => $new_name]);
                DB::table('repair_base_breakdown_order_entire_instances')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('repair_base_plan_out_cycle_fix_bills')->where('station_name', $old_name)->update(['station_name' => $new_name]);
                DB::table('repair_base_plan_out_cycle_fix_entire_instances')->where('station_name', $old_name)->update(['station_name' => $new_name]);
                DB::table('station_install_location_records')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('station_locations')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('temp_station_eis')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('temp_station_position')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('v250_workshop_in_entire_instances')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('v250_workshop_out_entire_instances')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('warehouse_in_batch_reports')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('warehouse_reports')->where('station_name', $old_name)->update(['station_name' => $new_name]);
                DB::table('warehouse_report_entire_instances')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
                DB::table('warehouse_storage_batch_reports')->where('maintain_station_name', $old_name)->update(['maintain_station_name' => $new_name]);
            }


            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    final public function comboSceneWorkshop()
    {
        /**
         * breakdown_logs.scene_workshop_name
         * collect_device_order_entire_instances.maintain_workshop_name
         * entire_instances.maintain_workshop_name
         * entire_instances.last_maintain_workshop_name
         * print_new_location_and_old_entire_instances.old_maintain_workshop_name
         * repair_base_breakdown_order_entire_instances.scene_workshop_name
         * station_locations.scene_workshop_name
         * temp_station_position.scene_workshop_name
         * warehouse_reports.scene_workshop_name
         * warehouse_reports_display_board_statistics.scene_workshop_name
         */

        DB::beginTransaction();
        try {
            $scene_workshops = [
                '怀化西信号车间' => '怀化西驼峰信号车间',
                // '怀化西驼峰信号车间' => '怀化西信号车间',
            ];

            foreach ($scene_workshops as $old_name => $new_name) {
                DB::table('maintains')->where('name', $old_name)->update(['name' => $new_name]);
                DB::table('breakdown_logs')->where('scene_workshop_name', $old_name)->update(['scene_workshop_name' => $new_name,]);
                DB::table('collect_device_order_entire_instances')->where('maintain_workshop_name', $old_name)->update(['maintain_workshop_name' => $new_name,]);
                DB::table('entire_instances')->where('maintain_workshop_name', $old_name)->update(['maintain_workshop_name' => $new_name,]);
                DB::table('entire_instances')->where('last_maintain_workshop_name', $old_name)->update(['last_maintain_workshop_name' => $new_name,]);
                DB::table('print_new_location_and_old_entire_instances')->where('old_maintain_workshop_name', $old_name)->update(['old_maintain_workshop_name' => $new_name,]);
                DB::table('repair_base_breakdown_order_entire_instances')->where('scene_workshop_name', $old_name)->update(['scene_workshop_name' => $new_name,]);
                DB::table('station_locations')->where('scene_workshop_name', $old_name)->update(['scene_workshop_name' => $new_name,]);
                DB::table('temp_station_position')->where('scene_workshop_name', $old_name)->update(['scene_workshop_name' => $new_name,]);
                DB::table('warehouse_reports')->where('scene_workshop_name', $old_name)->update(['scene_workshop_name' => $new_name,]);
                DB::table('warehouse_report_display_board_statistics')->where('scene_workshop_name', $old_name)->update(['scene_workshop_name' => $new_name,]);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    final public function comboFactory()
    {
        DB::beginTransaction();
        try {
            // 改名
            $update_factory_names = [
                '施耐德电气信息技术（中国）有限公司（APC）' => '施耐德电气信息技术（中国）有限公司（APC）',  // 118
                '美国电力转换公司(APC）' => '施耐德电气信息技术（中国）有限公司（APC）',  // 118
                '施耐德' => '施耐德电气信息技术（中国）有限公司（APC）',  // 118
                '湖南中车时代通信信号有限公司' => '湖南中车时代通信信号有限公司',  // 184
                '湖南中车时代通信信号有限公司（株洲中车时代电气股份有限公司）' => '湖南中车时代通信信号有限公司',  // 184
                '中国铁路通信信号股份有限公司(中国通号CRSC)' => '中国铁路通信信号股份有限公司(中国通号CRSC)', // 221
                '中国铁路通信信号股份有限公司' => '中国铁路通信信号股份有限公司(中国通号CRSC)',  // 221
            ];

            foreach ($update_factory_names as $old_name => $new_name) {
                DB::table('entire_instances')->where('factory_name', $old_name)->update(['factory_name' => $new_name]);
            }

            DB::table("factories")->truncate();

            // 改代码
            $insert_factories = [
                '中国铁道科学研究院' => 'P0001',
                '北京全路通信信号研究设计院集团有限公司' => 'P0002',
                '北京市华铁信息技术开发总公司' => 'P0003',
                '通号(北京)轨道工业集团有限公司' => 'P0004',
                '河南辉煌科技股份有限公司' => 'P0005',
                '上海铁大电信科技股份有限公司' => 'P0006',
                '卡斯柯信号有限公司' => 'P0007',
                '北京和利时系统工程有限公司' => 'P0008',
                '西门子股份公司' => 'P0009',
                '沈阳铁路信号有限责任公司' => 'P0010',
                '北京安达路通铁路信号技术有限公司' => 'P0011',
                '北京安润通电子技术开发有限公司' => 'P0012',
                '北京北信丰元铁路电子设备有限公司' => 'P0013',
                '北京国铁路阳技术有限公司' => 'P0014',
                '北京交大微联科技有限公司' => 'P0015',
                '北京交通铁路技术研究所有限公司' => 'P0016',
                '北京津宇嘉信科技股份有限公司' => 'P0017',
                '北京全路通铁路专用器材工厂' => 'P0018',
                '北京全路通信信号研究设计院有限公司' => 'P0019',
                '北京铁路局太原电务器材厂' => 'P0020',
                '北京铁路信号有限公司' => 'P0021',
                '成都铁路通信仪器仪表厂' => 'P0022',
                '大连电机厂' => 'P0023',
                '丹东中天照明电器有限公司' => 'P0024',
                '固安北信铁路信号有限公司' => 'P0025',
                '固安通号铁路器材有限公司' => 'P0026',
                '固安信通信号技术股份有限公司' => 'P0027',
                '哈尔滨复盛铁路工电器材有限公司' => 'P0028',
                '哈尔滨铁晶铁路通信信号器材厂' => 'P0029',
                '哈尔滨铁路局直属机关通信信号器材厂' => 'P0030',
                '合肥市中铁电务有限责任公司' => 'P0031',
                '河北德凯铁路信号器材有限公司' => 'P0032',
                '河北冀胜轨道科技股份有限公司' => 'P0033',
                '河北南皮铁路器材有限责任公司' => 'P0034',
                '黑龙江瑞兴科技股份有限公司' => 'P0035',
                '济南三鼎电气有限责任公司' => 'P0036',
                '锦州长青铁路器材厂' => 'P0037',
                '洛阳通号铁路器材有限公司' => 'P0038',
                '南昌铁路电务设备厂' => 'P0039',
                '宁波鸿钢铁路信号设备厂' => 'P0040',
                '饶阳铁建电务器材有限公司' => 'P0041',
                '厦门科华恒盛股份有限公司' => 'P0042',
                '山特电子(深圳）有限公司' => 'P0043',
                '陕西赛福特铁路器材有限公司' => 'P0044',
                '陕西省咸阳市国营七九五厂' => 'P0045',
                '上海电捷工贸有限公司' => 'P0046',
                '上海铁路通信有限公司' => 'P0047',
                '上海铁路信号器材有限公司' => 'P0048',
                '深圳科安达电子科技股份有限公司' => 'P0049',
                '深圳市恒毅兴实业有限公司' => 'P0050',
                '深圳市铁创科技发展有限公司' => 'P0051',
                '沈阳宏达电机制造有限公司' => 'P0052',
                '沈阳铁路器材厂' => 'P0053',
                '四川浩铁仪器仪表有限公司' => 'P0054',
                '天津赛德电气设备有限公司' => 'P0055',
                '天津铁路信号有限责任公司' => 'P0056',
                '天水佳信铁路电气有限公司' => 'P0057',
                '天水铁路器材厂' => 'P0058',
                '天水铁路信号工厂' => 'P0059',
                '温州凯信电气有限公司' => 'P0060',
                '西安嘉信铁路器材有限公司' => 'P0061',
                '西安全路通号器材研究有限公司' => 'P0062',
                '西安铁路信号有限责任公司' => 'P0063',
                '西安无线电二厂' => 'P0064',
                '西安信通博瑞特铁路信号有限公司' => 'P0065',
                '西安宇通铁路器材有限公司' => 'P0066',
                '西安中凯铁路电气有限责任公司' => 'P0067',
                '偃师市泰达电务设备有限公司' => 'P0068',
                '浙江金华铁路信号器材有限公司' => 'P0069',
                '郑州世创电子科技有限公司' => 'P0070',
                '中国铁路通信信号集团有限公司' => 'P0071',
                'CSEE公司' => 'P0072',
                'GE公司' => 'P0073',
                '艾佩斯电力设施有限公司' => 'P0074',
                '安萨尔多' => 'P0075',
                '北京阿尔卡特' => 'P0076',
                '北京从兴科技有限公司' => 'P0077',
                '北京电务器材厂' => 'P0078',
                '北京国正信安系统控制技术有限公司' => 'P0079',
                '北京黄土坡信号厂' => 'P0080',
                '北京锦鸿希电信息技术股份有限公司' => 'P0081',
                '北京联能科技有限公司' => 'P0082',
                '北京联泰信科铁路信通技术有限公司' => 'P0083',
                '北京全路通号器材研究有限公司' => 'P0084',
                '北京世纪东方国铁科技股份有限公司' => 'P0085',
                '北京铁路分局西直门电务段' => 'P0086',
                '北京铁通康达铁路通信信号设备有限公司' => 'P0087',
                '北京兆唐有限公司' => 'P0088',
                '长沙南车电气设备有限公司' => 'P0089',
                '成都铁路通信设备有限责任公司' => 'P0090',
                '丹东东明铁路灯泡厂' => 'P0091',
                '奉化市皓盛铁路电务器材有限公司' => 'P0092',
                '广州华炜科技有限公司' => 'P0093',
                '广州铁路电务工厂' => 'P0094',
                '哈尔滨路通科技开发有限公司' => 'P0095',
                '哈尔滨市科佳通用机电有限公司' => 'P0096',
                '哈尔滨铁路通信信号器材厂' => 'P0097',
                '哈铁信号器材厂' => 'P0098',
                '鹤壁博大电子科技有限公司' => 'P0099',
                '湖南湘依铁路机车电器股份有限公司' => 'P0100',
                '兰州大成铁路信号有限责任公司' => 'P0101',
                '兰州铁路电务器材有限公司' => 'P0102',
                '柳州辰天科技有限责任公司' => 'P0103',
                '牡丹江电缆厂' => 'P0104',
                '南非断路器有限公司' => 'P0105',
                '南京电子管厂' => 'P0106',
                '南京圣明科技有限公司' => 'P0107',
                '宁波思高软件科技有限公司' => 'P0108',
                '齐齐哈尔电务器材厂' => 'P0109',
                '青岛四机易捷铁路器材有限公司' => 'P0110',
                '绕阳铁建电务器材有限公司' => 'P0111',
                '陕西众信铁路设备有限公司' => 'P0112',
                '上海电务工厂' => 'P0113',
                '上海瑞信电气有限公司' => 'P0114',
                '上海友邦电气股份有限公司' => 'P0115',
                '深圳长龙铁路电子工程有限公司' => 'P0116',
                '沈阳电务器材厂' => 'P0117',
                '太原市京丰铁路电务器材制造有限公司' => 'P0119',
                '天水铁路电缆有限责任公司' => 'P0120',
                '天水铁路信号灯泡有限公司' => 'P0121',
                '万可电子（天津）有限公司' => 'P0122',
                '乌鲁木齐铁信公司' => 'P0123',
                '无锡同心铁路器材有限公司' => 'P0124',
                '西安大正信号有限公司' => 'P0125',
                '西安电务器材厂' => 'P0126',
                '西安东鑫瑞利德电子有限责任公司' => 'P0127',
                '西安开源仪表研究所' => 'P0128',
                '西安凯士信控制显示技术有限公司' => 'P0129',
                '西安天元铁路器材责任有限公司' => 'P0130',
                '西安通达电务器材厂' => 'P0131',
                '西安西电光电缆有限公司' => 'P0132',
                '西安西门子信号有限公司' => 'P0133',
                '西安信达铁路专用器材开发有限公司' => 'P0134',
                '襄樊电务器材厂' => 'P0135',
                '新铁德奥道岔有限公司' => 'P0136',
                '扬中市新华电务配件厂' => 'P0137',
                '郑州二七科达铁路器材厂' => 'P0138',
                '郑州华容电器科技有限公司' => 'P0139',
                '郑州铁路通号电务器材厂' => 'P0140',
                '中国铁道科学研究院通信信号研究所' => 'P0141',
                '重庆森威电子有限公司' => 'P0142',
                '株洲南车时代电气股份有限公司' => 'P0143',
                '北京怡蔚丰达电子技术有限公司' => 'P0144',
                '常州东方铁路器材有限公司' => 'P0145',
                'ABB（中国）有限公司' => 'P0146',
                '施耐德电气信息技术（中国）有限公司（APC）' => 'P0118',
                'PORYAN' => 'P0149',
                '西安持信铁路器材有限公司' => 'P0150',
                '埃伯斯电子（上海）有限公司' => 'P0151',
                'Emerson(美国艾默生电气公司）' => 'P0152',
                '广东保顺能源股份有限公司' => 'P0153',
                '宝胜科技创新股份有限公司' => 'P0154',
                '北方交通大学信号抗干扰实验站' => 'P0155',
                '北京安英特技术开发公司' => 'P0156',
                '沧州铁路信号厂' => 'P0157',
                '北京大地同丰科技有限公司' => 'P0158',
                '北京丰台铁路电器元件厂' => 'P0159',
                '北京冠九州铁路器材有限公司' => 'P0160',
                '北京市交大路通科技有限公司' => 'P0161',
                '北京康迪森交通控制技术有限责任公司' => 'P0162',
                '北京六联信息技术研究所' => 'P0163',
                '北京施维格科技有限公司' => 'P0164',
                '北京世纪瑞尔技术股份有限公司' => 'P0165',
                '北京市丰台铁路电气元件厂' => 'P0166',
                '北京泰雷兹交通自动化控制系统有限公司' => 'P0167',
                '铁科院(北京)工程咨询有限公司' => 'P0168',
                '北京西南交大盛阳科技有限公司' => 'P0169',
                '朝阳电源有限公司' => 'P0170',
                '戴尔（Dell）' => 'P0171',
                '丹东铁路通达保安器件有限公司' => 'P0172',
                '德州津铁物资有限公司' => 'P0173',
                '天水长信铁路信号设备有限公司' => 'P0174',
                '天水铁路信号电缆厂' => 'P0175',
                '广州舰铭铁路设备有限公司' => 'P0176',
                '广州铁路（集团）公司电务工厂' => 'P0177',
                '通号通信信息集团有限公司广州分公司' => 'P0178',
                '广州忘平信息科技有限公司' => 'P0179',
                '杭州慧景科技股份有限公司' => 'P0180',
                '合肥中交电气有限公司' => 'P0181',
                '鹤壁市华研电子科技有限公司' => 'P0182',
                '湖北洪乐电缆股份有限公司' => 'P0183',
                '华为技术股份有限公司（HUAWEI）' => 'P0185',
                '惠普（HP）' => 'P0186',
                '济南瑞通铁路电务有限责任公司' => 'P0187',
                '江苏亨通电力电缆有限公司' => 'P0188',
                '江苏今创安达交通信息技术公司' => 'P0189',
                '焦作铁路电缆有限责任公司' => 'P0190',
                '上海良信电器股份有限公司' => 'P0191',
                '凌华科技(中国)有限公司' => 'P0192',
                '庞巴迪公司（Bombardier Inc.）' => 'P0193',
                '日本京三(KYOSAN)' => 'P0194',
                '瑞网数据通信设备（北京）有限公司' => 'P0195',
                '山西润泽丰科技开发有限公司' => 'P0196',
                '陕西通号铁路器材有限公司' => 'P0197',
                '上海德意达电子电器设备有限公司' => 'P0198',
                '上海慧轩电气科技有限公司' => 'P0199',
                '上海新干通通信设备有限公司' => 'P0200',
                '金华铁路通信信号器材厂' => 'P0201',
                '苏州飞利浦消费电子有限公司' => 'P0202',
                '武汉瑞控电气工程有限公司' => 'P0203',
                '天津七一二通信广播有限公司' => 'P0204',
                '天津海斯特电机有限公司' => 'P0205',
                '天津精达铁路器材有限公司' => 'P0206',
                '天水广信铁路信号公司' => 'P0207',
                '武汉贝通科技有限公司' => 'P0208',
                '西安盛达铁路电器有限公司' => 'P0209',
                '西安铁通科技开发实业公司' => 'P0210',
                '西安唯迅监控设备有限公司' => 'P0211',
                '西安铁路信号工厂' => 'P0212',
                '西安一信铁路器材有限公司' => 'P0213',
                '研华科技（中国）有限公司' => 'P0214',
                '扬州长城铁路器材有限公司' => 'P0215',
                '英沃思科技(北京)有限公司' => 'P0216',
                '宁波市皓盛铁路电务器材有限公司' => 'P0217',
                '郑州铁路专用器材有限公司' => 'P0218',
                '中车株洲电力机车研究所有限公司' => 'P0219',
                '中达电通股份有限公司' => 'P0220',
                '中利科技集团股份有限公司' => 'P0222',
                '中兴通讯股份有限公司' => 'P0223',
                '株洲中车时代电气股份有限公司' => 'P0224',
                'COMLAB（北京）通信系统设备有限公司' => 'P0225',
                '北京博飞电子技术有限责任公司' => 'P0226',
                '北京鼎汉技术集团股份有限公司' => 'P0227',
                '北京华铁信息技术有限公司' => 'P0228',
                '北京交大思诺科技股份有限公司' => 'P0229',
                '北京全路通信信号研究设计院集团有限公司广州分公司' => 'P0230',
                '北京信达环宇安全网络技术有限公司' => 'P0231',
                '北京英诺威尔科技股份有限公司' => 'P0232',
                '北京智讯天成技术有限公司' => 'P0233',
                '北京中智润邦科技有限公司' => 'P0234',
                '长沙飞波通信技术有限公司' => 'P0235',
                '长沙斯耐沃机电有限公司' => 'P0236',
                '长沙铁路建设有限公司' => 'P0237',
                '长沙智创机电设备有限公司' => 'P0238',
                '郴州长治建筑有限公司' => 'P0239',
                '楚天龙股份有限公司' => 'P0240',
                '东方腾大工程维修服务有限公司' => 'P0241',
                '高新兴创联科技有限公司' => 'P0242',
                '广东省肇庆市燊荣建筑安装装饰工程有限公司' => 'P0243',
                '广东永达建筑有限公司' => 'P0244',
                '广宁县第二建筑工程有限公司' => 'P0245',
                '广州里程通信设备有限公司' => 'P0246',
                '广州赛力迪软件科技有限公司' => 'P0247',
                '广州盛佳建业科技有限责任公司' => 'P0248',
                '广州市大周电子科技有限公司' => 'P0249',
                '广州市广源电子科技有限公司' => 'P0250',
                '广州昊明通信设备有限公司' => 'P0251',
                '海口思宏电子工程有限公司' => 'P0252',
                '海南国鑫实业有限公司' => 'P0253',
                '海南海岸网络科技有限公司' => 'P0254',
                '海南海口建筑集团有限公司' => 'P0255',
                '海南华联安视智能工程有限公司' => 'P0256',
                '海南建祥瑞建筑工程有限公司' => 'P0257',
                '海南中弘建设工程有限公司' => 'P0258',
                '海南寰宇华强网络科技有限公司' => 'P0259',
                '海南鑫泰隆水电工程有限公司' => 'P0260',
                '杭州慧景科技有限公司' => 'P0261',
                '河南蓝信科技有限责任公司' => 'P0262',
                '河南思维自动化设备股份有限公司' => 'P0263',
                '湖南长铁装备制造有限公司' => 'P0264',
                '湖南飞波工程有限公司' => 'P0265',
                '湖南省石柱建筑工程有限公司' => 'P0266',
                '湖南中车时代通信信号有限公司' => 'P0184',
                '怀化铁路工程有限公司' => 'P0268',
                '怀化铁路工程总公司' => 'P0269',
                '江苏理士电池有限公司' => 'P0270',
                '江苏万华通信科技有限公司' => 'P0271',
                '南京盛佳建业科技有限责任公司' => 'P0272',
                '南京泰通科技股份有限公司' => 'P0273',
                '宁津南铁重工设备有限公司' => 'P0274',
                '饶阳县路胜铁路信号器材有限公司' => 'P0275',
                '陕西西北铁道电子股份有限公司' => 'P0276',
                '上海仁昊电子科技有限公司' => 'P0277',
                '深圳市速普瑞科技有限公司' => 'P0278',
                '深圳市英维克科技有限公司' => 'P0279',
                '通号（长沙）轨道交通控制技术有限公司' => 'P0280',
                '通号工程局集团有限公司' => 'P0281',
                '维谛技术有限公司' => 'P0282',
                '武汉佳和电气有限公司' => 'P0283',
                '西安博优铁路机电有限责任公司' => 'P0284',
                '浙江友诚铁路设备科技有限公司' => 'P0285',
                '中国海底电缆建设有限公司' => 'P0286',
                '中国铁道科学研究院集团有限公司通信信号研究所' => 'P0287',
                '中国铁建电气化局集团有限公司' => 'P0288',
                '中国铁路通信信号股份有限公司(中国通号CRSC)' => 'P0221',
                '中国铁路通信信号上海工程局集团有限公司' => 'P0290',
                '中山市德全建设工程有限公司' => 'P0291',
                '中铁电气化局第一工程有限公司' => 'P0292',
                '中铁电气化局集团第三工程有限公司' => 'P0293',
                '中铁电气化局集团有限公司' => 'P0294',
                '中铁二十五局集团电务工程有限公司' => 'P0295',
                '中铁建电气化局集团第四工程有限公司' => 'P0296',
                '中铁四局集团电气化工程有限公司' => 'P0297',
                '中铁武汉电气化局集团第一工程有限公司' => 'P0298',
                '中铁武汉电气化局集团有限公司' => 'P0300',
                '中移建设有限公司' => 'P0301',
                '珠海朗电气有限公司' => 'P0302',
                '株洲市亿辉贸易有限公司' => 'P0303',
                '湖南长铁工业开发有限公司' => 'P0304',
                '大连嘉诺机械制造有限公司' => 'P0305',
                '天津宝力电源有限公司' => 'P0306',
                '广东广特电气股份有限公司' => 'P0307',
                '沈阳希尔科技发展有限公司' => 'P0308',
                '太原电务器材厂' => 'P0341',
                '沈阳信号厂' => 'P0342',
                '西安思源科创轨道交通技术开发有限公司' => 'P0343',
                '上海雅珥电气有限公司' => 'P0344',
                '北京联讯伟业科技发展有限公司' => 'P0345',
                '广州电务器材厂' => 'P0346',
                '江阴信达铁路器材有限公司' => 'P0347',
                '合肥中铁电务有限责任公司' => 'P0348',
                '青岛酉信轨道装备有限公司' => 'P0349',
                '山东圣阳电源股份有限公司' => 'P0350',
                '航天柏克（广东）科技有限公司' => 'P0351',
                '杭州创联电子技术有限公司' => 'P0352',
                '普联技术有限公司（TP-LINK）' => 'P0353',
                '友讯电子设备（上海）有限公司（D-LINK）' => 'P0354',
                '迈普通信技术股份有限公司' => 'P0355',
                '光猫OBCC' => 'P0356',
                '瑞斯康达科技发展股份有限公司' => 'P0357',
                'LIKnet' => 'P0358',
                '郑州锐康科技有限公司（RUCOM）' => 'P0359',
                '中科智感科技（湖南）有限公司' => 'P0360',
                '海能达通信股份有限公司' => 'P0361',
                '福建省福清市元洪路上郑捷联电子有限公司' => 'P0362',
                '飞生（上海）电子科技有限公司' => 'P0363',
                '天津三星电子有限公司' => 'P0364',
                '浙江海高思通信科技有限公司' => 'P0365',
                '海峡集团有限责任公司' => 'P0366',
                '双飞燕' => 'P0367',
                '东平联祥' => 'P0368',
                '天迪' => 'P0369',
                '新华三技术有限公司（H3C）' => 'P0370',
                '台湾锋厚科技有限公司（Rextron瑞创）' => 'P0371',
                '兄弟(中国)商业有限公司（brother）' => 'P0372',
                '山泽基业科技有限公司' => 'P0373',
                '重庆飙雷数贸网络科技有限公司' => 'P0374',
                '宝家丽智能科技有限公司' => 'P0375',
                '长虹塑料有限公司' => 'P0376',
                '得力集团有限公司' => 'P0377',
                '泰科电子公司' => 'P0378',
                '金士顿科技（Kingston）' => 'P0379',
                '恒飞电缆股份有限公司' => 'P0380',
                '深圳市比控技术有限公司' => 'P0381',
                '深圳市漫步者科技股份有限公司(edifier)' => 'P0382',
                '广博集团股份有限公司' => 'P0383',
                '佳能（中国）有限公司（Canon）' => 'P0384',
                '安普集团股份有限公司' => 'P0385',
                'scva' => 'P0386',
                '宁波沃尔森光电科技有限公司' => 'P0387',
                '株式会社 东芝 （TOSHIBA CORPORATION）' => 'P0388',
                '克列茨国际贸易（上海）有限公司' => 'P0389',
                '西安安陆信铁路技术有限公司' => 'P0390',
                '西安自动化仪表厂' => 'P0391',
                '中国红旗仪表有限公司' => 'P0392',
                '常熟市压力表厂' => 'P0393',
                '上海自动化仪表有限公司' => 'P0394',
                '深圳市驿生胜利科技有限公司' => 'P0395',
                '成都光大灵曦科技发展有限公司' => 'P0396',
                '天津市德力电子仪器有限公司' => 'P0397',
                '北京市瑞泽胜为科技有限公司' => 'P0398',
                '上海西利光电仪表公司' => 'P0399',
                '杭州东顺仪器仪表公司' => 'P0400',
                '深圳瑞研通讯设备有限公司' => 'P0401',
                '天津玺联腾科技发展有限公司' => 'P0402',
                '英国雷迪公司' => 'P0403',
                '上海康海仪器有限公司' => 'P0404',
                '苏州亿升五金工具有限公司' => 'P0405',
                '安徽世福仪器有限公司' => 'P0406',
                '武汉市康达电气有限公司' => 'P0407',
                '武汉铁路昌信经济开发公司' => 'P0408',
                '上海新新电子仪器厂' => 'P0409',
                '宝鸡仪表有限公司' => 'P0410',
                '三星（中国）投资有限公司（SAMSUNG）' => 'P0411',
                '南京祥瑞德电器科技有限公司' => 'P0412',
                '通号研究设计院' => 'P0413',
                '北京安特视讯通信技术有限公司（antelv）' => 'P0414',
                '日电(中国)有限公司 NEC (China) Co., Ltd.' => 'P0415',
                '研祥智能科技股份有限公司（IPC-810B等等）' => 'P0416',
                'Linkne' => 'P0417',
                'RAD' => 'P0418',
                '北京金泰联创科技发展有限公司（NETLINK）' => 'P0419',
                '广州北羊信息技术有限公司' => 'P0420',
                '铁大厂家' => 'P0421',
                '文登威力工具公司' => 'P0422',
                'ORing' => 'P0423',
                '台湾四零四科技' => 'P0424',
                '胜为' => 'P0425',
                '爱国者' => 'P0426',
                '河南赛伦交通科技有限公司' => 'P0427',
                '沈阳铁路信号公司' => 'P0428',
                '南昌路通高新技术有限责任公司' => 'P0309',
                '杭州华三通信技术有限公司' => 'P0310',
                '北京宏正腾达科技有限公司（ATEN）' => 'P0311',
                '瑞陆信息科技（上海）有限公司' => 'P0312',
                '思科系统公司（CISCO）' => 'P0313',
                '罗技（中国）科技有限公司' => 'P0314',
                '主向位科技股份有限公司（CTC Union Technologies Co.，Ltd）' => 'P0315',
                '武汉艾德蒙科技股份有限公司（AOC）' => 'P0316',
                '联想集团' => 'P0317',
                '上海邦诚电信技术股份有限公司' => 'P0318',
                '成都安维信科技有限公司' => 'P0319',
                '明纬（广州）电子有限公司' => 'P0320',
                '荷兰皇家飞利浦电子公司（Philips）' => 'P0321',
                '宁波世际波斯工具' => 'P0322',
                '广州工具厂' => 'P0323',
                '城都钟表厂' => 'P0324',
                '西安安路信铁路技术有限公司' => 'P0325',
                '深圳市沃仕达科技有限公司' => 'P0326',
                '上海第六电表厂' => 'P0327',
                '博世（BOSCH）' => 'P0328',
                '西安凯信铁路器材有限公司' => 'P0329',
                '武汉铁科' => 'P0330',
                '郑州北信电子产品有限公司' => 'P0331',
                '深圳华谊仪表科技有限公司' => 'P0332',
                '上海嵘顺实业有限公司' => 'P0333',
                '优利德科技（中国）股份有限公司' => 'P0334',
                '福禄克电子仪器仪表公司（FLUKE)' => 'P0335',
                '上海正阳仪表厂' => 'P0336',
                '西安胜利仪器' => 'P0337',
                '台湾泰仕TES电子工业股份有限公司' => 'P0338',
                '上海第四电表厂' => 'P0339',
                '摩托罗拉公司（motorla）' => 'P0340',
            ];

            foreach ($insert_factories as $name => $unique_code) {
                DB::table("factories")->insert(['name' => $name, 'unique_code' => $unique_code]);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollback();
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    /**
     * 上道位置前两位改为01和02
     */
    final public function installPosition01_02()
    {
        try {
            InstallPosition::with([
                'WithInstallTier',
                'WithInstallTier.WithInstallShelf',
            ])
                ->whereHas('WithInstallTier.WithInstallShelf', function ($WithInstallShelf) {
                    $WithInstallShelf->where('name', 'like', 'Z%');
                })
                ->whereIn('name', ['01', '02',])
                ->get()
                ->each(function ($install_position) {
                    if (preg_match('/^Z\d{1,2}/i', $install_position->WithInstallTier->WithInstallShelf->name)) {
                        $install_position->volume=2;
                        $install_position->saveOrFail();

                        $this->comment("{$install_position->real_name}");
                    }
                });

            // InstallShelf::with([
            //     'WithInstallTiers',
            //     'WithInstallTiers.WithInstallPositions',
            //     'WithInstallPlatoon',
            //     'WithInstallPlatoon.WithInstallRoom',
            //     'WithInstallPlatoon.WithInstallRoom.WithStation',
            // ])
            //     ->where('name', 'like', 'Z%')
            //     ->whereHas('WithInstallTiers')
            //     ->whereHas('WithInstallTiers.WithInstallPositions')
            //     ->get()
            //     ->each(function ($install_shelf) {
            //         $install_shelf_name = $install_shelf->name;
            //         if (preg_match('/^Z\d{1,2}/i', $install_shelf_name)) {
            //             $this->comment("{$install_shelf->WithInstallPlatoon->WithInstallRoom->WithStation->name} {$install_shelf->WithInstallPlatoon->WithInstallRoom->type->text} {$install_shelf->WithInstallPlatoon->name}排 {$install_shelf_name}");
            //
            //             $install_shelf->WithInstallTiers->each(function ($install_tier) {
            //                 $install_tier->WithInstallPositions->each(function ($install_position) {
            //                     $old_install_position_name = $install_position->name;
            //                     if ($install_position->name == '1') {
            //                         $install_position->fill(['name' => '01', 'volume' => 2]);
            //                         $this->comment("{$old_install_position_name} > {$install_position->name}");
            //                         $install_position->saveOrFail();
            //                     } else if ($install_position->name == '2') {
            //                         $install_position->fill(['name' => '02', 'volume' => 2]);
            //                         $this->comment("{$old_install_position_name} > {$install_position->name}");
            //                         $install_position->saveOrFail();
            //                     } else {
            //                         $install_position->fill(['name' => strval(intval($install_position->name) - 2)]);
            //                         $this->comment("{$old_install_position_name} > {$install_position->name}");
            //                         $install_position->saveOrFail();
            //                     }
            //                 });
            //             });
            //         }
            //     });

            $this->info('finish');
        } catch (Throwable $e) {
            $msg = "{$e->getMessage()} {$e->getLine()} {$e->getFile()}";
            $this->error($msg);
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!method_exists($this, $this->argument('function_name'))) {
            $this->error("错误，方法：{$this->argument('function_name')} 不存在。");
            return 0;
        }

        $this->{$this->argument('function_name')}();
        return 0;
    }
}
