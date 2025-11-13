<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Horarios Semanales</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
        }
        .header h1 {
            color: #1f2937;
            font-size: 20px;
            margin-bottom: 5px;
        }
        .header h2 {
            color: #6b7280;
            font-size: 14px;
            font-weight: normal;
        }
        .meta-info {
            margin-bottom: 15px;
            font-size: 10px;
            color: #6b7280;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        thead {
            background-color: #f3f4f6;
        }
        th {
            padding: 8px;
            text-align: left;
            font-weight: 600;
            color: #1f2937;
            border-bottom: 2px solid #3b82f6;
            font-size: 10px;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        tbody tr:hover {
            background-color: #eff6ff;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
            margin-right: 3px;
        }
        .badge-blue {
            background-color: #dbeafe;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Horarios Semanales</h1>
        <h2>Sistema de Gestión Académica FICCT</h2>
    </div>

    <div class="meta-info">
        <strong>Periodo:</strong> {{ $periodo->nombre ?? 'N/A' }} |
        <strong>Fecha de generación:</strong> {{ $fecha_generacion }} |
        <strong>Total de registros:</strong> {{ count($horarios) }}
    </div>

    @if(count($horarios) > 0)
        <table>
            <thead>
                <tr>
                    <th>Días</th>
                    <th>Materia</th>
                    <th>Horario</th>
                    <th>Grupo</th>
                    <th>Aula</th>
                    <th>Docente</th>
                </tr>
            </thead>
            <tbody>
                @foreach($horarios as $horario)
                    <tr>
                        <td>
                            @if($horario->dias_semana)
                                @foreach($horario->dias_semana as $dia)
                                    <span class="badge badge-blue">{{ strtoupper(substr($dia, 0, 3)) }}</span>
                                @endforeach
                            @else
                                N/A
                            @endif
                        </td>
                        <td><strong>{{ $horario->grupo->materia->nombre ?? 'N/A' }}</strong></td>
                        <td style="font-family: monospace;">
                            {{ $horario->bloque->hora_inicio ?? '' }} - {{ $horario->bloque->hora_fin ?? '' }}
                        </td>
                        <td>{{ $horario->grupo->codigo ?? 'N/A' }}</td>
                        <td>{{ $horario->aula->numero_aula ?? 'N/A' }}</td>
                        <td>
                            {{ $horario->docente->persona->nombre ?? '' }}
                            {{ $horario->docente->persona->apellido_paterno ?? '' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 40px; color: #9ca3af;">
            No se encontraron horarios con los filtros seleccionados
        </div>
    @endif

    <div class="footer">
        Sistema de Gestión Académica FICCT - Generado automáticamente el {{ $fecha_generacion }}
    </div>
</body>
</html>
