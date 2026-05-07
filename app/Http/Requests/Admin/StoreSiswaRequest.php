<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            // Data permanen (wajib saat create)
            'nisn'           => ['required','string','max:30','unique:siswa,nisn'],
            'nis'            => ['required','string','max:30','unique:siswa,nis'],
            'nama'           => ['required','string','max:150'],
            'jenis_kelamin'  => ['required', Rule::in(['L','P'])],
            'tempat_lahir'   => ['required','string','max:100'],
            'tanggal_lahir'  => ['required','date'],
            'agama'          => ['nullable', Rule::in(['Islam','Katolik','Protestan','Hindu','Buddha','Konghucu'])],

            // Alamat & kontak
            'alamat'         => ['nullable','string'],
            'no_hp'          => ['nullable','string','max:30'],
            'email'          => ['nullable','email','max:150'],

            // Upload foto (opsional)
            'foto'           => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],

            // Info lain
            'jalur_penerimaan' => ['nullable', Rule::in(['Afirmasi','Mutasi','Prestasi','Domisili Khusus','Domisili Reguler'])],
            'kebutuhan_khusus' => ['nullable', Rule::in(['Iya','Tidak'])],
            'tahun_masuk'      => ['nullable','digits:4'],
            'status'           => ['required', Rule::in(['aktif','nonaktif'])],

            // Orang tua (tambahan)
            'ayah_nik'         => ['nullable','digits_between:8,20'],
            'ayah_pendidikan'  => ['nullable', Rule::in(['SD','SMP','SMA/MA/SMK','D3','S1','S2','S3','Lainnya'])],
            'ibu_nik'          => ['nullable','digits_between:8,20'],
            'ibu_pendidikan'   => ['nullable', Rule::in(['SD','SMP','SMA/MA/SMK','D3','S1','S2','S3','Lainnya'])],

            // Yang sudah ada sebelumnya (opsional)
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

            // relasi
            'kelas_id'         => ['nullable','integer'],
        ];
    }
}