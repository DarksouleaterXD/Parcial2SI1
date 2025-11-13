<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Aulas Disponibles</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #10b981;
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
            padding: 6px 4px;
            text-align: center;
            font-weight: 600;
            color: #1f2937;
            border-bottom: 2px solid #10b981;
            font-size: 9px;
        }
        td {
            padding: 5px 4px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
        }
        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .disponible {
            background-color: #d1fae5;
            color: #065f46;
            font-weight: 600;
        }
        .ocupada {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: 600;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
        .aula-header {
            background-color: #f3f4f6;
            text-align: left;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Aulas Disponibles</h1>
        <h2>Sistema de Gestión Académica FICCT</h2>
    </div>

    <div class="meta-info">
        @if($dia)
            <strong>Día:</strong> {{ $dia }} |
        @endif
        <strong>Fecha de generación:</strong> {{ $fecha_generacion }} |
        <strong>Total de aulas:</strong> {{ count($aulas) }}
    </div>

    @if(count($aulas) > 0)
        <table>
            <thead>
                <tr>
                    <th>Aula</th>
                    <th>Capacidad</th>
                    @foreach($bloques as $bloque)
                        <th style="font-size: 8px;">
                            {{ $bloque->nombre }}<br>
                            <span style="font-weight: normal;">{{ $bloque->hora_inicio }}-{{ $bloque->hora_fin }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($aulas as $aula_data)
                    <tr>
                        <td class="aula-header">{{ $aula_data['aula']->numero_aula }}</td>
                        <td>{{ $aula_data['aula']->capacidad }}</td>
                        @foreach($aula_data['bloques'] as $bloque_info)
                            <td class="{{ $bloque_info['disponible'] ? 'disponible' : 'ocupada' }}">
                                {{ $bloque_info['disponible'] ? 'LIBRE' : 'OCUPADA' }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 15px; font-size: 9px;">
            <strong>Leyenda:</strong>
            <span style="background-color: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 3px; margin-left: 10px;">LIBRE</span>
            <span style="background-color: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 3px; margin-left: 5px;">OCUPADA</span>
        </div>
    @else
        <div style="text-align: center; padding: 40px; color: #9ca3af;">
            No se encontraron aulas
        </div>
    @endif

    <div class="footer">
        Sistema de Gestión Académica FICCT - Generado automáticamente el {{ $fecha_generacion }}
    </div>
</body>
</html>
