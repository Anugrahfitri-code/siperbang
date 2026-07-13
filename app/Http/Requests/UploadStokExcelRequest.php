<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadStokExcelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'Petugas Persediaan';
    }

    public function rules(): array
    {
        return [
            'file_excel' => 'required|file|mimes:xlsx,xls|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'file_excel.required' => 'File Excel wajib diunggah.',
            'file_excel.file' => 'Unggahan harus berupa file.',
            'file_excel.mimes' => 'Format file harus berupa Excel (.xlsx atau .xls).',
            'file_excel.max' => 'Ukuran file maksimal adalah 10MB.',
        ];
    }
}
