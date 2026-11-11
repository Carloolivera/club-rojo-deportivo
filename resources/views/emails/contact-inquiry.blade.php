{{-- resources/views/emails/contact-inquiry.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; color: #333; background: #f8f9fa; }
    .card { background: #fff; border-radius: 8px; padding: 32px; border: 1px solid #e9ecef; }
    h2 { color: #D71F28; border-bottom: 2px solid #D71F28; padding-bottom: 12px; margin-top: 0; font-size: 20px; }
    .field { margin-bottom: 20px; }
    .label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
    .value { font-size: 16px; font-weight: bold; color: #212529; }
    .footer { margin-top: 32px; font-size: 12px; color: #adb5bd; border-top: 1px solid #e9ecef; padding-top: 16px; }
  </style>
</head>
<body>
  <div class="card">
    <h2>Nueva consulta de socio</h2>
    <div class="field">
      <div class="label">Nombre completo</div>
      <div class="value">{{ $data['name'] }}</div>
    </div>
    <div class="field">
      <div class="label">Teléfono / WhatsApp</div>
      <div class="value">{{ $data['phone'] }}</div>
    </div>
    @if(!empty($data['sport']))
    <div class="field">
      <div class="label">Deporte de interés</div>
      <div class="value">{{ $data['sport'] }}</div>
    </div>
    @endif
    <div class="footer">
      Enviado desde el formulario de contacto de <strong>clubrojo.demo</strong><br>
      Respondé directamente a {{ $data['name'] }} al número {{ $data['phone'] }}.
    </div>
  </div>
</body>
</html>
