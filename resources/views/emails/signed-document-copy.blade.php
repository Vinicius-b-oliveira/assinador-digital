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
            Olá, {{ $signatoryName }}.
        </p>

        <p style="margin: 0 0 16px;">
            O documento <strong>{{ $documentTitle }}</strong> foi assinado por todos os signatários.
            Em anexo você encontra o <strong>certificado de assinaturas</strong>, com a relação de
            todos os assinantes, datas e códigos de integridade.
        </p>

        <p style="margin: 0; font-size: 13px; color: #6b7280;">
            Guarde este comprovante para seus registros.
        </p>
    </div>
</body>
</html>
