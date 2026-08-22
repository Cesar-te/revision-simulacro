@extends('layouts.app')

@section('title', 'Listado de Simulacros')

@section('content')
<div class="space-y-8">
    <!-- Header Hero Banner -->
    <div class="glass-card rounded-3xl p-6 sm:p-8 relative overflow-hidden border border-slate-800/80">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold">
                    <i class="fa-solid fa-bolt text-indigo-400"></i>
                    <span>Calificación Automática Ponderada UNPRG</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Gestión y Revisión de Simulacros</h1>
                <p class="text-sm text-slate-400 max-w-2xl">
                    Importa respuestas desde archivos Excel, aplica automáticamente las ponderaciones por grupo académico (Biomédicas, Letras, Ingenierías), calcula penalidades y genera el orden de mérito listo para publicar.
                </p>
            </div>
            <button onclick="document.getElementById('modal-new-exam').classList.remove('hidden')" class="px-5 py-3 text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 rounded-xl shadow-xl shadow-indigo-500/25 hover:shadow-indigo-500/35 hover:-translate-y-0.5 transition-all flex items-center gap-2.5 flex-shrink-0">
                <i class="fa-solid fa-cloud-arrow-up text-base"></i>
                <span>Importar Nuevo Simulacro</span>
            </button>
        </div>
    </div>

    <!-- Ponderación Reference Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Grupo A -->
        <div class="glass-card rounded-2xl p-5 border-l-4 border-l-rose-500 hover:border-slate-700 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold px-2.5 py-1 rounded-md bg-rose-500/10 text-rose-400 border border-rose-500/20">GRUPO A</span>
                <i class="fa-solid fa-heart-pulse text-rose-400 text-lg"></i>
            </div>
            <h3 class="text-base font-bold text-white">Ciencias Médicas (Biomédicas)</h3>
            <p class="text-xs text-slate-400 mt-1">Medicina, Enfermería, Biología, etc.</p>
            <div class="mt-4 pt-3 border-t border-slate-800/80 space-y-1.5 text-xs text-slate-300">
                <div class="flex justify-between"><span class="text-slate-400">Verbal y Mate (40 preg):</span> <span class="font-semibold text-white">20.00 pts c/u</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Ciencias Naturales (32 preg):</span> <span class="font-semibold text-rose-300">25.00 pts c/u</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Ciencias Básicas (10 preg):</span> <span class="font-semibold text-white">14.3014 pts</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Humanidades (18 preg):</span> <span class="font-semibold text-white">14.2770 pts</span></div>
            </div>
        </div>

        <!-- Grupo B, C, D -->
        <div class="glass-card rounded-2xl p-5 border-l-4 border-l-amber-500 hover:border-slate-700 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold px-2.5 py-1 rounded-md bg-amber-500/10 text-amber-400 border border-amber-500/20">GRUPO B, C, D</span>
                <i class="fa-solid fa-book-open text-amber-400 text-lg"></i>
            </div>
            <h3 class="text-base font-bold text-white">Letras / Humanidades</h3>
            <p class="text-xs text-slate-400 mt-1">Derecho, Economía, Educación, Artes, etc.</p>
            <div class="mt-4 pt-3 border-t border-slate-800/80 space-y-1.5 text-xs text-slate-300">
                <div class="flex justify-between"><span class="text-slate-400">Verbal y Mate (40 preg):</span> <span class="font-semibold text-white">20.00 pts c/u</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Humanidades / Letras (34 preg):</span> <span class="font-semibold text-amber-300">23.5290 pts c/u</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Ciencias Básicas (15 preg):</span> <span class="font-semibold text-white">16.0012 pts</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Ciencias Naturales (11 preg):</span> <span class="font-semibold text-white">14.5450 pts</span></div>
            </div>
        </div>

        <!-- Grupo E, F -->
        <div class="glass-card rounded-2xl p-5 border-l-4 border-l-cyan-500 hover:border-slate-700 transition-all">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold px-2.5 py-1 rounded-md bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">GRUPO E, F</span>
                <i class="fa-solid fa-microchip text-cyan-400 text-lg"></i>
            </div>
            <h3 class="text-base font-bold text-white">Ciencias e Ingenierías</h3>
            <p class="text-xs text-slate-400 mt-1">Ing. Civil, Sistemas, Industrial, Agrícola, etc.</p>
            <div class="mt-4 pt-3 border-t border-slate-800/80 space-y-1.5 text-xs text-slate-300">
                <div class="flex justify-between"><span class="text-slate-400">Verbal y Mate (40 preg):</span> <span class="font-semibold text-white">20.00 pts c/u</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Ciencias Básicas (24 preg):</span> <span class="font-semibold text-cyan-300">22.2220 pts c/u</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Física y Química (12 preg):</span> <span class="font-semibold text-cyan-300">22.2220 pts c/u</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Humanidades (19 preg):</span> <span class="font-semibold text-white">17.6310 pts</span></div>
            </div>
        </div>
    </div>

    <!-- Section: Simulacros List -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-indigo-400"></i>
                <span>Simulacros Registrados ({{ $exams->count() }})</span>
            </h2>
        </div>

        @if($exams->isEmpty())
            <div class="glass-card rounded-2xl p-12 text-center border border-slate-800">
                <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <h3 class="text-base font-bold text-white">Aún no hay simulacros creados</h3>
                <p class="text-xs text-slate-400 max-w-md mx-auto mt-1 mb-6">
                    Comienza creando un simulacro para subir las claves oficiales y el Excel de respuestas de los estudiantes.
                </p>
                <button onclick="document.getElementById('modal-new-exam').classList.remove('hidden')" class="px-5 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-lg shadow-indigo-500/20 transition-all inline-flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i>
                    <span>Crear Primer Simulacro</span>
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($exams as $exam)
                    <div class="glass-card rounded-2xl p-6 border border-slate-800 hover:border-indigo-500/40 transition-all flex flex-col justify-between group">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                                    <i class="fa-solid fa-circle-question text-indigo-400 mr-1"></i> {{ $exam->total_questions }} Preguntas
                                </span>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                    Malas: {{ $exam->incorrect_penalty }} pts
                                </span>
                            </div>

                            <a href="{{ route('exams.show', $exam) }}" class="block">
                                <h3 class="text-lg font-bold text-white group-hover:text-indigo-400 transition-colors line-clamp-1">
                                    {{ $exam->title }}
                                </h3>
                            </a>
                            <p class="text-xs text-slate-400 mt-1 line-clamp-2">
                                {{ $exam->description ?: 'Simulacro de evaluación tipo admisión UNPRG.' }}
                            </p>

                            <div class="grid grid-cols-2 gap-3 mt-5 p-3 rounded-xl bg-slate-950/60 border border-slate-800/60 text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Evaluados</span>
                                    <span class="text-base font-bold text-white flex items-center gap-1.5 mt-0.5">
                                        <i class="fa-solid fa-users text-indigo-400 text-xs"></i>
                                        {{ $exam->student_results_count }} alumnos
                                    </span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[11px]">Claves registradas</span>
                                    <span class="text-base font-bold text-white flex items-center gap-1.5 mt-0.5">
                                        <i class="fa-solid fa-key text-amber-400 text-xs"></i>
                                        {{ $exam->answer_keys_count }} / {{ $exam->expected_answer_keys_count }}
                                    </span>
                                    @if($exam->answer_key_groups_count > 1)
                                        <span class="text-[10px] text-slate-500 block mt-0.5">
                                            {{ $exam->total_questions }} por grupo x {{ $exam->answer_key_groups_count }} grupos
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between gap-3">
                            <a href="{{ route('exams.show', $exam) }}" class="flex-1 px-4 py-2 text-xs font-semibold text-center text-white bg-indigo-600/90 hover:bg-indigo-600 rounded-lg transition-colors flex items-center justify-center gap-1.5">
                                <span>Ver Resultados</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                            
                            @if($exam->student_results_count > 0)
                                <a href="{{ route('exams.export', $exam) }}" title="Descargar Excel con Orden de Mérito" class="p-2 text-emerald-400 hover:text-emerald-300 hover:bg-emerald-500/10 rounded-lg transition-colors border border-emerald-500/20">
                                    <i class="fa-solid fa-file-excel text-sm"></i>
                                </a>
                            @endif

                            <form action="{{ route('exams.destroy', $exam) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este simulacro y sus resultados?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Eliminar Simulacro" class="p-2 text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded-lg transition-colors border border-rose-500/20">
                                    <i class="fa-solid fa-trash-can text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
