<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        p.subtitle { font-size: 11px; color: #555; margin-top: 0 0 16px; }
        .summary { background: #fef2f2; border: 1px solid #fecaca; padding: 8px 12px; margin-bottom: 14px; border-radius: 4px; }
        .summary b { color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #b91c1c; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        tr:nth-child(even) td { background: #fafafa; }
        .amount { text-align: right; }
        .periods { font-size: 9px; color: #6b7280; }
        tfoot td { font-weight: bold; border-top: 2px solid #374151; background: #f3f4f6; }
        tfoot .amount { color: #b91c1c; }
    </style>
</head>
<body>
    <h1>Club Rojo Deportivo — Listado de Morosos</h1>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</p>

    <div class="summary">
        <b>{{ $debtors->count() }}</b> socio(s) con cuotas vencidas —
        Deuda total: <b>${{ number_format($totalDebt, 0, ',', '.') }}</b>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Socio</th>
                <th>Deporte</th>
                <th>Cuotas vencidas</th>
                <th>Períodos</th>
                <th class="amount" style="text-align:right">Deuda</th>
            </tr>
        </thead>
        <tbody>
            @forelse($debtors as $i => $debtor)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $debtor['name'] }}</td>
                <td>{{ $debtor['sport'] }}</td>
                <td style="text-align:center">{{ $debtor['count'] }}</td>
                <td><span class="periods">{{ $debtor['periods'] }}</span></td>
                <td class="amount">${{ number_format($debtor['total'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;color:#6b7280;padding:16px">
                    No hay socios con cuotas vencidas.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($debtors->count())
        <tfoot>
            <tr>
                <td colspan="5">Total</td>
                <td class="amount">${{ number_format($totalDebt, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
