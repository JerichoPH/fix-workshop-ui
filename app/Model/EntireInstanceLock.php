<?php

namespace App\Model;

use App\Exceptions\EntireInstanceLockException;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * App\Model\EntireInstanceLock
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string $entire_instance_identity_code 设备唯一编号
 * @property string $lock_name 锁名称
 * @property string $lock_type 锁类型：
 * ONLY享锁
 * EXCEPT除锁
 * @property string $remark 备注
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLock query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLock whereEntireInstanceIdentityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLock whereLockName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLock whereLockType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLock whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Model\EntireInstanceLock whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EntireInstanceLock extends Model
{
    public static $LOCK_TYPES = [
        'ONLY' => '享锁',
        'EXCEPT' => '排锁'
    ];

    protected $guarded = [];

    /**
     * 设置享锁
     * @param string $identity_code
     * @param array $lock_names
     * @param string $remark
     * @param \Closure|null $closure
     * @return EntireInstanceLock|bool|\Illuminate\Database\Eloquent\Builder|Model
     * @throws EntireInstanceLockException
     */
    final public static function setOnlyLock(
        string $identity_code,
        array $lock_names,
        string $remark = '',
        \Closure $closure = null
    )
    {
        if (empty($identity_code)) return false;
        sort($lock_names);
        $lock_name = implode(',', $lock_names);

        $lock = self::with([])
            ->where('entire_instance_identity_code', $identity_code)
            ->where('lock_type', 'ONLY')
            // ->where('lock_name',$lock_name)
            ->first();

        if ($lock) {
            throw new EntireInstanceLockException(empty($lock->remark) ? '设备重复占用' : $lock->remark, 403);
        } else {
            # 设备加锁
            DB::beginTransaction();
            $ret = EntireInstanceLock::with([])->create([
                'entire_instance_identity_code' => $identity_code,
                'lock_name' => $lock_name,
                'lock_type' => 'ONLY',
                'remark' => $remark,
            ]);
            if ($closure) $closure();
            DB::commit();
            return $ret;
        }
    }

    /**
     * 设置多设备享锁
     * @param array $identity_codes
     * @param array $lock_names
     * @param array $remarks
     * @param \Closure|null $closure
     * @return bool|mixed
     * @throws \Exception
     */
    final public static function setOnlyLocks(array $identity_codes, array $lock_names, array $remarks = [], \Closure $closure = null)
    {
        if (empty($identity_codes)) return false;
        sort($lock_names);
        $lock_name = implode(',', $lock_names);

        $locks = self::with([])
            ->whereIn('entire_instance_identity_code', $identity_codes)
            ->get();

        if ($locks->isEmpty()) {
            # 所有设备没有锁，执行批量加锁
            DB::beginTransaction();
            $insert = [];
            $time = date('Y-m-d H:i:s');
            foreach ($identity_codes as $identity_code) {
                $insert[] = [
                    'created_at' => $time,
                    'updated_at' => $time,
                    'entire_instance_identity_code' => $identity_code,
                    'lock_name' => $lock_name,
                    'lock_type' => 'ONLY',
                    'remark' => $remarks[$identity_code] ?? '',
                ];
            }

            if ($closure) $closure();

            $ret = DB::table('entire_instance_locks')->insert($insert);
            DB::commit();

            return $ret;
        } else {
            $remark = $locks->first()->remark;
            throw new EntireInstanceLockException(empty($remark) ? '部分设备已被占用，不能完成批量加锁' : $remark, 403);
        }
    }

    /**
     * 获取多设备享锁
     * @param array $identity_codes
     * @param array $lock_names
     * @return bool
     */
    final public static function getOnlyLocks(array $identity_codes, array $lock_names, \Closure $closure = null)
    {
        if (empty($identity_codes)) return false;
        sort($lock_names);

        return DB::transaction(function () use ($identity_codes, $lock_names, $closure) {
            $ret = self::with([])->where('lock_type', 'ONLY')
                ->where('lock_name', implode(',', $lock_names))
                ->whereIn('entire_instance_identity_code', $identity_codes)
                ->exists();

            if ($closure) $closure();

            return $ret;
        });
    }

    /**
     * 释放锁
     * @param string $identity_code
     * @param array $lock_names
     * @param Closure|null $closure
     * @return false|mixed
     */
    final public static function freeLock(string $identity_code, array $lock_names, Closure $closure = null)
    {
        if (empty($identity_code)) return false;

        sort($lock_names);
        $lock_name = implode(',', $lock_names);

        return DB::transaction(function () use ($identity_code, $lock_name, $closure) {
            try {
                $lock = self::with([])
                    ->where('entire_instance_identity_code', $identity_code)
                    // ->where('lock_name', $lock_name)
                    ->firstOrFail();

                if ($closure) $closure();

                return $lock ? $lock->delete() : true;
            } catch (ModelNotFoundException $e) {
                return true;
            } catch (\Exception $e) {
                throw $e;
            }
        });
    }

    /**
     * 释放多锁
     * @param array $identity_codes
     * @param array $lock_names
     * @param \Closure|null $closure
     * @return bool
     */
    final public static function freeLocks(array $identity_codes, array $lock_names, \Closure $closure = null)
    {
        if (empty($identity_codes)) return false;

        sort($lock_names);
        $lock_name = implode(',', $lock_names);

        return DB::transaction(function () use ($identity_codes, $lock_name, $closure) {
            $ret = DB::table('entire_instance_locks')
                ->whereIn('entire_instance_identity_code', $identity_codes)
                // ->where('lock_name', $lock_name)
                ->delete();

            if ($closure) $closure();

            return $ret;
        });
    }

    final public function getLockTypeAttribute($value)
    {
        return self::$LOCK_TYPES[$value];
    }
}
