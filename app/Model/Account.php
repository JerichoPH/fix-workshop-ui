<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Model\Account
 *
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property string $account 登录用账号
 * @property string $password 登录用密码
 * @property string|null $email 邮箱
 * @property string|null $phone 手机号
 * @property int $status_id 状态
 * @property string|null $open_id 开放编号
 * @property int|null $organization_id 机构编号
 * @property string|null $email_code 邮件验证码内容
 * @property string|null $email_code_exp 邮件验证码到期时间
 * @property string|null $wechat_official_open_id 微信公众平台开放编号
 * @property string|null $nickname 昵称
 * @property string|null $avatar 用户头像
 * @property int $supervision 验收权限
 * @property string $identity_code 工号
 * @property string|null $workshop_code
 * @property string|null $workshop_name
 * @property string|null $station_name
 * @property int|null $work_area 所属工区：0未分配、1转辙机、2继电器、3综合工区
 * @property int $read_scope 数据读取范围：1、个人 2、工区 3、车间
 * @property int $write_scope 数据操作范围：1、个人 2、工区 3、车间
 * @property string $temp_task_position 职位，默认：Crew组员
 * @property string $access_key 用户令牌
 * @property string $secret_key 用户密钥
 * @property string|null $workshop_unique_code 所属车间编码
 * @property string|null $station_unique_code 所属车站编码
 * @property string|null $work_area_unique_code 所属工区编码
 * @property mixed $rank 职级
 * 无 None
 * 科长 SectionChief
 * 主管工程师 EngineerMaster
 * 工程师 Engineer
 * 现场车间主任 SceneWorkshopPrincipal
 * 现场车间副主任 SceneWorkshopDeputyPrincipal
 * 现场车间员工 SceneWorkshopCrew
 * 现场工区工长 SceneWorkAreaPrincipal
 * 现场工区副工长 SceneWorkAreaDeputyPrincipal
 * 现场工区员工 SceneWorkAreaCrew
 * 检修车间主任 WorkshopPrincipal
 * 检修车间副主任 WorkshopDeputyPrincipal
 * 检修车间工程师 WorkshopEngineer
 * 检修工区工长 WorkshopWorkAreaPrincipal
 * 检修工区副工长 WorkshopWorkAreaDeputyPrincipal
 * 检修车间职工 WorkshopCrew
 * 支部书记 BranchSecretary
 * 干事 SecretaryInChargeOfSth
 * 现场车间干部 SceneWorkshopCadre
 * 现场车间工长 SceneWorkshopWorkAreaPrincipal
 * 现场车间副工长 SceneWorkshopWorkAreaDeputyPrincipal
 * 班长 Monitor
 * 见习生 Trainee
 * 电子设备主管工程师 ElectronicEngineerMaster
 * 电子设备工程师 ElectronicEngineer
 * 主管电子设备副科长 ElectronicSectionDeputyChiefMaster
 * 主任 Chairman
 * 主任干事 ChairmanSecretaryInChargeOfSth
 * 调度员 Dispatcher
 * 主任调度员 ChairmanDispatcher
 * @property-read Maintain $Station
 * @property-read WorkArea $WorkAreaByUniqueCode
 * @property-read Maintain $Workshop
 * @property-read Organization|null $organization
 * @property-read Collection|RbacRole[] $roles
 * @property-read int|null $roles_count
 * @property-read Status $status
 * @property int $page_size
 */
class Account extends Model
{
    use SoftDeletes;

    // 工区
    public static $WORK_AREAS = [
        0 => '无',
        1 => '转辙机工区',
        2 => '继电器工区',
        3 => '综合工区',
        4 => '电源屏工区',
    ];

    // 工区类型
    public static $WORK_AREA_TYPES = [
        'switchPoint' => 1,
        'pointSwitch' => 1,
        'reply' => 2,
        'synthesize' => 3,
        'powerSupplyPanel' => 4,
    ];

