@extends('layouts.app')

@section('title', $exam->title)

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('exams.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-indigo-400 transition-colors mb-2">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Volver a la lista de simulacros</span>
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">{{ $exam->title }}</h1>
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    {{ $exam->total_questions }} Preguntas
                </span>
            </div>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400 mt-2">
                <span>Penalidad: <strong class="text-rose-400">{{ $exam->incorrect_penalty }} pts</strong></span> •
                <span>En blanco: <strong class="text-slate-300">{{ $exam->blank_score }} pts</strong></span> •
                <span>Claves cargadas:
                    <span class="text-rose-300 font-semibold" title="Grupo A">A: {{ $keysByGroup['A'] }}</span> |
                    <span class="text-amber-300 font-semibold" title="Grupo BCD">BCD: {{ $keysByGroup['BCD'] }}</span> |
                    <span class="text-cyan-300 font-semibold" title="Grupo EF">EF: {{ $keysByGroup['EF'] }}</span>
                    @if($keysByGroup['ALL'] > 0)
                        | <span class="text-slate-300 font-semibold" title="General">Gen: {{ $keysByGroup['ALL'] }}</span>
                    @endif
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Modal Claves -->
            <button onclick="document.getElementById('modal-keys').classList.remove('hidden')" class="px-3.5 py-2 text-xs font-semibold text-slate-200 bg-slate-800 hover:bg-slate-700 rounded-xl border border-slate-700 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-key text-amber-400"></i>
                <span>Claves Oficiales</span>
            </button>

            <!-- Modal Puntajes -->
            <button onclick="document.getElementById('modal-scoring-rules').classList.remove('hidden')" class="px-3.5 py-2 text-xs font-semibold text-slate-200 bg-slate-800 hover:bg-slate-700 rounded-xl border border-slate-700 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-calculator text-cyan-400"></i>
                <span>Puntajes</span>
            </button>

            <!-- Modal Subir Respuestas -->
            <button onclick="document.getElementById('modal-upload-responses').classList.remove('hidden')" class="px-3.5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-md shadow-indigo-500/20 transition-all flex items-center gap-2">
                <i class="fa-solid fa-file-arrow-up"></i>
                <span>Importar Respuestas</span>
            </button>

            <!-- Recalcular -->
            @if($totalStudents > 0)
                <form action="{{ route('exams.recalculate', $exam) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" title="Recalcular todos los puntajes según las claves vigentes" class="p-2 text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl border border-slate-700 transition-colors">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </form>

                <!-- Exportar Excel con 4 Hojas -->
                <a href="{{ route('exams.export', ['exam' => $exam, 'career' => request('career')]) }}" class="px-3.5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md shadow-emerald-500/20 transition-all flex items-center gap-2" title="Descargar archivo Excel con 4 pestañas: General, Biomédicas (A), Letras (BCD) e Ingenierías (EF)">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Exportar Excel</span>
                </a>

                <!-- Exportar PDF Horizontal -->
                <a href="{{ route('exams.export-pdf', ['exam' => $exam, 'group' => request('group')]) }}" target="_blank" class="px-3.5 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-500 rounded-xl shadow-md shadow-rose-500/20 transition-all flex items-center gap-2" title="Descargar PDF en hoja horizontal con las 17 asignaturas y orden de mérito">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Exportar PDF (Horizontal)</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Statistics Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Evaluados -->
        <div class="glass-card rounded-2xl p-4 border border-slate-800">
            <span class="text-[11px] font-semibold text-slate-400 block uppercase tracking-wider">Total Evaluados</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-extrabold text-white">{{ number_format($totalStudents) }}</span>
                <span class="text-xs text-slate-500">alumnos</span>
            </div>
        </div>

        <!-- Promedio -->
        <div class="glass-card rounded-2xl p-4 border border-slate-800">
            <span class="text-[11px] font-semibold text-slate-400 block uppercase tracking-wider">Promedio General</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-extrabold text-indigo-400">{{ number_format($avgScore, 2) }}</span>
                <span class="text-xs text-slate-500">pts</span>
            </div>
        </div>

        <!-- Máximo Puntaje -->
        <div class="glass-card rounded-2xl p-4 border border-slate-800">
            <span class="text-[11px] font-semibold text-slate-400 block uppercase tracking-wider">Puntaje Máximo (1° Puesto)</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-extrabold text-emerald-400">{{ number_format($maxScore, 4) }}</span>
                <span class="text-xs text-slate-500">pts</span>
            </div>
        </div>

        <!-- Mínimo Puntaje -->
        <div class="glass-card rounded-2xl p-4 border border-slate-800">
            <span class="text-[11px] font-semibold text-slate-400 block uppercase tracking-wider">Puntaje Mínimo</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-extrabold {{ $minScore < 0 ? 'text-rose-400' : 'text-slate-300' }}">{{ number_format($minScore, 4) }}</span>
                <span class="text-xs text-slate-500">pts</span>
            </div>
        </div>

        <!-- Distribución Grupos -->
        <div class="glass-card rounded-2xl p-4 border border-slate-800 col-span-2 lg:col-span-1">
            <span class="text-[11px] font-semibold text-slate-400 block uppercase tracking-wider">Alumnos por Grupo</span>
            <div class="flex items-center gap-2 mt-1.5 text-xs font-semibold">
                <span class="text-rose-400" title="Biomédicas">A: {{ $groupsCount['A'] }}</span> •
                <span class="text-amber-400" title="Letras">BCD: {{ $groupsCount['BCD'] }}</span> •
                <span class="text-cyan-400" title="Ingenierías">EF: {{ $groupsCount['EF'] }}</span>
            </div>
        </div>
    </div>

    <!-- Filters and Academic Group Tabs -->
    <div class="glass-card rounded-2xl p-4 border border-slate-800">
        <form method="GET" action="{{ route('exams.show', $exam) }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <!-- Academic Group Tabs / Pills -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0">
                <!-- Tab General -->
                <a href="{{ route('exams.show', ['exam' => $exam, 'career' => request('career'), 'search' => request('search')]) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all flex items-center gap-1.5 {{ !request('group') || request('group') === 'all' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'bg-slate-800/70 text-slate-300 hover:text-white hover:bg-slate-800' }}">
                    <i class="fa-solid fa-globe"></i>
                    <span>Tabla General</span>
                    <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ !request('group') || request('group') === 'all' ? 'bg-white/20 text-white' : 'bg-slate-700 text-slate-300' }}">{{ $totalStudents }}</span>
                </a>

                <!-- Tab Biomédicas A -->
                <a href="{{ route('exams.show', ['exam' => $exam, 'group' => 'A', 'career' => request('career'), 'search' => request('search')]) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request('group') === 'A' ? 'bg-rose-600 text-white shadow-md shadow-rose-500/20' : 'bg-slate-800/70 text-rose-400 hover:text-rose-200 hover:bg-slate-800' }}">
                    <i class="fa-solid fa-heart-pulse"></i>
                    <span>Biomédicas (A)</span>
                    <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ request('group') === 'A' ? 'bg-white/20 text-white' : 'bg-rose-500/10 text-rose-300 border border-rose-500/20' }}">{{ $groupsCount['A'] }}</span>
                </a>

                <!-- Tab Letras BCD -->
                <a href="{{ route('exams.show', ['exam' => $exam, 'group' => 'BCD', 'career' => request('career'), 'search' => request('search')]) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request('group') === 'BCD' ? 'bg-amber-600 text-white shadow-md shadow-amber-500/20' : 'bg-slate-800/70 text-amber-400 hover:text-amber-200 hover:bg-slate-800' }}">
                    <i class="fa-solid fa-book-open"></i>
                    <span>Letras (BCD)</span>
                    <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ request('group') === 'BCD' ? 'bg-white/20 text-white' : 'bg-amber-500/10 text-amber-300 border border-amber-500/20' }}">{{ $groupsCount['BCD'] }}</span>
                </a>

                <!-- Tab Ingenierías EF -->
                <a href="{{ route('exams.show', ['exam' => $exam, 'group' => 'EF', 'career' => request('career'), 'search' => request('search')]) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all flex items-center gap-1.5 {{ request('group') === 'EF' ? 'bg-cyan-600 text-white shadow-md shadow-cyan-500/20' : 'bg-slate-800/70 text-cyan-400 hover:text-cyan-200 hover:bg-slate-800' }}">
                    <i class="fa-solid fa-compass-drafting"></i>
                    <span>Ingenierías (EF)</span>
                    <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ request('group') === 'EF' ? 'bg-white/20 text-white' : 'bg-cyan-500/10 text-cyan-300 border border-cyan-500/20' }}">{{ $groupsCount['EF'] }}</span>
                </a>
            </div>

            <!-- Career Dropdown and Search -->
            <div class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="group" value="{{ request('group') }}">

                <!-- Career Dropdown -->
                <select name="career" onchange="this.form.submit()" class="px-3 py-1.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-indigo-500">
                    <option value="all">Todas las Carreras</option>
                    @foreach($careersList as $cName)
                        <option value="{{ $cName }}" {{ request('career') == $cName ? 'selected' : '' }}>
                            {{ $cName }}
                        </option>
                    @endforeach
                </select>

                <!-- Search Input -->
                <div class="relative flex-1 sm:w-64">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por DNI o Nombre..." class="w-full pl-8 pr-3 py-1.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-500 text-xs"></i>
                </div>

                <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl transition-colors">
                    Filtrar
                </button>

                @if(request('group') || request('career') || request('search'))
                    <a href="{{ route('exams.show', $exam) }}" title="Limpiar filtros" class="p-1.5 text-slate-400 hover:text-white bg-slate-800 rounded-xl text-xs">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Results Table (Orden de Mérito) -->
    <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden shadow-2xl">
        @if($results->isEmpty())
            <div class="p-12 text-center">
                <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center mx-auto mb-3 text-xl">
                    <i class="fa-solid fa-user-xmark"></i>
                </div>
                <h3 class="text-base font-bold text-white">No hay alumnos evaluados o no coinciden con los filtros</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
                    Importa las respuestas de los estudiantes mediante un archivo Excel para calcular los puntajes.
                </p>
                <button onclick="document.getElementById('modal-upload-responses').classList.remove('hidden')" class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-lg shadow-indigo-500/20 transition-all inline-flex items-center gap-2">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Subir Excel de Respuestas</span>
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-900/95 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                        <tr>
                            @if(request('group') && request('group') !== 'all')
                                <th class="py-3 px-3 text-center whitespace-nowrap text-amber-300 font-bold">Pto. Grupo</th>
                                <th class="py-3 px-2 text-center whitespace-nowrap">Pto. Gen.</th>
                                <th class="py-3 px-2 text-center whitespace-nowrap">Pto. Carr.</th>
                            @else
                                <th class="py-3 px-3 text-center whitespace-nowrap text-amber-300 font-bold">Pto. Gen.</th>
                                <th class="py-3 px-2 text-center whitespace-nowrap">Pto. Carr.</th>
                                <th class="py-3 px-2 text-center whitespace-nowrap">Pto. Grupo</th>
                            @endif

                            <th class="py-3 px-3 whitespace-nowrap">DNI</th>
                            <th class="py-3 px-4 whitespace-nowrap">Nombre y Apellido</th>
                            <th class="py-3 px-4 whitespace-nowrap">Carrera</th>
                            <th class="py-3 px-3 text-center whitespace-nowrap">Grupo</th>

                            <!-- Asignaturas UNPRG -->
                            @foreach($subjectColumns ?? \App\Services\ScoringService::SUBJECT_COLUMNS as $code => $col)
                                <th class="py-2.5 px-2 text-center text-[10px] font-bold bg-emerald-950/30 text-emerald-300 border-l border-slate-800/80 whitespace-nowrap" title="{{ $col['name'] }}">
                                    {{ $code }}
                                </th>
                            @endforeach

                            <th class="py-3 px-2 text-center whitespace-nowrap border-l border-slate-800/80" title="Respuestas Correctas">
                                <span class="text-emerald-400 font-bold">☑</span>
                            </th>
                            <th class="py-3 px-2 text-center whitespace-nowrap border-l border-slate-800/80" title="Respuestas Incorrectas">
                                <span class="text-rose-400 font-bold">☒</span>
                            </th>
                            <th class="py-3 px-2 text-center whitespace-nowrap border-l border-slate-800/80" title="Respuestas en Blanco">
                                <span class="text-slate-400 font-semibold text-[10px]">Blanco</span>
                            </th>
                            <th class="py-3 px-3 text-right whitespace-nowrap border-l border-slate-800/80 text-amber-300 font-bold">
                                PUNTS
                            </th>
                            <th class="py-3 px-3 text-center whitespace-nowrap border-l border-slate-800/80">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($results as $res)
                            @php
                                $subScores = $res->subject_scores;
                                $isGroupTab = request('group') && request('group') !== 'all';
                                $primaryRank = $isGroupTab ? $res->group_rank : $res->general_rank;
                            @endphp
                            <tr class="hover:bg-slate-800/40 transition-colors group">
                                <!-- Puesto Principal (Pto. Grupo en pestaña de grupo, Pto. Gen en tabla general) -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    @if($primaryRank === 1)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-300 font-bold text-[11px]">🥇 1</span>
                                    @elseif($primaryRank === 2)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-300/20 border border-slate-300/40 text-slate-200 font-bold text-[11px]">🥈 2</span>
                                    @elseif($primaryRank === 3)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-700/20 border border-amber-700/40 text-amber-400 font-bold text-[11px]">🥉 3</span>
                                    @else
                                        <span class="font-bold text-slate-300 text-xs">{{ $primaryRank }}°</span>
                                    @endif
                                </td>

                                @if($isGroupTab)
                                    <!-- Pto. Gen secundario -->
                                    <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                        <span class="text-xs text-slate-400 font-mono">{{ $res->general_rank }}°</span>
                                    </td>
                                    <!-- Pto. Carrera -->
                                    <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 font-semibold text-[11px] border border-slate-700">
                                            {{ $res->career_rank }}°
                                        </span>
                                    </td>
                                @else
                                    <!-- Pto. Carrera -->
                                    <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 font-semibold text-[11px] border border-slate-700">
                                            {{ $res->career_rank }}°
                                        </span>
                                    </td>
                                    <!-- Pto. Grupo -->
                                    <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                        <span class="text-xs text-slate-400 font-mono">{{ $res->group_rank }}°</span>
                                    </td>
                                @endif

                                <!-- DNI -->
                                <td class="py-2.5 px-3 font-mono text-slate-300 text-xs whitespace-nowrap">
                                    {{ $res->dni ?: '-' }}
                                </td>

                                <!-- Nombre Completo -->
                                <td class="py-2.5 px-4 whitespace-nowrap">
                                    <div class="font-bold text-white group-hover:text-indigo-400 transition-colors">
                                        {{ $res->full_name }}
                                    </div>
                                    @if($res->email)
                                        <span class="text-[10px] text-slate-500 block truncate max-w-[180px]">{{ $res->email }}</span>
                                    @endif
                                </td>

                                <!-- Carrera -->
                                <td class="py-2.5 px-4 whitespace-nowrap">
                                    <span class="text-slate-200 font-medium">{{ $res->career }}</span>
                                </td>

                                <!-- Grupo Académico -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    @if($res->academic_group === 'A')
                                        <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 font-semibold text-[10px]">Biomédicas (A)</span>
                                    @elseif($res->academic_group === 'BCD')
                                        <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 font-semibold text-[10px]">Letras (BCD)</span>
                                    @elseif($res->academic_group === 'EF')
                                        <span class="px-2 py-0.5 rounded-full bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 font-semibold text-[10px]">Ingenierías (EF)</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 border border-slate-700 font-semibold text-[10px]">{{ $res->academic_group }}</span>
                                    @endif
                                </td>

                                <!-- Asignaturas UNPRG (HV, HM, ARIT, etc.) -->
                                @foreach($subjectColumns ?? \App\Services\ScoringService::SUBJECT_COLUMNS as $code => $col)
                                    @php
                                        $val = $subScores[$code] ?? 0.0;
                                    @endphp
                                    <td class="py-2.5 px-2 text-right font-mono text-[11px] whitespace-nowrap border-l border-slate-800/40 {{ $val > 0 ? 'text-slate-200 font-medium' : ($val < 0 ? 'text-rose-400 font-bold' : 'text-slate-500') }}">
                                        {{ number_format($val, 4) }}
                                    </td>
                                @endforeach

                                <!-- Buenas -->
                                <td class="py-2.5 px-2 text-center whitespace-nowrap border-l border-slate-800/40">
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold text-[11px]">
                                        {{ $res->correct_count }}
                                    </span>
                                </td>

                                <!-- Malas -->
                                <td class="py-2.5 px-2 text-center whitespace-nowrap border-l border-slate-800/40">
                                    <span class="px-1.5 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20 font-bold text-[11px]">
                                        {{ $res->incorrect_count }}
                                    </span>
                                </td>

                                <!-- Blanco -->
                                <td class="py-2.5 px-2 text-center whitespace-nowrap border-l border-slate-800/40">
                                    <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700 font-bold text-[11px]">
                                        {{ $res->blank_count }}
                                    </span>
                                </td>

                                <!-- Puntaje Total -->
                                <td class="py-2.5 px-3 text-right whitespace-nowrap border-l border-slate-800/40">
                                    <span class="text-xs font-extrabold {{ $res->total_score < 0 ? 'text-rose-400 font-bold' : 'text-white bg-gradient-to-r from-emerald-400 to-cyan-300 bg-clip-text text-transparent' }}">
                                        {{ number_format($res->total_score, 4) }}
                                    </span>
                                </td>

                                <!-- Acción: Ver Desglose -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap border-l border-slate-800/40">
                                    <button onclick="openStudentModal({{ $res->id }})" class="px-2 py-1 text-[11px] font-semibold text-indigo-300 hover:text-white bg-indigo-500/10 hover:bg-indigo-600 rounded-lg border border-indigo-500/20 transition-all inline-flex items-center gap-1">
                                        <i class="fa-solid fa-list-check text-[10px]"></i>
                                        <span>Examen</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($results->hasPages())
                <div class="p-4 border-t border-slate-800 bg-slate-900/40">
                    {{ $results->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        @endif
    </div>
</div>

<!-- MODAL: Subir Archivo de Respuestas -->
<div id="modal-upload-responses" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl relative max-h-[92vh] flex flex-col animate-scale-up">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <i class="fa-solid fa-file-excel"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Importar Respuestas de Alumnos</h3>
                    <p class="text-xs text-slate-400">Puedes subir los 3 archivos a la vez o uno por uno</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-upload-responses').classList.add('hidden')" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="mt-4 overflow-y-auto flex-1 pr-1 space-y-4">
            <!-- Opción 1: Subir los 3 grupos a la vez (Lote) -->
            <form action="{{ route('exams.upload-responses', $exam) }}" method="POST" enctype="multipart/form-data" class="p-4 rounded-xl bg-slate-950/60 border border-emerald-500/20 space-y-3">
                @csrf
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <span class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-bolt"></i>
                        <span>Opción A: Subir los 3 Archivos de Respuestas a la vez</span>
                    </span>
                    <span class="text-[10px] text-slate-400">Recomendado</span>
                </div>

                <div class="space-y-2 text-xs">
                    <div>
                        <label class="block text-[11px] font-semibold text-rose-400 mb-1">1. Respuestas Grupo A (Biomédicas)</label>
                        <input type="file" name="responses_file_a" accept=".xlsx,.xls,.csv" class="w-full text-xs text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-rose-500/20 file:text-rose-300 hover:file:bg-rose-500/30">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-amber-400 mb-1">2. Respuestas Grupo BCD (Letras)</label>
                        <input type="file" name="responses_file_bcd" accept=".xlsx,.xls,.csv" class="w-full text-xs text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-500/20 file:text-amber-300 hover:file:bg-amber-500/30">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-cyan-400 mb-1">3. Respuestas Grupo EF (Ingenierías)</label>
                        <input type="file" name="responses_file_ef" accept=".xlsx,.xls,.csv" class="w-full text-xs text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-cyan-500/20 file:text-cyan-300 hover:file:bg-cyan-500/30">
                    </div>
                </div>

                <div class="pt-2 text-right">
                    <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg shadow-lg shadow-emerald-500/20 transition-all inline-flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Procesar los 3 Grupos a la Vez</span>
                    </button>
                </div>
            </form>

            <!-- Opción 2: Subir un solo archivo individual -->
            <form action="{{ route('exams.upload-responses', $exam) }}" method="POST" enctype="multipart/form-data" class="p-4 rounded-xl bg-slate-950/40 border border-slate-800 space-y-3">
                @csrf
                <div class="border-b border-slate-800 pb-2">
                    <span class="text-xs font-bold text-slate-300">Opción B: Subir solo 1 grupo específico</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <label class="flex flex-col items-center p-2 rounded-xl border border-slate-700 bg-slate-900 cursor-pointer has-[:checked]:border-rose-500 has-[:checked]:bg-rose-500/10 text-center">
                        <input type="radio" name="academic_group" value="A" class="sr-only" checked>
                        <span class="text-xs font-bold text-rose-400">Biomédicas (A)</span>
                    </label>
                    <label class="flex flex-col items-center p-2 rounded-xl border border-slate-700 bg-slate-900 cursor-pointer has-[:checked]:border-amber-500 has-[:checked]:bg-amber-500/10 text-center">
                        <input type="radio" name="academic_group" value="BCD" class="sr-only">
                        <span class="text-xs font-bold text-amber-400">Letras (BCD)</span>
                    </label>
                    <label class="flex flex-col items-center p-2 rounded-xl border border-slate-700 bg-slate-900 cursor-pointer has-[:checked]:border-cyan-500 has-[:checked]:bg-cyan-500/10 text-center">
                        <input type="radio" name="academic_group" value="EF" class="sr-only">
                        <span class="text-xs font-bold text-cyan-400">Ingenierías (EF)</span>
                    </label>
                </div>

                <div>
                    <input type="file" name="responses_file" accept=".xlsx,.xls,.csv" required class="w-full text-xs text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white hover:file:bg-slate-700">
                </div>

                <div class="pt-1 text-right">
                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white bg-slate-800 hover:bg-slate-700 rounded-lg border border-slate-700 transition-all inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-upload"></i>
                        <span>Procesar Este Grupo</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Configurar Puntajes por Pregunta -->
<div id="modal-scoring-rules" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-5xl w-full p-6 shadow-2xl relative max-h-[92vh] flex flex-col">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400">
                    <i class="fa-solid fa-calculator"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Puntajes por Pregunta Correcta</h3>
                    <p class="text-xs text-slate-400">Penalidad: {{ number_format($exam->incorrect_penalty, 4) }} | Blanco: {{ number_format($exam->blank_score, 4) }}</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-scoring-rules').classList.add('hidden')" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('exams.scoring-rules.update', $exam) }}" method="POST" class="mt-4 overflow-y-auto flex-1 pr-1">
            @csrf
            <div class="overflow-x-auto border border-slate-800 rounded-xl">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-950 text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-3 min-w-52">Bloque</th>
                            @foreach($scoringGroups as $groupCode => $groupLabel)
                                <th class="py-3 px-3 min-w-44 text-center">{{ $groupLabel }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 bg-slate-900/50">
                        @foreach($scoringCategories as $categoryCode => $categoryLabel)
                            <tr>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-200">{{ $categoryLabel }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $categoryCode }}</div>
                                </td>
                                @foreach($scoringGroups as $groupCode => $groupLabel)
                                    <td class="py-3 px-3">
                                        <input type="number" step="0.0001" min="0" max="1000" name="rules[{{ $groupCode }}][{{ $categoryCode }}]" value="{{ number_format($scoringRules[$groupCode][$categoryCode] ?? 0, 4, '.', '') }}" class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-white text-right font-mono focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-scoring-rules').classList.add('hidden')" class="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">Cancelar</button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-cyan-600 hover:bg-cyan-500 rounded-lg shadow-lg shadow-cyan-500/20 transition-all inline-flex items-center gap-2">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    <span>Guardar y Recalcular</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Ver / Subir Claves Oficiales -->
<div id="modal-keys" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-4xl w-full p-6 shadow-2xl relative max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Claves Oficiales por Grupo Académico</h3>
                    <p class="text-xs text-slate-400">
                        Total registradas:
                        <strong class="text-rose-400">A: {{ $keysByGroup['A'] }}</strong> |
                        <strong class="text-amber-400">BCD: {{ $keysByGroup['BCD'] }}</strong> |
                        <strong class="text-cyan-400">EF: {{ $keysByGroup['EF'] }}</strong>
                    </p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-keys').classList.add('hidden')" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Formulario para subir claves (en lote o individual) -->
        <div class="my-4 p-4 rounded-xl bg-slate-950/60 border border-slate-800 flex-shrink-0 space-y-3">
            <!-- Subir en Lote los 3 archivos de claves -->
            <form action="{{ route('exams.upload-keys', $exam) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div class="flex items-center justify-between border-b border-slate-800 pb-1.5">
                    <span class="text-xs font-bold text-amber-400">Subir Claves de los 3 Grupos a la Vez (o individual)</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <label class="block text-[11px] font-semibold text-rose-400 mb-1">Claves Biomédicas (A)</label>
                        <input type="file" name="keys_file_a" accept=".xlsx,.xls,.csv" class="w-full text-[11px] text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:bg-rose-500/20 file:text-rose-300 hover:file:bg-rose-500/30">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-amber-400 mb-1">Claves Letras (BCD)</label>
                        <input type="file" name="keys_file_bcd" accept=".xlsx,.xls,.csv" class="w-full text-[11px] text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:bg-amber-500/20 file:text-amber-300 hover:file:bg-amber-500/30">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-cyan-400 mb-1">Claves Ingenierías (EF)</label>
                        <input type="file" name="keys_file_ef" accept=".xlsx,.xls,.csv" class="w-full text-[11px] text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:bg-cyan-500/20 file:text-cyan-300 hover:file:bg-cyan-500/30">
                    </div>
                </div>
                <div class="text-right pt-1">
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-500 rounded-lg shadow-md shadow-amber-500/20 transition-all inline-flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Guardar Claves Seleccionadas</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Pestañas de filtrado de claves en modal -->
        <div class="flex items-center gap-2 mb-3 flex-shrink-0" id="keys-tab-bar">
            <button type="button" onclick="filterKeysTable('ALL')" id="key-tab-ALL" class="px-3 py-1 text-xs font-bold rounded-lg bg-indigo-600 text-white transition-all">
                Todas ({{ $answerKeys->count() }})
            </button>
            <button type="button" onclick="filterKeysTable('A')" id="key-tab-A" class="px-3 py-1 text-xs font-semibold rounded-lg bg-slate-800 text-rose-400 hover:bg-slate-700 transition-all">
                Biomédicas A ({{ $keysByGroup['A'] }})
            </button>
            <button type="button" onclick="filterKeysTable('BCD')" id="key-tab-BCD" class="px-3 py-1 text-xs font-semibold rounded-lg bg-slate-800 text-amber-400 hover:bg-slate-700 transition-all">
                Letras BCD ({{ $keysByGroup['BCD'] }})
            </button>
            <button type="button" onclick="filterKeysTable('EF')" id="key-tab-EF" class="px-3 py-1 text-xs font-semibold rounded-lg bg-slate-800 text-cyan-400 hover:bg-slate-700 transition-all">
                Ingenierías EF ({{ $keysByGroup['EF'] }})
            </button>
        </div>

        <!-- Lista de Claves Registradas -->
        <div class="overflow-y-auto flex-1 divide-y divide-slate-800 border border-slate-800 rounded-xl">
            @if($answerKeys->isEmpty())
                <div class="p-8 text-center text-xs text-slate-400">
                    No se han registrado claves oficiales todavía. Sube los archivos Excel de claves por grupo arriba.
                </div>
            @else
                <table class="w-full text-left text-xs" id="table-keys-list">
                    <thead class="bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3 text-center w-14">N°</th>
                            <th class="py-2.5 px-3 text-center w-24">Grupo</th>
                            <th class="py-2.5 px-3">Asignatura / Área</th>
                            <th class="py-2.5 px-3 text-center w-20">Clave</th>
                            <th class="py-2.5 px-3 text-center w-24">Anulada</th>
                            <th class="py-2.5 px-3">Justificación / Detalle</th>
                            <th class="py-2.5 px-3 text-center w-24">Guardar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 bg-slate-900/50">
                        @foreach($answerKeys as $key)
                            <tr class="hover:bg-slate-800/40 key-row" data-group="{{ $key->academic_group }}" data-key-id="{{ $key->id }}">
                                <td class="py-2 px-3 text-center font-bold text-slate-300">{{ $key->question_number }}</td>
                                <td class="py-2 px-3 text-center whitespace-nowrap">
                                    @if($key->academic_group === 'A')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">A</span>
                                    @elseif($key->academic_group === 'BCD')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">BCD</span>
                                    @elseif($key->academic_group === 'EF')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">EF</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">Gen</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-slate-300">
                                    <input form="answer-key-form-{{ $key->id }}" type="text" name="subject" value="{{ $key->subject ?: 'General' }}" class="w-full min-w-40 px-2 py-1.5 bg-slate-950 border border-slate-700 rounded-lg text-[11px] text-slate-200 focus:outline-none focus:border-amber-500">
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <input form="answer-key-form-{{ $key->id }}" type="text" name="correct_key" value="{{ $key->correct_key }}" maxlength="5" class="w-16 px-2 py-1.5 bg-slate-950 border border-slate-700 rounded-lg text-xs text-amber-300 text-center font-bold uppercase focus:outline-none focus:border-amber-500">
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <label class="inline-flex items-center justify-center">
                                        <input form="answer-key-form-{{ $key->id }}" type="checkbox" name="is_annulled" value="1" {{ $key->is_annulled ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-500">
                                    </label>
                                </td>
                                <td class="py-2 px-3 text-slate-400 text-[11px]">
                                    <input form="answer-key-form-{{ $key->id }}" type="text" name="explanation" value="{{ $key->explanation }}" placeholder="Opcional" class="w-full min-w-56 px-2 py-1.5 bg-slate-950 border border-slate-700 rounded-lg text-[11px] text-slate-300 placeholder-slate-600 focus:outline-none focus:border-amber-500">
                                </td>
                                <td class="py-2 px-3 text-center">
                                    <form id="answer-key-form-{{ $key->id }}" action="{{ route('exams.answer-keys.update', ['exam' => $exam, 'answerKey' => $key]) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-2.5 py-1.5 text-[11px] font-semibold text-white bg-amber-600 hover:bg-amber-500 rounded-lg transition-colors">
                                            Guardar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

<!-- MODAL: Detalle de Examen del Estudiante (Desglose de 100 Preguntas) -->
<div id="modal-student-detail" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-4xl w-full p-6 shadow-2xl relative max-h-[92vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <div>
                    <h3 id="modal-student-name" class="text-base font-bold text-white">Detalle de Respuestas</h3>
                    <p id="modal-student-subtitle" class="text-xs text-slate-400">Cargando información...</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-student-detail').classList.add('hidden')" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Student Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-4 flex-shrink-0" id="modal-student-metrics">
            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 text-center">
                <span class="text-[10px] text-slate-400 block uppercase font-semibold">Puntaje Total</span>
                <span id="metric-total-score" class="text-lg font-extrabold text-white">0.0000</span>
            </div>
            <div class="p-3 rounded-xl bg-emerald-500/5 border border-emerald-500/20 text-center">
                <span class="text-[10px] text-emerald-400 block uppercase font-semibold">Correctas</span>
                <span id="metric-correct" class="text-lg font-extrabold text-emerald-300">0</span>
            </div>
            <div class="p-3 rounded-xl bg-rose-500/5 border border-rose-500/20 text-center">
                <span class="text-[10px] text-rose-400 block uppercase font-semibold">Incorrectas</span>
                <span id="metric-incorrect" class="text-lg font-extrabold text-rose-300">0</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-800/40 border border-slate-700 text-center">
                <span class="text-[10px] text-slate-400 block uppercase font-semibold">En Blanco</span>
                <span id="metric-blank" class="text-lg font-extrabold text-slate-300">0</span>
            </div>
        </div>

        <!-- Question Grid / List -->
        <div class="overflow-y-auto flex-1 pr-1">
            <div id="modal-questions-grid" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-2.5">
                <!-- Se poblará vía JavaScript -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function openStudentModal(studentId) {
        const modal = document.getElementById('modal-student-detail');
        const nameEl = document.getElementById('modal-student-name');
        const subEl = document.getElementById('modal-student-subtitle');
        const grid = document.getElementById('modal-questions-grid');

        nameEl.innerText = 'Cargando...';
        subEl.innerText = 'Por favor espere';
        grid.innerHTML = '<div class="col-span-full py-8 text-center text-xs text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Cargando detalle del examen...</div>';

        modal.classList.remove('hidden');

        try {
            const response = await fetch(`/exams/{{ $exam->id }}/student/${studentId}`);
            const data = await response.json();
            const student = data.student;
            const details = data.details || {};

            nameEl.innerText = student.full_name;
            subEl.innerText = `DNI: ${student.dni || '-'} | Carrera: ${student.career} | Grupo: ${student.group_label || student.academic_group} | Puesto Gen: ${student.general_rank}°`;

            const totalScoreVal = parseFloat(student.total_score);
            const totalScoreEl = document.getElementById('metric-total-score');
            totalScoreEl.innerText = totalScoreVal.toFixed(4);
            totalScoreEl.className = totalScoreVal < 0 ? 'text-lg font-extrabold text-rose-400' : 'text-lg font-extrabold text-white';
            document.getElementById('metric-correct').innerText = student.correct_count;
            document.getElementById('metric-incorrect').innerText = student.incorrect_count;
            document.getElementById('metric-blank').innerText = student.blank_count;

            grid.innerHTML = '';

            const keys = Object.keys(details).map(Number).sort((a, b) => a - b);
            keys.forEach(qNum => {
                const item = details[qNum];
                const card = document.createElement('div');

                let borderClass = 'border-slate-800 bg-slate-950/40 text-slate-400';
                let icon = '<i class="fa-solid fa-minus text-slate-500"></i>';
                let badgeColor = 'bg-slate-800 text-slate-400';

                if (item.status === 'correct' || item.status === 'annulled') {
                    borderClass = 'border-emerald-500/30 bg-emerald-500/5 text-emerald-300';
                    icon = '<i class="fa-solid fa-check text-emerald-400"></i>';
                    badgeColor = 'bg-emerald-500/20 text-emerald-300';
                } else if (item.status === 'incorrect') {
                    borderClass = 'border-rose-500/30 bg-rose-500/5 text-rose-300';
                    icon = '<i class="fa-solid fa-xmark text-rose-400"></i>';
                    badgeColor = 'bg-rose-500/20 text-rose-300';
                }

                card.className = `p-2.5 rounded-xl border ${borderClass} flex flex-col justify-between text-xs transition-all`;
                card.innerHTML = `
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-[11px]">Preg. ${item.question_number}</span>
                        <span>${icon}</span>
                    </div>
                    <div class="text-[10px] text-slate-400 truncate mb-1" title="${item.subject}">${item.subject}</div>
                    <div class="flex items-center justify-between text-[11px] font-mono mt-1 pt-1 border-t border-slate-800/60">
                        <span>Marcó: <strong class="text-white">${item.given_answer || '-'}</strong></span>
                        <span>Clave: <strong class="text-amber-400">${item.correct_key || '-'}</strong></span>
                    </div>
                    <div class="text-right text-[10px] font-bold mt-1 ${item.points > 0 ? 'text-emerald-400' : (item.points < 0 ? 'text-rose-400' : 'text-slate-500')}">
                        ${item.points > 0 ? '+' : ''}${item.points.toFixed(4)} pts
                    </div>
                `;
                grid.appendChild(card);
            });

        } catch (error) {
            grid.innerHTML = '<div class="col-span-full py-8 text-center text-xs text-rose-400">Error al cargar el detalle del alumno.</div>';
        }
    }

    function filterKeysTable(group) {
        const rows = document.querySelectorAll('.key-row');
        rows.forEach(row => {
            const rowGroup = row.getAttribute('data-group');
            if (group === 'ALL' || rowGroup === group || rowGroup === 'ALL') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // Update active tab buttons
        ['ALL', 'A', 'BCD', 'EF'].forEach(g => {
            const btn = document.getElementById(`key-tab-${g}`);
            if (!btn) return;
            if (g === group) {
                btn.className = 'px-3 py-1 text-xs font-bold rounded-lg bg-indigo-600 text-white transition-all';
            } else {
                let textCol = 'text-slate-300';
                if (g === 'A') textCol = 'text-rose-400';
                if (g === 'BCD') textCol = 'text-amber-400';
                if (g === 'EF') textCol = 'text-cyan-400';
                btn.className = `px-3 py-1 text-xs font-semibold rounded-lg bg-slate-800 ${textCol} hover:bg-slate-700 transition-all`;
            }
        });
    }

    function toggleExportMenu() {
        const menu = document.getElementById('export-dropdown-menu');
        if (menu) {
            menu.classList.toggle('hidden');
        }
    }

    // Cerrar dropdown si se hace click fuera
    window.addEventListener('click', function(e) {
        const wrapper = document.getElementById('export-dropdown-wrapper');
        const menu = document.getElementById('export-dropdown-menu');
        if (wrapper && menu && !wrapper.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
</script>
@endsection
