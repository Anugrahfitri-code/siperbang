<?php

namespace App\Http\Requests\SiteBranding;

use Illuminate\Foundation\Http\FormRequest;

class PublishSiteBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Superadmin';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'effective_from' => ['nullable', 'date'],
        ];
    }
}
