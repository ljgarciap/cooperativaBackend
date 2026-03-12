<!DOCTYPE html>
<html>
<head>
    <meta charset="utf8">
    <title>Documentación de Desembolso</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #334155;
            line-height: 1.5;
            margin: 0;
            padding: 2.5rem;
        }
        .header {
            border-bottom: 2px solid #3b82f6;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
        }
        .header h1 {
            color: #1e293b;
            font-size: 1.8rem;
            margin: 0;
            text-transform: uppercase;
        }
        .header p {
            color: #64748b;
            margin: 0.2rem 0 0;
            font-size: 0.9rem;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 2rem;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            padding: 0.5rem;
            font-weight: bold;
            background: #f1f5f9;
            width: 35%;
            border: 1px solid #e2e8f0;
        }
        .info-value {
            display: table-cell;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
        }
        .section-title {
            background: #3b82f6;
            color: white;
            padding: 0.5rem 1rem;
            font-weight: bold;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .footer {
            margin-top: 5rem;
            font-size: 0.8rem;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Documentación de Desembolso</h1>
        <p>Generado automáticamente el: {{ $fecha_generacion }}</p>
    </div>

    <div class="section-title">DETALLES DEL TRÁMITE</div>
    
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">No. TRAMITE</div>
            <div class="info-value">{{ $no_tramite }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">FECHA PROCESAMIENTO</div>
            <div class="info-value">{{ $credito->created_at->format('d/m/Y') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">CEDULA / IDENTIFICACIÓN</div>
            <div class="info-value">{{ $credito->identificacion }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">NOMBRE DEL ASOCIADO</div>
            <div class="info-value" style="text-transform: uppercase;">{{ $credito->nombre }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">CELULAR</div>
            <div class="info-value">{{ $credito->celular ?: 'No registrado' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">CORREO ELECTRÓNICO</div>
            <div class="info-value">{{ $credito->correo ?: 'No registrado' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">ESTADO DE LA SOLICITUD</div>
            <div class="info-value">{{ $credito->estado }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">MONTO APROBADO</div>
            <div class="info-value">${{ number_format($credito->monto, 0) }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">LÍNEA DE CRÉDITO</div>
            <div class="info-value">{{ $credito->tipo }}</div>
        </div>
    </div>

    <div class="section-title">CONDICIONES Y OBSERVACIONES</div>
    
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">OBSERVACIONES DEL ANALISTA</div>
            <div class="info-value">{{ $credito->observaciones ?: 'Sin observaciones adicionales.' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">PLAN DE PAGOS</div>
            <div class="info-value">Generado automáticamente según términos de la línea.</div>
        </div>
        <div class="info-row">
            <div class="info-label">MEDIO ENVIO</div>
            <div class="info-value">Correo electrónico corporativo / SMS</div>
        </div>
    </div>

    <div class="footer">
        Este documento es una representación digital del proceso de aprobación via Agente IA.
        <br>
        © {{ date('Y') }} Cooperativa - Todos los derechos reservados.
    </div>
</body>
</html>
