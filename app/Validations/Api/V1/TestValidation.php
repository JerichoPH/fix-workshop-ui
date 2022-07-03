<?php

namespace App\Validations\Api\V1;

use App\Validations\Validation;
use Illuminate\Http\Request;

class TestValidation extends Validation
{
    public $rules = [];
    public $messages = [];
    public $attributes = [];

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->messages = array_merge(parent::$BASE_MESSAGES, $this->messages);
    }
}