<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p.subtitle { font-size: 12px; color: #555; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #1e40af; color: #fff; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
        .active   { background: #d1fae5; color: #065f46; }
        .inactive { background: #f3f4f6; color: #374151; }
        tfoot td { font-weight: bold; border-top: 2px solid #374151; }
    </style>
</head>
<body>
    {{-- Branding placeholder --}}
    <h1>Club Rojo Deportivo</h1>
    <p class="subtitle">Reporte de Socios — Generado el {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Deporte</th>
                <th>Estado</th>
                <th>Fecha alta</th>
            </tr>
        </thead>
        <tbody>
            @forelse($members as $member)
            <tr>
                <td>{{ $member->name }}</td>
                <td>{{ $member->sport?->name ?? '—' }}</td>
                <td><span class="badge {{ $member->status }}">{{ ucfirst($member->status) }}</span></td>
                <td>{{ $member->created_at->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center;color:#6b7280;">Sin socios registrados.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td>{{ $members->count() }} socio(s)</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
