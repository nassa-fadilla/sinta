<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuruRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    /**
     * Samakan bentuk input sebelum divalidasi
     */
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
            'profil'             => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'nip'                => ['nullable','string','max:30','unique:guru,nip'],
            'nuptk'              => ['nullable','string','max:30','unique:guru,nuptk'],
            'nama'               => ['required','string','max:150'],
            'jenis_kelamin'      => ['required', Rule::in(['L','P'])],
            'tempat_lahir'       => ['nullable','string','max:100'],
            'tanggal_lahir'      => ['nullable','date'],
            // validasi case-insensitive lewat lower-case
            'status_kepegawaian' => ['nullable', Rule::in(['pns','pppk','honorer'])],
            'no_hp'              => ['nullable','string','max:30'],
            'email'              => ['nullable','email','max:150'],
            'status'             => ['required', Rule::in(['aktif','nonaktif'])],
        ];
    }

    /**
     * Setelah valid, kembalikan ke bentuk kanonik untuk disimpan
     */
    protected function passedValidation(): void
    {
        // mapping status_kepegawaian ke bentuk baku
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

    public function attributes(): array
    {
        return [
            'nuptk'               => 'NUPTK',
            'no_hp'               => 'No HP',
            'status_kepegawaian'  => 'status kepegawaian',
            'tanggal_lahir'       => 'tanggal lahir',
            'tempat_lahir'        => 'tempat lahir',
            'profil'              => 'profil',
        ];
    }
}