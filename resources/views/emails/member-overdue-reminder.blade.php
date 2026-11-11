<x-mail::message>
# Hola {{ $member->name }}

Te recordamos que tenés **{{ $overdueFees->count() }}** cuota(s) vencida(s) en el **Club Rojo Deportivo**.

<x-mail::table>
| Período | Monto |
|:--------|------:|
@foreach ($overdueFees->sortBy('period') as $fee)
| {{ $fee->period }} | ${{ number_format($fee->amount, 0, ',', '.') }} |
@endforeach
</x-mail::table>

**Total adeudado: ${{ number_format($overdueFees->sum('amount'), 0, ',', '.') }}**

Para regularizar tu situación, podés ver y pagar tus cuotas desde el siguiente enlace (válido por 7 días):

<x-mail::button :url="$fees_url">
Ver mis cuotas
</x-mail::button>

O bien acercarte a la secretaría del club.

Gracias,<br>
**Club Rojo Deportivo**
</x-mail::message>
