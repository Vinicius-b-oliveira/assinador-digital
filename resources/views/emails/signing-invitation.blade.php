<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convite para assinatura</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 24px; background-color: #f3f4f6;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="font-size: 20px; margin: 0 0 16px;">Olá, {{ $signatory->name }}</h1>

        <p style="margin: 0 0 16px;">
            Você foi convidado a assinar o documento
            <strong>{{ $documentTitle }}</strong>.
        </p>

        <p style="margin: 0 0 24px;">
            Clique no botão abaixo para visualizar o documento e registrar a sua assinatura.
        </p>

        <p style="margin: 0 0 24px;">
            <a href="{{ $signUrl }}"
               style="display: inline-block; background-color: #111827; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px;">
                Assinar documento
            </a>
        </p>

        <p style="margin: 0; font-size: 13px; color: #6b7280;">
            Se o botão não funcionar, copie e cole este link no navegador:<br>
            <a href="{{ $signUrl }}" style="color: #2563eb;">{{ $signUrl }}</a>
        </p>
    </div>
</body>
</html>
