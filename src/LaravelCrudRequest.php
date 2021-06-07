<?php


namespace XcentricItFoundation\LaravelCrudController;

use Illuminate\Foundation\Http\FormRequest;

class LaravelCrudRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function validate(): array
    {
        return parent::validate($this->rules());
    }
}
