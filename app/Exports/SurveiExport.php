<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SurveiExport
{
    protected $survei;
    protected $respon;

    public function __construct($survei, $respon)
    {
        $this->survei = $survei;
        $this->respon = $respon;
    }

    /**
     * Buat file Excel hasil survei dalam format rapi dan deskriptif
     */
    public function generateFile(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul survei
        $sheet->setCellValue('A1', 'Hasil Survei: ' . $this->survei->judul);
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header tabel
        $sheet->fromArray(['No', 'Nama Orang Tua', 'Tanggal', 'Jawaban'], null, 'A3');
        $sheet->getStyle('A3:D3')->getFont()->setBold(true);

        $row = 4;
        $no = 1;

        // Ambil pertanyaan untuk menampilkan label deskriptif
        $pertanyaanList = $this->survei->pertanyaan()->orderBy('urutan')->pluck('pertanyaan', 'id')->toArray();

        foreach ($this->respon as $r) {
            $ortuName = $r->ortu->name ?? '-';
            $tgl = $r->created_at->format('d/m/Y H:i');

            // Decode jawaban
            $decoded = is_array($r->jawaban)
                ? $r->jawaban
                : json_decode($r->jawaban, true);

            if (!is_array($decoded)) {
                $decoded = [];
            }

            // Format deskriptif
            $formatted = [];
            foreach ($decoded as $key => $val) {
                $label = $pertanyaanList[$key] ?? "Pertanyaan {$key}";
                if (is_array($val)) {
                    $val = implode(', ', $val);
                }
                $formatted[] = "{$label}: {$val}";
            }

            // Gabungkan jawaban dalam satu sel, pisah baris dengan newline
            $jawaban = implode("\n", $formatted);

            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $ortuName);
            $sheet->setCellValue("C{$row}", $tgl);
            $sheet->setCellValue("D{$row}", $jawaban);
            $sheet->getStyle("D{$row}")->getAlignment()->setWrapText(true);

            $row++;
        }

        // Lebar kolom otomatis
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Simpan ke storage
        $filename = 'hasil-survei-' . \Str::slug($this->survei->judul) . '.xlsx';
        $filePath = storage_path('app/public/' . $filename);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }
}
