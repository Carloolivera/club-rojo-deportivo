<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p.period { font-size: 12px; color: #555; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #1e40af; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
        .paid    { background: #d1fae5; color: #065f46; }
        .pending { background: #fef3c7; color: #92400e; }
        .overdue { background: #fee2e2; color: #991b1b; }
        .exempt  { background: #e0e7ff; color: #3730a3; }
        tfoot td { font-weight: bold; border-top: 2px solid #374151; }
    </style>
</head>
<body>
    <h1>Reporte de Cuotas</h1>
    <p class="period">Período: {{ $period }}</p>

    <table>
        <thead>
            <tr>
                <th>Socio</th>
                <th>Deporte</th>
                <th>Monto</th>
                <th>Estado</th>
                <th>Pagado el</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fees as $fee)
            <tr>
                <td>{{ $fee->member->name }}</td>
                <td>{{ $fee->member->sport?->name ?? '—' }}</td>
                <td>${{ number_format($fee->amount, 0, ',', '.') }}</td>
                <td><span class="badge {{ $fee->status }}">{{ ucfirst($fee->status) }}</span></td>
                <td>{{ $fee->paid_at?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;color:#6b7280;">Sin cuotas para este período.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total</td>
                <td>{{ $fees->count() }} cuota(s)</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
