<?php

namespace App\Validations;

use Illuminate\Contracts\Validation\Validator as ValidatorReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class Validation
{
    public static $BASE_MESSAGES = [
        'required' => ':attribute必填',
        'unique' => ':attribute被占用',
        'email' => ':attribute格式不正确',
        'between' => ':attribute不能小于:min或大于:max',
        'boolean' => ':attribute必须是逻辑类型',
        'digits_between' => ':attribute必须是数字，且大于:min小于:max',
        'min' => ':attribute不能小于:min',
        'max' => ':attribute不能大于:max',
        'in' => ':attribute只能填写:values',
        'size' => ':attribute长度不正确',
    ];
    public $rules = [];
    public $messages = [];
    public $attributes = [];
    public $request = null;

    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->messages = array_merge(
            [
                'required' => ':attribute必填',
                'unique' => ':attribute被占用',
                'email' => ':attribute格式不正确',
                'between' => ':attribute不能小于:min或大于:max',
                'boolean' => ':attribute必须是逻辑类型',
                'digits_between' => ':attribute必须是数字，且大于:min小于:max',
                'min' => ':attribute不能小于:min',
                'max' => ':attribute不能大于:max',
                'in' => ':attribute只能填写:values',
                'size' => ':attribute长度不正确',
            ],
            $this->messages
        );
    }

    /**
     * 验证
     * @return ValidatorReturn
     */
    public function check(): ValidatorReturn
    {
        return validator($this->request->all(), $this->rules, $this->messages, $this->attributes);
    }

    /**
     * @return Collection
     */
    final public function validated(): Collection
    {
        $_ = [];
        collect($this->rules)->keys()->each(function ($key) use (&$_) {
            if ($this->request->get($key)) {
                $_[$key] = $this->request->get($key);
            }
        });

        return collect($_);
    }
}
