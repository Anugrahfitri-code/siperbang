<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifikasiKodePersediaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'Petugas Persediaan';
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array',
            'items.*.detail_id' => 'required|exists:stok_upload_details,id',
            'items.*.action' => 'required|in:Setuju,Perbaiki,Tolak',
            'items.*.kode_persediaan' => 'required_if:items.*.action,Perbaiki|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Data verifikasi wajib dikirim.',
            'items.*.action.required' => 'Setiap item wajib memiliki keputusan (Setuju, Perbaiki, atau Tolak).',
            'items.*.kode_persediaan.required_if' => 'Kode persediaan wajib diisi jika Anda memilih keputusan Perbaiki.',
        ];
    }
}
