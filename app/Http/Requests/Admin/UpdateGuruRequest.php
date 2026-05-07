<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuruRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status'             => $this->status ? strtolower(trim($this->status)) : null,
            'status_kepegawaian' => $this->status_kepegawaian ? strtolower(trim($this->status_kepegawaian)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            // ❌ Field permanen: tidak boleh diubah
            'nip'            => ['prohibited'],
            'nuptk'          => ['prohibited'],
            'nama'           => ['prohibited'],
            'jenis_kelamin'  => ['prohibited'],
            'tempat_lahir'   => ['prohibited'],
            'tanggal_lahir'  => ['prohibited'],

            // ✅ Boleh diubah
            'status_kepegawaian' => ['required', Rule::in(['pns','pppk','honorer'])],
            'no_hp'              => ['nullable','string','max:30'],
            'email'              => ['nullable','email','max:150'],
            'status'             => ['required', Rule::in(['aktif','nonaktif'])],
            'profil'               => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
        ];
    }

    protected function passedValidation(): void
    {
        $map = [
            'pns'     => 'PNS',
            'pppk'    => 'PPPK',
            'honorer' => 'Honorer',
        ];

        $this->merge([
            'status'             => $this->status ? strtolower($this->status) : null,
            'status_kepegawaian' => $this->status_kepegawaian ? ($map[$this->status_kepegawaian] ?? null) : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'prohibited' => 'Field :attribute tidak boleh diubah.',
        ];
    }

    public function attributes(): array
    {
        return [
            'no_hp'               => 'No HP',
            'status_kepegawaian'  => 'status kepegawaian',
            'profil'              => 'profil',
        ];
    }
}