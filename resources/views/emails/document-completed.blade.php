<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento concluído</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 24px; background-color: #f3f4f6;">
    <div style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 32px;">
        <h1 style="font-size: 20px; margin: 0 0 16px;">Documento concluído</h1>

        <p style="margin: 0 0 16px;">
            Todos os signatários assinaram o documento
            <strong>{{ $documentTitle }}</strong>.
        </p>

        <p style="margin: 0 0 24px;">
            <a href="{{ $documentUrl }}"
               style="display: inline-block; background-color: #111827; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px;">
                Ver documento
            </a>
        </p>

        <p style="margin: 0; font-size: 13px; color: #6b7280;">
            Você recebeu este aviso porque é o responsável por este documento.
        </p>
    </div>
</body>
</html>
