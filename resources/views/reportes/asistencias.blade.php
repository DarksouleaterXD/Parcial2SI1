<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Asistencias Docentes</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #1f2937;
            margin: 0;
            font-size: 20pt;
        }
        .header .subtitle {
            color: #6b7280;
            margin-top: 5px;
        }
        .periodo {
            background-color: #eff6ff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        thead {
            background-color: #f3f4f6;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 10px;
            text-align: left;
        }
        th {
            font-weight: bold;
            color: #374151;
            font-size: 10pt;
        }
        td {
            font-size: 10pt;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: bold;
        }
        .badge-presente {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-retardo {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-ausente {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 9pt;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Reporte de Asistencias Docentes</h1>
        <div class="subtitle">Sistema de Gestión Académica</div>
    </div>

    <div class="periodo">
        <strong>Período:</strong> {{ $fechaDesde }} al {{ $fechaHasta }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 35%;">Docente</th>
                <th class="text-center" style="width: 10%;">Total</th>
                <th class="text-center" style="width: 13%;">Presente</th>
                <th class="text-center" style="width: 13%;">Retardo</th>
                <th class="text-center" style="width: 13%;">Ausente</th>
                <th class="text-center" style="width: 16%;">% Asistencia</th>
            </tr>
        </thead>
        <tbody>
            @forelse($estadisticas as $docente)
                @php
                    $total = $docente->total_asistencias;
                    $porcentaje = $total > 0 ? round(($docente->asistencias_presente / $total) * 100, 1) : 0;
                @endphp
                <tr>
                    <td>{{ $docente->persona->nombre_completo ?? 'N/A' }}</td>
                    <td class="text-center"><strong>{{ $total }}</strong></td>
                    <td class="text-center">
                        <span class="badge badge-presente">{{ $docente->asistencias_presente }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-retardo">{{ $docente->asistencias_retardo }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-ausente">{{ $docente->asistencias_ausente }}</span>
                    </td>
                    <td class="text-center">
                        <strong>{{ $porcentaje }}%</strong>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 30px;">
                        No hay datos de asistencias en el período seleccionado
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} • Sistema de Gestión Académica FICCT
    </div>
</body>
</html>
