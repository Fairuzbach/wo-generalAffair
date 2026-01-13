<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class WorkOrderExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        if ($this->data instanceof \Illuminate\Database\Eloquent\Builder) {
            return $this->data->with(['plantInfo', 'user'])->get();
        }
        $this->data->load(['plantInfo', 'user']);
        return $this->data;
    }

    // Sheet Title
    public function title(): string
    {
        return 'Work Order GA - ' . date('d M Y');
    }

    // =========================================================================
    // 1. HEADER KOLOM
    // =========================================================================
    public function headings(): array
    {
        return [
            'NO',
            'ID TIKET',
            'PEMOHON',
            'DIVISI PELAPOR',
            'LOKASI (PLANT)',
            'DEPARTEMEN TUJUAN',
            'PARAMETER',
            'KATEGORI',
            'DESKRIPSI MASALAH',
            'STATUS TERAKHIR',
            'TANGGAL DIBUAT',
            'TANGGAL TARGET',
            'MULAI PENGERJAAN',
            'SELESAI AKTUAL',
            'DURASI (JAM)',
            'PIC / TEKNISI',
            'CATATAN AKHIR',
        ];
    }

    // =========================================================================
    // 2. MAPPING DATA
    // =========================================================================
    public function map($ticket): array
    {
        static $rowNumber = 0;
        $rowNumber++;

        // A. Handle User (Pelapor)
        $user = $ticket->user;
        $namaPemohon = $user ? $user->name : ($ticket->requester_name ?? '-');
        $divisiPemohon = $user ? ($user->divisi ?? '-') : '-';
        $lokasi = $ticket->plantInfo->plant_name ?? $ticket->plantInfo->name ?? $ticket->plant;

        // B. Format Tanggal
        $tglDibuat = $ticket->created_at ? Carbon::parse($ticket->created_at)->setTimezone('Asia/Jakarta')->locale('id')->isoFormat('DD MMM YYYY HH:mm') : '-';
        $tglTarget = $ticket->target_completion_date ? Carbon::parse($ticket->target_completion_date)->locale('id')->isoFormat('DD MMM YYYY') : '-';
        $tglMulai = $ticket->actual_start_date ? Carbon::parse($ticket->actual_start_date)->setTimezone('Asia/Jakarta')->locale('id')->isoFormat('DD MMM YYYY HH:mm') : '-';
        $tglSelesai = $ticket->actual_completion_date ? Carbon::parse($ticket->actual_completion_date)->setTimezone('Asia/Jakarta')->locale('id')->isoFormat('DD MMM YYYY HH:mm') : '-';

        // C. Hitung Durasi
        $durasi = '-';
        if ($ticket->actual_start_date && $ticket->actual_completion_date) {
            $start = Carbon::parse($ticket->actual_start_date);
            $end = Carbon::parse($ticket->actual_completion_date);
            $hours = round($start->diffInHours($end, false), 1);
            $durasi = $hours . ' Jam';
        }

        // D. Catatan
        $catatanAkhir = $ticket->completion_note ?? $ticket->cancellation_note ?? $ticket->rejection_reason ?? '-';

        return [
            $rowNumber,
            $ticket->ticket_num,
            $namaPemohon,
            $divisiPemohon,
            $lokasi,
            $ticket->department,
            $ticket->parameter_permintaan ?? '-',
            $ticket->category,
            $ticket->description,
            strtoupper(str_replace('_', ' ', $ticket->status)), // Format Status ke Huruf Besar
            $tglDibuat,
            $tglTarget,
            $tglMulai,
            $tglSelesai,
            $durasi,
            $ticket->processed_by_name ?? '-',
            $catatanAkhir
        ];
    }

    // =========================================================================
    // 3. STYLING & LOGIKA WARNA
    // =========================================================================
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                // Setup Table
                $sheet->setAutoFilter('A1:' . $lastColumn . $lastRow);
                $sheet->freezePane('A2');
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Setup Column Widths
                $widths = [
                    'A' => 6,
                    'B' => 18,
                    'C' => 20,
                    'D' => 18,
                    'E' => 15,
                    'F' => 18,
                    'G' => 15,
                    'H' => 12,
                    'I' => 40,
                    'J' => 18,
                    'K' => 18,
                    'L' => 15,
                    'M' => 18,
                    'N' => 18,
                    'O' => 14,
                    'P' => 20,
                    'Q' => 40
                ];
                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // Text Wrap
                $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('Q2:Q' . $lastRow)->getAlignment()->setWrapText(true);

                // --- LOOP SETIAP BARIS ---
                for ($row = 2; $row <= $lastRow; $row++) {

                    // 1. ZEBRA STRIPING (BACKGROUND ABU SELANG-SELING)
                    // Dijalankan PERTAMA agar tidak menimpa warna status/kategori
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFF8F9FA'],
                            ]
                        ]);
                    }

                    // 2. WARNA STATUS (KOLOM J)
                    $status = $sheet->getCell('J' . $row)->getValue();
                    $statusColor = $this->getStatusColor($status);

                    if ($statusColor) {
                        $sheet->getStyle('J' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => $statusColor],
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['argb' => 'FFFFFFFF'] // Font Putih
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                            ]
                        ]);
                    }

                    // 3. WARNA KATEGORI (KOLOM H) - BERAT, SEDANG, RINGAN
                    $kategori = strtoupper($sheet->getCell('H' . $row)->getValue());
                    $kategoriColor = null;

                    if (in_array($kategori, ['BERAT', 'HIGH'])) {
                        $kategoriColor = 'FFEF4444'; // Merah
                    } elseif (in_array($kategori, ['SEDANG', 'MEDIUM'])) {
                        $kategoriColor = 'FFF59E0B'; // Oranye/Kuning
                    } elseif (in_array($kategori, ['RINGAN', 'LOW'])) {
                        $kategoriColor = 'FF10B981'; // Hijau
                    }

                    if ($kategoriColor) {
                        $sheet->getStyle('H' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => $kategoriColor],
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['argb' => 'FFFFFFFF'] // Font Putih
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                            ]
                        ]);
                    }
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();

        return [
            // Style Header (Biru Tua)
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF000000']]],
            ],
            // Border All Cells
            'A1:' . $lastColumn . $lastRow => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
            ],
            // Center Align Columns
            'A2:A' . $lastRow => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'B2:B' . $lastRow => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'H2:H' . $lastRow => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'J2:J' . $lastRow => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'O2:O' . $lastRow => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    // =========================================================================
    // HELPER: Status Color Mapping
    // =========================================================================
    private function getStatusColor($status)
    {
        $statusKey = strtoupper(trim($status));

        $statusMap = [
            // --- SUKSES / SELESAI (HIJAU) ---
            'COMPLETED'           => 'FF10B981',
            'APPROVED'            => 'FF10B981',

            // --- PROSES (BIRU) ---
            'IN PROGRESS'         => 'FF1E40AF',
            'OPEN'                => 'FF3B82F6',

            // --- MENUNGGU (ORANYE / MERAH) ---
            'PENDING'             => 'FFEAB308', // Kuning
            'WAITING SPV'         => 'FFF59E0B', // Oranye
            'WAITING APPROVAL'    => 'FFDC2626', // Merah
            'WAITING GA APPROVAL' => 'FFDC2626', // Merah

            // --- BATAL (ABU / MERAH TERANG) ---
            'CANCELLED'           => 'FF6B7280',
            'REJECTED'            => 'FFEF4444',
            'DECLINED'            => 'FFEF4444',
        ];

        return $statusMap[$statusKey] ?? null;
    }
}
