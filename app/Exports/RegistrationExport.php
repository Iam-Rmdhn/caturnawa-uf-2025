<?php

namespace App\Exports;

use App\Models\Registration;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RegistrationExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $registrations;

    public function __construct($registrations)
    {
        $this->registrations = $registrations;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->registrations;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No. Registrasi',
            'Nama Peserta',
            'Email',
            'Kompetisi',
            'Kategori',
            'Tim/Individu',
            'Institusi',
            'Telepon',
            'Status Registrasi',
            'Status Pembayaran',
            'Jumlah Bayar',
            'Tanggal Daftar',
            'Tanggal Konfirmasi',
        ];
    }

    /**
     * @param mixed $registration
     * @return array
     */
    public function map($registration): array
    {
        return [
            $registration->registration_number,
            $registration->user->name,
            $registration->user->email,
            $registration->competition->name,
            ucfirst($registration->competition->category),
            $registration->team_name ?: 'Individu',
            $registration->institution,
            $registration->phone,
            ucfirst($registration->status),
            $registration->payment ? ucfirst($registration->payment->status) : 'Belum Bayar',
            'Rp ' . number_format($registration->amount, 0, ',', '.'),
            $registration->created_at->format('d/m/Y H:i'),
            $registration->confirmed_at ? $registration->confirmed_at->format('d/m/Y H:i') : '-',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
            
            // Style the header row
            'A1:M1' => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
