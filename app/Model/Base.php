<?php

namespace App\Model;

use App\Facades\TextFacade;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Class Base
 * @package App\Models
 */
class Base extends Model
{

    protected $__filter_excepts = [];
    protected $__default_withs = [];

    /**
     * @param array $filter_excepts
     * @return Builder
     */
    final private function getBuilder(array $filter_excepts = []): Builder
    {
        $this->__filter_excepts = array_merge(['id', 'nonce', 'files', 'file', 'limit', 'page', 'size', 'ordering', 'timestamp', '_',], $filter_excepts);
        return $this->filter();  # 筛选
    }

    /**
     * 根据规则自动生成筛选条件
     * @return Builder
     */
    final private function filter(): Builder
    {
        $builder = $this->with($this->__default_withs);
        $params = array_filter(request()->except($this->__filter_excepts), function ($val) {
            return !empty($val);
        });
        if ($params) {
            foreach ($params as $field_name => $condition) {
                $builder->when($field_name, function ($query) use ($field_name, $condition) {
                    return self::condition($query, $field_name, $condition);
                });
            }
        }
        $builder->when(request('limit'), function ($query) {
            return $query->limit(request('limit'));
        });
        return $builder;
    }

    /**
     * 生成条件
     * @param $query
     * @param $field_name
     * @param $condition
     * @return mixed
     */
    final private function condition($query, $field_name, $condition)
    {
        if (is_array($condition)) {
            switch (strtolower($condition['operator'])) {
                case 'in':
                    return $query->whereIn($field_name, $condition['value']);
                case 'or':
                    return $query->orWhere($field_name, $condition['value']);
                case 'between':
                    return $query->whereBetween($field_name, $condition['value']);
                case 'like_l':
                    return $query->where($field_name, 'like', "%{$condition['value']}");
                case 'like_r':
                    return $query->where($field_name, 'like', "{$condition['value']}%");
                case 'like_b':
                    return $query->where($field_name, 'like', "%{$condition['value']}%");
                default:
                    return $query->where($field_name, $condition['operator'], $condition['value']);
            }
        } else {
            return $query->where($field_name, $condition);
        }
    }

    /**
     * 排序
     * @param Builder $builder
     * @return Builder
     */
    final private function ordering(Builder $builder): Builder
    {
        if (request('ordering')) {
            $builder->orderByRaw(request('ordering'));
        }
        return $builder;
    }

    /**
     * 创建新数据
     * @param array $attributes
     * @param string $limit
     * @param string $identity_code_field_name
     * @return Builder|Model
     */
    final public function CreateOne(array $attributes, string $limit = '.', string $identity_code_field_name = 'identity_code')
    {
        $identity_code = $this->GenerateIdentityCode($limit, $identity_code_field_name);
        return self::with([])->create(array_merge($attributes, [$identity_code_field_name => $identity_code,]));
    }

    /**
     * @param string $identity_code
     * @param array $attributes
     * @return Builder|Model
     * @throws Throwable
     */
    final public function UpdateOneByIdentityCode(string $identity_code, array $attributes = [])
    {
        $instance = $this->ReadOneByIdentityCode($identity_code)->firstOrFail();
        $instance->fill($attributes)->saveOrFail();
        return $instance;
    }

    /**
     * @param string $identity_code
     * @return Builder
     */
    final public function ReadOneByIdentityCode(string $identity_code): Builder
    {
        return self::with($this->__default_withs)->where('identity_code', $identity_code);
    }

    /**
     * @param int $id
     * @return Builder
     */
    final public function ReadOneById(int $id): Builder
    {
        return self::with($this->__default_withs)->where("id", $id);
    }

    /**
     * @param string $unique_code
     * @return Builder
     */
    final public function ReadOneByUniqueCode(string $unique_code):Builder
    {
        return self::with($this->__default_withs)->where("unique_code",$unique_code);
    }

    /**
     * @param array $filter_excepts
     * @return Builder
     */
    final public function ReadMany(array $filter_excepts = []): Builder
    {
        $builder = $this->getBuilder($filter_excepts);
        return $this->ordering($builder);  # 排序
    }

    /**
     * @param string $identity_code
     * @return bool|mixed|null
     * @throws Exception
     */
    final public function DeleteOneByIdentityCode(string $identity_code)
    {
        return $this->ReadOneByIdentityCode($identity_code)->delete();
    }

    /**
     * @param array $filter_excepts
     * @return mixed
     */
    final public function DeleteMany(array $filter_excepts = [])
    {
        return $this->getBuilder($filter_excepts)->delete();
    }

    /**
     * @param string $identity_code
     * @return bool|mixed|null
     */
    final public function ForceDeleteOne(string $identity_code)
    {
        return $this->ReadOneByIdentityCode($identity_code)->forceDelete();
    }

    /**
     * @param array $filter_excepts
     * @return mixed
     */
    final public function ForceDeleteMany(array $filter_excepts = [])
    {
        return $this->getBuilder($filter_excepts)->forceDelete();
    }

    /**
     * @param string $limit
     * @param string $identity_code_field_name
     * @return int
     */
    final protected function GetLastIdentityCodeNumber(string $limit = '.', string $identity_code_field_name = 'identity_code'): int
    {
        $last = self::with([])
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay(),])
            ->orderByDesc('id')
            ->first();

        return @$last[$identity_code_field_name] ? intval(TextFacade::from32(collect(explode($limit, $last[$identity_code_field_name]))->last())) : 0;
    }

    /**
     * @param string $limit
     * @param string $identity_code_field_name
     * @return string
     */
    final protected function GenerateNextIdentityCodeNumber(string $limit = '.', string $identity_code_field_name = 'identity_code'): string
    {
        return str_pad(TextFacade::to32($this->GetLastIdentityCodeNumber($limit, $identity_code_field_name) + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 生成新编号
     * @param string $limit
     * @param string $identity_code_field_name
     * @return string
     */
    final public function GenerateIdentityCode(string $limit = '.', string $identity_code_field_name = 'identity_code'): string
    {
        // $str_rand = md5(self::class) . Str::random(6) . Str::uuid();
        // $uuid = md5(uniqid($str_rand));
        // return "$uuid.{$this->generateNextIdentityCodeNumber($limit, $identity_code_field_name)}";
        return Str::uuid();
    }
}
