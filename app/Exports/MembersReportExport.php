<?php

namespace App\Exports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MembersReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(private ?Collection $records = null) {}

    public function collection(): Collection
    {
        if ($this->records !== null) {
            return $this->records->load('sport');
        }

        return Member::with('sport')
            ->orderBy('status')
            ->get();
    }

    public function headings(): array
    {
        return ['Nombre', 'DNI', 'Email', 'Teléfono', 'Deporte', 'Estado', 'Fecha alta'];
    }

    public function map($member): array
    {
        return [
            $member->name,
            $member->dni,
            $member->email ?? '—',
            $member->phone ?? '—',
            $member->sport?->name ?? '—',
            $this->translateStatus($member->status),
            $member->created_at->format('d/m/Y'),
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
        return 'Nómina de Socios';
    }

    private function translateStatus(string $status): string
    {
        return match ($status) {
            'active'    => 'Activo',
            'inactive'  => 'Inactivo',
            'suspended' => 'Suspendido',
            default     => $status,
        };
    }
}
