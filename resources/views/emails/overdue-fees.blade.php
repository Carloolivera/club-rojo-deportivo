<x-mail::message>
# Alerta de Cuotas Vencidas

Se detectaron **{{ $fees->count() }}** socio(s) con cuotas pendientes de pago con más de **{{ $threshold }} días** de atraso.

<x-mail::table>
| Socio | Período | Monto | Días de atraso |
|:------|:--------|------:|---------------:|
@foreach ($fees as $fee)
| {{ $fee->member->name }} | {{ $fee->period }} | ${{ number_format($fee->amount, 2) }} | {{ now()->diffInDays(\Carbon\Carbon::createFromFormat('Y-m', $fee->period)->endOfMonth()) }} |
@endforeach
</x-mail::table>

Por favor, tome las acciones correspondientes.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
