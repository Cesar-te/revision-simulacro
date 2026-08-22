<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 5mm 4mm 6mm 4mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 5.6pt;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        .header-container {
            text-align: center;
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1.2px solid #cbd5e1;
        }

        .main-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            line-height: 1.1;
        }

        .sub-title {
            font-size: 7.2pt;
            color: #334155;
            margin-top: 1px;
            font-weight: 600;
        }

        .meta-badges {
            font-size: 6pt;
            color: #64748b;
            margin-top: 1px;
        }

        table.results-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 5.2pt;
        }

        table.results-table th,
        table.results-table td {
            border: 0.4px solid #cbd5e1;
            padding: 1.8px 1px;
            line-height: 1.05;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        table.results-table thead tr th {
            background-color: #d1e7dd;
            color: #14532d;
            font-weight: bold;
            text-align: center;
            font-size: 5.2pt;
            padding: 2.2px 1px;
        }

        table.results-table thead tr th.th-career {
            background-color: #cfe2ff;
            color: #1e3a8a;
        }

        table.results-table thead tr th.th-base {
            background-color: #e2e8f0;
            color: #1e293b;
        }

        table.results-table thead tr th.th-punts {
            background-color: #fef08a;
            color: #854d0e;
        }

        table.results-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        .font-bold { font-weight: bold; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        .score-positive { color: #0f172a; }
        .score-negative { color: #dc2626; font-weight: bold; }
        .score-zero { color: #94a3b8; }

        .rank-medal-1 { background-color: #fef3c7; color: #b45309; font-weight: bold; }
        .rank-medal-2 { background-color: #f1f5f9; color: #334155; font-weight: bold; }
        .rank-medal-3 { background-color: #ffedd5; color: #c2410c; font-weight: bold; }

        .badge-group {
            font-size: 4.8pt;
            font-weight: bold;
            padding: 0.5px 2px;
            border-radius: 2px;
            display: inline-block;
        }
        .badge-a { background-color: #ffe4e6; color: #e11d48; }
        .badge-bcd { background-color: #fef3c7; color: #d97706; }
        .badge-ef { background-color: #cffafe; color: #0891b2; }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 5.5pt;
            color: #94a3b8;
            text-align: right;
            border-top: 0.4px solid #e2e8f0;
            padding-top: 1px;
        }

        .page-number:before {
            content: "Página " counter(page);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-container">
        @if(!empty($logoBase64))
            <div style="text-align: center; margin-bottom: 5px;">
                <img src="{{ $logoBase64 }}" style="max-height: 96px; width: auto;" alt="Logo Cachimbos">
            </div>
        @endif
        <div class="main-title">CACHIMBOS UNPRG - {{ $groupTitle }}</div>
        <div class="sub-title">{{ mb_strtoupper($exam->title, 'UTF-8') }}</div>
        <div class="meta-badges">
            Evaluados: <strong>{{ $results->count() }} alumnos</strong> |
            Penalidad: <strong style="color: #dc2626;">{{ $exam->incorrect_penalty }} pts</strong> |
            En blanco: <strong>{{ $exam->blank_score }} pts</strong> |
            Fecha: <strong>{{ date('d/m/Y H:i') }}</strong>
        </div>
    </div>

    <!-- Results Table -->
    <table class="results-table">
        <thead>
            <tr>
                <th style="width: 2.2%;" class="th-base">N°</th>
                <th style="width: 4.8%;" class="th-base">DNI</th>
                <th style="width: 14.5%; text-align: left; padding-left: 2px;" class="th-base">NOMBRE Y APELLIDO</th>
                <th style="width: 12.0%; text-align: left; padding-left: 2px;" class="th-career">CARRERA</th>

                @foreach($subjectCols as $code => $col)
                    <th style="width: 3.6%;">{{ $code }}</th>
                @endforeach

                <th style="width: 1.7%;" class="th-base" title="Buenas">B</th>
                <th style="width: 1.7%;" class="th-base" title="Malas">M</th>
                <th style="width: 1.7%;" class="th-base" title="En blanco">BL</th>
                <th style="width: 4.6%;" class="th-punts">PUNTS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($results as $index => $r)
                @php
                    $subScores = $r->subject_scores;
                    $rankNum = $group ? ($r->group_rank ?: ($index + 1)) : $r->general_rank;
                    $rowClass = '';
                    if ($rankNum === 1) $rowClass = 'rank-medal-1';
                    elseif ($rankNum === 2) $rowClass = 'rank-medal-2';
                    elseif ($rankNum === 3) $rowClass = 'rank-medal-3';
                @endphp
                <tr>
                    <!-- N° -->
                    <td class="text-center font-bold {{ $rowClass }}">{{ $rankNum }}</td>

                    <!-- DNI -->
                    <td class="text-center font-mono">{{ $r->dni ?: '-' }}</td>

                    <!-- Nombre -->
                    <td class="text-left font-bold" style="padding-left: 2px;" title="{{ $r->full_name }}">
                        {{ $r->full_name }}
                    </td>

                    <!-- Carrera -->
                    <td class="text-left" style="padding-left: 2px;" title="{{ $r->career }}">
                        {{ $r->career }}
                    </td>

                    <!-- 17 Asignaturas -->
                    @foreach(array_keys($subjectCols) as $code)
                        @php
                            $val = (float)($subScores[$code] ?? 0.0);
                            $class = $val > 0 ? 'score-positive' : ($val < 0 ? 'score-negative' : 'score-zero');
                        @endphp
                        <td class="text-right font-mono {{ $class }}">
                            {{ number_format($val, 4) }}
                        </td>
                    @endforeach

                    <!-- Buenas (B), Malas (M), Blanco (BL) -->
                    <td class="text-center" style="color: #15803d; font-weight: bold;">{{ $r->correct_count }}</td>
                    <td class="text-center" style="color: #b91c1c; font-weight: bold;">{{ $r->incorrect_count }}</td>
                    <td class="text-center" style="color: #64748b;">{{ $r->blank_count }}</td>

                    <!-- Puntaje Total -->
                    <td class="text-right font-mono font-bold {{ $r->total_score < 0 ? 'score-negative' : '' }}">
                        {{ number_format($r->total_score, 4) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="25" class="text-center" style="padding: 15px; color: #94a3b8;">
                        No hay resultados registrados para este reporte.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        Simulacro UNPRG • Calificación Ponderada • <span class="page-number"></span>
    </div>
</body>
</html>
