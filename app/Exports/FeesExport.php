<?php

namespace App\Exports;

use App\Models\Fee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FeesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        private string $period = '',
        private ?Collection $records = null
    ) {}

    public function collection(): Collection
    {
        if ($this->records !== null) {
            return $this->records->load('member.sport');
        }

        return Fee::with('member.sport')
            ->forPeriod($this->period)
            ->orderBy('status')
            ->get();
    }

    public function headings(): array
    {
        return ['Socio', 'DNI', 'Email', 'Deporte', 'Período', 'Monto ($)', 'Estado', 'Pagado el'];
    }

    public function map($fee): array
    {
        return [
            $fee->member->name,
            $fee->member->dni,
            $fee->member->email ?? '—',
            $fee->member->sport?->name ?? '—',
            $fee->period,
            $fee->amount,
            $this->translateStatus($fee->status),
            $fee->paid_at?->format('d/m/Y') ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E3A5F']],
            ],
        ];
    }

    public function title(): string
    {
        return $this->period ? 'Cuotas ' . $this->period : 'Cuotas';
    }

    private function translateStatus(string $status): string
    {
        return match ($status) {
            'paid'    => 'Pagado',
            'pending' => 'Pendiente',
            'overdue' => 'Vencido',
            default   => $status,
        };
    }
}
