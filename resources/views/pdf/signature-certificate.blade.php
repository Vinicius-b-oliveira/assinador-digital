<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Certificado de Assinaturas</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
        }

        body {
            color: #1f2937;
            font-size: 12px;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0 0 4px;
        }

        .header p {
            color: #6b7280;
            font-size: 11px;
            margin: 0;
        }

        .meta {
            margin-bottom: 24px;
        }

        .meta-row {
            margin-bottom: 4px;
        }

        .meta-label {
            color: #6b7280;
            display: inline-block;
            width: 140px;
        }

        .meta-value {
            font-weight: bold;
        }

        h2 {
            font-size: 14px;
            margin: 0 0 12px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f3f4f6;
            font-size: 11px;
            text-transform: uppercase;
        }

        .signer {
            font-weight: bold;
        }

        .email {
            color: #6b7280;
            font-size: 11px;
        }

        .hash {
            color: #6b7280;
            font-size: 10px;
            word-break: break-all;
        }

        .footer {
            color: #9ca3af;
            font-size: 10px;
            margin-top: 32px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Certificado de Assinaturas</h1>
        <p>Comprovante de integridade e autoria das assinaturas coletadas.</p>
    </div>

    <div class="meta">
        <div class="meta-row">
            <span class="meta-label">Documento</span>
            <span class="meta-value">{{ $document->title }}</span>
        </div>
        @if ($document->description)
            <div class="meta-row">
                <span class="meta-label">Descrição</span>
                <span class="meta-value">{{ $document->description }}</span>
            </div>
        @endif
        <div class="meta-row">
            <span class="meta-label">Arquivo original</span>
            <span class="meta-value">{{ $document->file_original_name }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Total de assinaturas</span>
            <span class="meta-value">{{ $signatures->count() }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Concluído em</span>
            <span class="meta-value">{{ $generatedAt->format('d/m/Y H:i:s') }}</span>
        </div>
    </div>

    <h2>Assinaturas</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Signatário</th>
                <th>Data/Hora</th>
                <th>IP</th>
                <th>Hash de integridade</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($signatures as $index => $signature)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div class="signer">{{ $signature->signer_name }}</div>
                        <div class="email">{{ $signature->signatory->email }}</div>
                    </td>
                    <td>{{ $signature->signed_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $signature->ip_address ?? '—' }}</td>
                    <td class="hash">{{ hash('sha256', $signature->signature_data) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Documento gerado automaticamente pelo Assinador Digital em {{ $generatedAt->format('d/m/Y H:i:s') }}.
    </div>
</body>
</html>
