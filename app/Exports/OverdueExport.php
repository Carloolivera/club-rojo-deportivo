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

class OverdueExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function collection(): Collection
    {
        $overdueFees = Fee::with(['member.sport'])
            ->where('status', 'overdue')
            ->orderBy('member_id')
            ->orderBy('period')
            ->get();

        return $overdueFees->groupBy('member_id')->map(function (Collection $fees) {
            $member = $fees->first()->member;

            return (object) [
                'name'        => $member?->name ?? '—',
                'dni'         => $member?->dni ?? '—',
                'email'       => $member?->email ?? '—',
                'sport'       => $member?->sport?->name ?? '—',
                'overdue_qty' => $fees->count(),
                'total_debt'  => $fees->sum('amount'),
                'periods'     => $fees->pluck('period')->implode(', '),
            ];
        })->values();
    }

    public function headings(): array
    {
        return ['Socio', 'DNI', 'Email', 'Deporte', 'Cuotas vencidas', 'Deuda total ($)', 'Períodos'];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->dni,
            $row->email,
            $row->sport,
            $row->overdue_qty,
            $row->total_debt,
            $row->periods,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF8B1A1A']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Morosos ' . now()->format('Y-m-d');
    }
}
