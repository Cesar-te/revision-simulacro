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
            <p class="text-xs text-slate-400 mt-1">
                Penalidad: <span class="text-rose-400 font-semibold">{{ $exam->incorrect_penalty }} pts</span> | 
                En blanco: <span class="text-slate-300 font-semibold">{{ $exam->blank_score }} pts</span> | 
                Claves registradas: <span class="text-amber-400 font-semibold">{{ $answerKeys->count() }} / {{ $exam->total_questions }}</span>
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Modal Claves -->
            <button onclick="document.getElementById('modal-keys').classList.remove('hidden')" class="px-3.5 py-2 text-xs font-semibold text-slate-200 bg-slate-800 hover:bg-slate-700 rounded-xl border border-slate-700 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-key text-amber-400"></i>
                <span>Claves Oficiales</span>
            </button>

            <!-- Modal Subir Respuestas -->
            <button onclick="document.getElementById('modal-upload-responses').classList.remove('hidden')" class="px-3.5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-md shadow-indigo-500/20 transition-all flex items-center gap-2">
                <i class="fa-solid fa-file-arrow-up"></i>
                <span>Importar Respuestas (Excel)</span>
            </button>

            <!-- Recalcular -->
            @if($totalStudents > 0)
                <form action="{{ route('exams.recalculate', $exam) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" title="Recalcular todos los puntajes según las claves vigentes" class="p-2 text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl border border-slate-700 transition-colors">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </form>

                <!-- Exportar Excel -->
                <a href="{{ route('exams.export', ['exam' => $exam, 'group' => request('group'), 'career' => request('career')]) }}" class="px-3.5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-xl shadow-md shadow-emerald-500/20 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Exportar Orden de Mérito</span>
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
                <span class="text-2xl font-extrabold text-slate-300">{{ number_format($minScore, 4) }}</span>
                <span class="text-xs text-slate-500">pts</span>
            </div>
        </div>

        <!-- Distribución Grupos -->
        <div class="glass-card rounded-2xl p-4 border border-slate-800 col-span-2 lg:col-span-1">
            <span class="text-[11px] font-semibold text-slate-400 block uppercase tracking-wider">Por Grupo</span>
            <div class="flex items-center gap-2 mt-1.5 text-xs font-semibold">
                <span class="text-rose-400" title="Biomédicas">A: {{ $groupsCount['A'] }}</span> •
                <span class="text-amber-400" title="Letras">BCD: {{ $groupsCount['BCD'] }}</span> •
                <span class="text-cyan-400" title="Ingenierías">EF: {{ $groupsCount['EF'] }}</span>
            </div>
        </div>
    </div>

    <!-- Filters and Search Bar -->
    <div class="glass-card rounded-2xl p-4 border border-slate-800">
        <form method="GET" action="{{ route('exams.show', $exam) }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <!-- Academic Group Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                <a href="{{ route('exams.show', ['exam' => $exam, 'career' => request('career'), 'search' => request('search')]) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ !request('group') || request('group') === 'all' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20' : 'bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-800' }}">
                    Todos los Grupos
                </a>
                <a href="{{ route('exams.show', ['exam' => $exam, 'group' => 'A', 'career' => request('career'), 'search' => request('search')]) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ request('group') === 'A' ? 'bg-rose-600 text-white shadow-md shadow-rose-500/20' : 'bg-slate-800/80 text-rose-400/80 hover:text-rose-300 hover:bg-slate-800' }}">
                    Grupo A (Biomédicas)
                </a>
                <a href="{{ route('exams.show', ['exam' => $exam, 'group' => 'BCD', 'career' => request('career'), 'search' => request('search')]) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ request('group') === 'BCD' ? 'bg-amber-600 text-white shadow-md shadow-amber-500/20' : 'bg-slate-800/80 text-amber-400/80 hover:text-amber-300 hover:bg-slate-800' }}">
                    Grupo B, C, D (Letras)
                </a>
                <a href="{{ route('exams.show', ['exam' => $exam, 'group' => 'EF', 'career' => request('career'), 'search' => request('search')]) }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-colors {{ request('group') === 'EF' ? 'bg-cyan-600 text-white shadow-md shadow-cyan-500/20' : 'bg-slate-800/80 text-cyan-400/80 hover:text-cyan-300 hover:bg-slate-800' }}">
                    Grupo E, F (Ingenierías)
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
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900/90 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4 text-center">Pto. Gen.</th>
                            <th class="py-3 px-4 text-center">Pto. Carr.</th>
                            <th class="py-3 px-4">DNI</th>
                            <th class="py-3 px-4">Postulante</th>
                            <th class="py-3 px-4">Carrera</th>
                            <th class="py-3 px-4 text-center">Grupo</th>
                            <th class="py-3 px-4 text-center" title="Respuestas Correctas">Buenas</th>
                            <th class="py-3 px-4 text-center" title="Respuestas Incorrectas">Malas</th>
                            <th class="py-3 px-4 text-center" title="Respuestas en Blanco">Blanco</th>
                            <th class="py-3 px-4 text-right">Puntaje Total</th>
                            <th class="py-3 px-4 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($results as $res)
                            <tr class="hover:bg-slate-800/40 transition-colors group">
                                <!-- Puesto General -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($res->general_rank === 1)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-300 font-bold text-xs">🥇 1</span>
                                    @elseif($res->general_rank === 2)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-300/20 border border-slate-300/40 text-slate-200 font-bold text-xs">🥈 2</span>
                                    @elseif($res->general_rank === 3)
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-700/20 border border-amber-700/40 text-amber-400 font-bold text-xs">🥉 3</span>
                                    @else
                                        <span class="font-bold text-slate-400">{{ $res->general_rank }}°</span>
                                    @endif
                                </td>

                                <!-- Puesto Carrera -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 font-semibold text-[11px] border border-slate-700">
                                        {{ $res->career_rank }}°
                                    </span>
                                </td>

                                <!-- DNI -->
                                <td class="py-3 px-4 font-mono text-slate-300 whitespace-nowrap">
                                    {{ $res->dni ?: '-' }}
                                </td>

                                <!-- Nombre Completo -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="font-bold text-white group-hover:text-indigo-400 transition-colors">
                                        {{ $res->full_name }}
                                    </div>
                                    @if($res->email)
                                        <span class="text-[11px] text-slate-500 block">{{ $res->email }}</span>
                                    @endif
                                </td>

                                <!-- Carrera -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="text-slate-200 font-medium">{{ $res->career }}</span>
                                </td>

                                <!-- Grupo Académico -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($res->academic_group === 'A')
                                        <span class="px-2.5 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 font-semibold text-[11px]">Biomédicas (A)</span>
                                    @elseif($res->academic_group === 'BCD')
                                        <span class="px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 font-semibold text-[11px]">Letras (BCD)</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 font-semibold text-[11px]">Ingenierías (EF)</span>
                                    @endif
                                </td>

                                <!-- Buenas -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold">
                                        {{ $res->correct_count }}
                                    </span>
                                </td>

                                <!-- Malas -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20 font-bold">
                                        {{ $res->incorrect_count }}
                                    </span>
                                </td>

                                <!-- Blanco -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700 font-bold">
                                        {{ $res->blank_count }}
                                    </span>
                                </td>

                                <!-- Puntaje Total -->
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <span class="text-sm font-extrabold text-white bg-gradient-to-r from-emerald-400 to-cyan-300 bg-clip-text text-transparent">
                                        {{ number_format($res->total_score, 4) }}
                                    </span>
                                </td>

                                <!-- Acción: Ver Desglose -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <button onclick="openStudentModal({{ $res->id }})" class="px-2.5 py-1 text-[11px] font-semibold text-indigo-300 hover:text-white bg-indigo-500/10 hover:bg-indigo-600 rounded-lg border border-indigo-500/20 transition-all flex items-center gap-1.5 mx-auto">
                                        <i class="fa-solid fa-list-check"></i>
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
                <div class="p-4 border-t border-slate-800">
                    {{ $results->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

<!-- MODAL: Subir Archivo de Respuestas -->
<div id="modal-upload-responses" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <i class="fa-solid fa-file-excel"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Importar Respuestas de Alumnos</h3>
                    <p class="text-xs text-slate-400">Sube el archivo Excel exportado de Google Forms o similar</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-upload-responses').classList.add('hidden')" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('exams.upload-responses', $exam) }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Archivo Excel (.xlsx, .xls)</label>
                <div class="relative border-2 border-dashed border-slate-700 hover:border-emerald-500/60 rounded-xl p-6 text-center bg-slate-950/50 transition-colors">
                    <input type="file" name="responses_file" accept=".xlsx,.xls,.csv" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="space-y-2">
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-emerald-400"></i>
                        <p class="text-xs text-slate-300 font-medium">Haz clic o arrastra aquí el archivo Excel de respuestas</p>
                        <p class="text-[11px] text-slate-500">Ej: SIMULACRO 7° (LETRAS) (respuestas).xlsx</p>
                    </div>
                </div>
            </div>

            <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('modal-upload-responses').classList.add('hidden')" class="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">Cancelar</button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-upload"></i>
                    <span>Procesar y Calificar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Ver / Subir Claves Oficiales -->
<div id="modal-keys" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-3xl w-full p-6 shadow-2xl relative max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white">Claves Oficiales del Simulacro</h3>
                    <p class="text-xs text-slate-400">{{ $answerKeys->count() }} claves registradas</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-keys').classList.add('hidden')" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Formulario para subir / actualizar archivo de claves -->
        <div class="my-4 p-4 rounded-xl bg-slate-950/60 border border-slate-800 flex-shrink-0">
            <form action="{{ route('exams.upload-keys', $exam) }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-center gap-3">
                @csrf
                <div class="flex-1 w-full">
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Cargar/Actualizar Excel de Claves (.xlsx)</label>
                    <input type="file" name="keys_file" accept=".xlsx,.xls,.csv" required class="w-full text-xs text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500">
                </div>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-500 rounded-lg shadow-md shadow-amber-500/20 transition-all flex items-center gap-2 flex-shrink-0">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Subir Claves</span>
                </button>
            </form>
        </div>

        <!-- Lista de Claves Registradas -->
        <div class="overflow-y-auto flex-1 divide-y divide-slate-800 border border-slate-800 rounded-xl">
            @if($answerKeys->isEmpty())
                <div class="p-8 text-center text-xs text-slate-400">
                    No se han registrado claves oficiales todavía. Sube el archivo Excel de claves arriba.
                </div>
            @else
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950 text-slate-400 sticky top-0 border-b border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3 text-center w-16">N°</th>
                            <th class="py-2.5 px-3">Asignatura / Área</th>
                            <th class="py-2.5 px-3 text-center w-20">Clave</th>
                            <th class="py-2.5 px-3">Justificación / Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 bg-slate-900/50">
                        @foreach($answerKeys as $key)
                            <tr class="hover:bg-slate-800/40">
                                <td class="py-2 px-3 text-center font-bold text-slate-300">{{ $key->question_number }}</td>
                                <td class="py-2 px-3 text-slate-300">{{ $key->subject ?: 'General' }}</td>
                                <td class="py-2 px-3 text-center">
                                    <span class="inline-block w-6 h-6 leading-6 rounded-md bg-amber-500/10 border border-amber-500/30 text-amber-300 font-bold">
                                        {{ $key->correct_key }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-slate-400 text-[11px]">{{ $key->explanation ?: '-' }}</td>
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

            document.getElementById('metric-total-score').innerText = parseFloat(student.total_score).toFixed(4);
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
</script>
@endsection