    // 临时生产任务职级
    public static $TEMP_TASK_POSITIONS = [
        'ParagraphPrincipal' => '电务段负责人',
        'ParagraphCrew' => '电务段成员',
        'WorkshopPrincipal' => '车间主任',
        'WorkshopEngineer' => '车间工程师',
        'WorkshopWorkArea' => '工区工长',
        'WorkshopCrew' => '工区组员',
        'Crew' => '工区组员',
    ];

    // 职级
    public static $RANKS = [
        'None' => '无',
        'ParagraphChief' => '段长',
        'ParagraphDeputy' => '副段长',
        'SectionChief' => '科长',
        'SectionDeputyChief' => '副科长',
        'EngineerMaster' => '主管工程师',
        'Engineer' => '工程师',
        'SceneWorkshopPrincipal' => '现场车间主任',
        'SceneWorkshopDeputyPrincipal' => '现场车间副主任',
        'SceneWorkshopCrew' => '现场车间职工',
        'SceneWorkAreaPrincipal' => '现场工区工长',
        'SceneWorkAreaDeputyPrincipal' => '现场工区副工长',
        'SceneWorkAreaCrew' => '现场工区职工',
        'WorkshopPrincipal' => '检修车间主任',
        'WorkshopDeputyPrincipal' => '检修车间副主任',
        'WorkshopEngineer' => '检修车间工程师',
        'WorkshopWorkAreaPrincipal' => '检修工区工长',
        'WorkshopWorkAreaDeputyPrincipal' => '检修工区副工长',
        'WorkshopCrew' => '检修车间职工',
        'BranchSecretary' => '支部书记',
        'SecretaryInChargeOfSth' => '干事',
        'SceneWorkshopCadre' => '现场车间干部',
        'SceneWorkshopWorkAreaPrincipal' => '现场车间工长',
        'SceneWorkshopWorkAreaDeputyPrincipal' => '现场车间副工长',
        'Monitor' => '班长',
        'Trainee' => '见习生',
        'ElectronicEngineerMaster' => '电子设备主管工程师',
        'ElectronicEngineer' => '电子设备工程师',
        'ElectronicSectionDeputyChiefMaster' => '主管电子设备副科长',
        'Chairman' => '主任',
        'ChairmanSecretaryInChargeOfSth' => '主任干事',
        'Dispatcher' => '调度员',
        'ChairmanDispatcher' => '主任调度员',
    ];


    protected $guarded = [];

    final public function getWorkAreaAttribute($value)
    {
        return @self::$WORK_AREAS[$value] ?: '无';
    }

    final public function getRealWorkArea()
    {
        return array_flip(self::$WORK_AREAS)[$this->attributes['work_area']];
    }

    final public function getTempTaskPositionAttribute($value)
    {
        return @self::$TEMP_TASK_POSITIONS[$value] ?: '无';
    }

    final public function getRealTempTaskPosition()
    {
        return array_flip(self::$TEMP_TASK_POSITIONS)[$this->attributes['temp_task_position']];
    }

    final public function status()
    {
        return $this->belongsTo(Status::class, 'status_id', 'id');
    }

    final public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'id');
    }

    final public function roles()
    {
        return $this->belongsToMany(
            RbacRole::class,
            'pivot_role_accounts',
            'account_id',
            'rbac_role_id'
        );
    }

    /**
     * 所属工区
     * @return HasOne
     */
    final public function WorkAreaByUniqueCode(): HasOne
    {
        return $this->hasOne(WorkArea::class, 'unique_code', 'work_area_unique_code');
    }

    /**
     * 获取职级
     * @param $value
     * @return mixed
     */
    final public function getRankAttribute($value)
    {
        return (object)['code' => $value, 'name' => self::$RANKS[$value] ?? '无'];
    }

    /**
     * 现场车间
     * @return HasOne
     */
    final public function Workshop(): HasOne
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'workshop_unique_code');
    }

    /**
     * 车站
     */
    final public function Station()
    {
        return $this->hasOne(Maintain::class, 'unique_code', 'station_unique_code');
    }
}
