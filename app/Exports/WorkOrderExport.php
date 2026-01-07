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
    // 1. HEADER KOLOM (DITAMBAH AGAR LENGKAP)
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
    // 2. MAPPING DATA (LOGIKA PENGISIAN)
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

        // B. Format Tanggal Indonesia (Lengkap dengan Jam)
        $tglDibuat = $ticket->created_at
            ? Carbon::parse($ticket->created_at)->setTimezone('Asia/Jakarta')->locale('id')->isoFormat('DD MMM YYYY HH:mm')
            : '-';
        $tglTarget = $ticket->target_completion_date
            ? Carbon::parse($ticket->target_completion_date)->locale('id')->isoFormat('DD MMM YYYY')
            : '-';

        $tglMulai = $ticket->actual_start_date
            ? Carbon::parse($ticket->actual_start_date)->setTimezone('Asia/Jakarta')->locale('id')->isoFormat('DD MMM YYYY HH:mm')
            : '-';
        $tglSelesai = $ticket->actual_completion_date
            ? Carbon::parse($ticket->actual_completion_date)->setTimezone('Asia/Jakarta')->locale('id')->isoFormat('DD MMM YYYY HH:mm')
            : '-';

        // C. Hitung Durasi (KPI Lead Time)
        $durasi = '-';
        if ($ticket->actual_start_date && $ticket->actual_completion_date) {
            $start = Carbon::parse($ticket->actual_start_date);
            $end = Carbon::parse($ticket->actual_completion_date);
            $hours = round($start->diffInHours($end, false), 1);
            $durasi = $hours . ' Jam';
        }

        // D. Gabungkan Catatan
        $catatanAkhir = $ticket->completion_note
            ?? $ticket->cancellation_note
            ?? $ticket->rejection_reason
            ?? '-';

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
            strtoupper(str_replace('_', ' ', $ticket->status)),
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
    // 3. STYLING (ENHANCED DESIGN)
    // =========================================================================
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                // Auto Filter
                $fullRange = 'A1:' . $lastColumn . $lastRow;
                $sheet->setAutoFilter($fullRange);

                // Freeze Header
                $sheet->freezePane('A2');

                // Set Row Height untuk Header
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Set Column Widths (Manual untuk hasil optimal)
                $sheet->getColumnDimension('A')->setWidth(6);   // NO
                $sheet->getColumnDimension('B')->setWidth(18);  // ID TIKET
                $sheet->getColumnDimension('C')->setWidth(20);  // PEMOHON
                $sheet->getColumnDimension('D')->setWidth(18);  // DIVISI
                $sheet->getColumnDimension('E')->setWidth(15);  // LOKASI
                $sheet->getColumnDimension('F')->setWidth(18);  // DEPT
                $sheet->getColumnDimension('G')->setWidth(15);  // PARAMETER
                $sheet->getColumnDimension('H')->setWidth(12);  // KATEGORI
                $sheet->getColumnDimension('I')->setWidth(40);  // DESKRIPSI
                $sheet->getColumnDimension('J')->setWidth(18);  // STATUS
                $sheet->getColumnDimension('K')->setWidth(18);  // TGL DIBUAT
                $sheet->getColumnDimension('L')->setWidth(15);  // TGL TARGET
                $sheet->getColumnDimension('M')->setWidth(18);  // MULAI
                $sheet->getColumnDimension('N')->setWidth(18);  // SELESAI
                $sheet->getColumnDimension('O')->setWidth(14);  // DURASI
                $sheet->getColumnDimension('P')->setWidth(20);  // PIC
                $sheet->getColumnDimension('Q')->setWidth(40);  // CATATAN

                // Text Wrap untuk kolom deskripsi dan catatan
                $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('Q2:Q' . $lastRow)->getAlignment()->setWrapText(true);

                // Conditional Formatting untuk Status
                for ($row = 2; $row <= $lastRow; $row++) {
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
                                'color' => ['argb' => 'FFFFFFFF']
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                            ]
                        ]);
                    }

                    // Highlight kategori BERAT/HIGH
                    $kategori = $sheet->getCell('H' . $row)->getValue();
                    if (in_array(strtoupper($kategori), ['BERAT', 'HIGH'])) {
                        $sheet->getStyle('H' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFFF6B6B'],
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => ['argb' => 'FFFFFFFF']
                            ]
                        ]);
                    }

                    // Zebra Striping untuk readability
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFF8F9FA'],
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
            // Style Header (Gradient Blue)
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['argb' => 'FFFFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E3A8A'], // Biru Tua seperti logo
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ],

            // Border All Cells
            'A1:' . $lastColumn . $lastRow => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD1D5DB'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                ],
            ],

            // Center align untuk kolom tertentu
            'A2:A' . $lastRow => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'B2:B' . $lastRow => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'H2:H' . $lastRow => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'J2:J' . $lastRow => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'O2:O' . $lastRow => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
        ];
    }

    // =========================================================================
    // HELPER: Status Color Mapping
    // =========================================================================
    private function getStatusColor($status)
    {
        $statusMap = [
            'COMPLETED' => 'FF10B981',       // Green
            'IN PROGRESS' => 'FF1E40AF',     // Blue
            'PENDING' => 'FFEAB308',         // Yellow
            'WAITING SPV' => 'FFF59E0B',     // Orange
            'WAITING APPROVAL' => 'FFDC2626', // Red
            'CANCELLED' => 'FF6B7280',       // Gray
            'REJECTED' => 'FFEF4444',        // Red
        ];

        return $statusMap[strtoupper($status)] ?? null;
    }
}
