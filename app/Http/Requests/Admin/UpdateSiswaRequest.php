<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            // Permanen: tidak boleh diubah
            'nisn'           => ['prohibited'],
            'nis'            => ['prohibited'],
            'nama'           => ['prohibited'],
            'jenis_kelamin'  => ['prohibited'],
            'tempat_lahir'   => ['prohibited'],
            'tanggal_lahir'  => ['prohibited'],
            'agama'          => ['prohibited'],

            // Boleh diubah
            'alamat'         => ['nullable','string'],
            'no_hp'          => ['nullable','string','max:30'],
            'email'          => ['nullable','email','max:150'],

            'foto'           => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],

            'jalur_penerimaan' => ['nullable', Rule::in(['Afirmasi','Mutasi','Prestasi','Domisili Khusus','Domisili Reguler'])],
            'kebutuhan_khusus' => ['nullable', Rule::in(['Iya','Tidak'])],
            'tahun_masuk'      => ['nullable','digits:4'],
            'status'           => ['required', Rule::in(['aktif','nonaktif'])],

            // Orang tua (baru)
            'ayah_nik'         => ['nullable','digits_between:8,20'],
            'ayah_pendidikan'  => ['nullable', Rule::in(['SD','SMP','SMA/MA/SMK','D3','S1','S2','S3','Lainnya'])],
            'ibu_nik'          => ['nullable','digits_between:8,20'],
            'ibu_pendidikan'   => ['nullable', Rule::in(['SD','SMP','SMA/MA/SMK','D3','S1','S2','S3','Lainnya'])],

            // Yang sudah ada sebelumnya
            'ayah_nama'        => ['nullable','string','max:150'],
            'ayah_status'      => ['nullable', Rule::in(['hidup','meninggal'])],
            'ayah_pekerjaan'   => ['nullable','string','max:100'],
            'ayah_no_hp'       => ['nullable','string','max:30'],
            'ayah_alamat'      => ['nullable','string'],

            'ibu_nama'         => ['nullable','string','max:150'],
            'ibu_status'       => ['nullable', Rule::in(['hidup','meninggal'])],
            'ibu_pekerjaan'    => ['nullable','string','max:100'],
            'ibu_no_hp'        => ['nullable','string','max:30'],
            'ibu_alamat'       => ['nullable','string'],

            'kelas_id'         => ['nullable','integer'],
        ];
    }
}