<?php

namespace App\Http\Requests\SiteBranding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveSiteBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Superadmin';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'label' => trim((string) ($this->input('label') ?: 'Identitas '.now()->format('Y-m-d H:i'))),
            'action' => $this->input('action', 'publish'),
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:120'],
            'action' => ['required', 'in:draft,publish'],
            'effective_from' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'app_name' => ['required', 'string', 'max:120'],
            'app_name_colors' => ['nullable', 'string', 'max:5000'],
            'app_subtitle' => ['nullable', 'string', 'max:255'],
            'instansi_name' => ['required', 'string', 'max:120'],
            'instansi_name_colors' => ['nullable', 'string', 'max:5000'],
            'instansi_sub' => ['nullable', 'string', 'max:500'],
            'login_heading' => ['required', 'string', 'max:5000'],
            'login_description' => ['nullable', 'string', 'max:10000'],
            'footer_copyright' => ['required', 'string', 'max:500'],
            'contact_address' => ['nullable', 'string', 'max:500'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:100'],

            'app_logo' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
                'dimensions:max_width=2000,max_height=2000',
            ],
            'instansi_logo' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
                'dimensions:max_width=2000,max_height=2000',
            ],
            'favicon' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:1024',
                'dimensions:max_width=1024,max_height=1024',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ([
                'login_heading' => 'Judul halaman login wajib berisi teks.',
                'login_description' => 'Deskripsi halaman login harus berisi teks atau dikosongkan.',
            ] as $field => $message) {
                $html = (string) $this->input($field, '');

                if ($field === 'login_description' && trim($html) === '') {
                    continue;
                }

                $plainText = trim(html_entity_decode(
                    strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], "\n", $html)),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8',
                ));

                if ($plainText === '') {
                    $validator->errors()->add($field, $message);
                }
            }

            $template = (string) $this->input('footer_copyright', '');
            preg_match_all('/\{[^{}]+\}/u', $template, $matches);
            $unsupportedTokens = array_values(array_diff(
                array_unique($matches[0] ?? []),
                config('site_branding.tokens', []),
            ));

            if ($unsupportedTokens !== []) {
                $validator->errors()->add(
                    'footer_copyright',
                    'Token footer tidak dikenal: '.implode(', ', $unsupportedTokens).'.',
                );
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'app_name.required' => 'Nama aplikasi wajib diisi.',
            'instansi_name.required' => 'Nama singkat instansi wajib diisi.',
            'login_heading.required' => 'Judul halaman login wajib diisi.',
            'footer_copyright.required' => 'Template hak cipta wajib diisi.',
            'app_logo.mimetypes' => 'Logo aplikasi harus berformat PNG, JPG/JPEG, atau WebP.',
            'instansi_logo.mimetypes' => 'Logo instansi harus berformat PNG, JPG/JPEG, atau WebP.',
            'favicon.mimetypes' => 'Favicon harus berformat PNG, JPG/JPEG, atau WebP.',
            'app_logo.max' => 'Ukuran logo aplikasi maksimal 2MB.',
            'instansi_logo.max' => 'Ukuran logo instansi maksimal 2MB.',
            'favicon.max' => 'Ukuran favicon maksimal 1MB.',
            'app_logo.dimensions' => 'Dimensi logo aplikasi maksimal 2000×2000 piksel.',
            'instansi_logo.dimensions' => 'Dimensi logo instansi maksimal 2000×2000 piksel.',
            'favicon.dimensions' => 'Dimensi favicon maksimal 1024×1024 piksel.',
            'contact_email.email' => 'Format email kontak tidak valid.',
        ];
    }
}
