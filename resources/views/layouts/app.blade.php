<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Revisión de Simulacros UNPRG') - Calificador Automático</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased flex flex-col min-h-screen selection:bg-indigo-500 selection:text-white">
    <!-- Navigation Header -->
    <header class="sticky top-0 z-40 glassmorphism border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo & Brand -->
                <a href="{{ route('exams.index') }}" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-400 p-0.5 shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-200">
                        <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                            <i class="fa-solid fa-graduation-cap text-indigo-400 text-lg"></i>
                        </div>
                    </div>
                    <div>
                        <span class="text-lg font-bold bg-gradient-to-r from-white via-slate-100 to-indigo-300 bg-clip-text text-transparent">Simulacro UNPRG</span>
                        <span class="block text-[11px] font-medium text-slate-400 -mt-1">Calificador de Respuestas Excel</span>
                    </div>
                </a>

                <!-- Quick Navigation & Actions -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('exams.index') }}" class="px-3.5 py-2 text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/60 rounded-lg transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-slate-400"></i>
                        <span>Simulacros</span>
                    </a>
                    @auth
                        <span class="hidden md:inline text-xs text-slate-500 max-w-[180px] truncate">{{ auth()->user()->email }}</span>
                    @endauth
                    <button onclick="document.getElementById('modal-new-exam').classList.remove('hidden')" class="px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 rounded-lg shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/30 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Nuevo Simulacro</span>
                    </button>
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="px-3 py-2 text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800/60 rounded-lg transition-colors">
                                Salir
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 relative z-10 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 flex items-center justify-between shadow-lg shadow-emerald-500/5 animate-fade-in">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-400 text-lg"></i>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-400/60 hover:text-emerald-300 text-sm">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 shadow-lg shadow-rose-500/5">
                <div class="flex items-center gap-3 mb-1">
                    <i class="fa-solid fa-circle-exclamation text-rose-400 text-lg"></i>
                    <span class="text-sm font-semibold">Hubo un problema:</span>
                </div>
                @if(session('error'))
                    <p class="text-xs text-rose-300/80 ml-7">{{ session('error') }}</p>
                @endif
                @if($errors->any())
                    <ul class="list-disc list-inside text-xs text-rose-300/80 ml-7 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="relative z-10 glassmorphism border-t border-slate-800/80 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-calculator text-indigo-400"></i>
                <span>Sistema de Revisión y Calificación Ponderada de Simulacros UNPRG</span>
            </div>
            <div>
                <span>Grupos Académicos: Biomédicas (A) • Letras (B, C, D) • Ingenierías (E, F)</span>
            </div>
        </div>
    </footer>

    <!-- Global Modal: Create New Exam -->
    <div id="modal-new-exam" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm hidden">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl relative animate-scale-up max-h-[92vh] flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                        <i class="fa-solid fa-file-circle-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Crear Nuevo Simulacro</h3>
                        <p class="text-xs text-slate-400">Sube los 3 archivos de claves y los 3 de respuestas a la vez</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modal-new-exam').classList.add('hidden')" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('exams.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4 overflow-y-auto flex-1 pr-1">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Nombre del Simulacro *</label>
                    <input type="text" name="title" required placeholder="Ej: SIMULACRO 8° GENERAL (UNPRG)" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Penalidad por Incorrecta</label>
                        <input type="number" step="0.0001" name="incorrect_penalty" value="-1.1250" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Estándar UNPRG: -1.1250 pts</span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">Total de Preguntas</label>
                        <input type="number" name="total_questions" value="100" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Generalmente 100 preguntas</span>
                    </div>
                </div>

                <!-- SECCIÓN MULTI-GRUPO: 3 Grupos de Claves y Respuestas -->
                <div class="space-y-3 pt-2 border-t border-slate-800/80">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-200 uppercase tracking-wider">Archivos de los 3 Grupos (Opcional)</span>
                        <span class="text-[11px] text-slate-400">Puedes adjuntarlos ahora o dentro del panel</span>
                    </div>

                    <!-- 1. GRUPO A: BIOMÉDICAS -->
                    <div class="p-3 rounded-xl bg-slate-950/60 border border-rose-500/20 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-rose-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-heart-pulse"></i>
                                <span>1. Grupo A (Biomédicas / Ciencias Médicas)</span>
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-1">Claves Grupo A (.xlsx)</label>
                                <input type="file" name="keys_file_a" accept=".xlsx,.xls,.csv" class="w-full text-[11px] text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:bg-rose-500/20 file:text-rose-300 hover:file:bg-rose-500/30">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-1">Respuestas Alumnos Grupo A (.xlsx)</label>
                                <input type="file" name="responses_file_a" accept=".xlsx,.xls,.csv" class="w-full text-[11px] text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:bg-emerald-500/20 file:text-emerald-300 hover:file:bg-emerald-500/30">
                            </div>
                        </div>
                    </div>

                    <!-- 2. GRUPO BCD: LETRAS -->
                    <div class="p-3 rounded-xl bg-slate-950/60 border border-amber-500/20 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-book-open"></i>
                                <span>2. Grupo B, C, D (Letras / Humanidades / Sociales)</span>
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-1">Claves Grupo BCD (.xlsx)</label>
                                <input type="file" name="keys_file_bcd" accept=".xlsx,.xls,.csv" class="w-full text-[11px] text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:bg-amber-500/20 file:text-amber-300 hover:file:bg-amber-500/30">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-1">Respuestas Alumnos Grupo BCD (.xlsx)</label>
                                <input type="file" name="responses_file_bcd" accept=".xlsx,.xls,.csv" class="w-full text-[11px] text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:bg-emerald-500/20 file:text-emerald-300 hover:file:bg-emerald-500/30">
                            </div>
                        </div>
                    </div>

                    <!-- 3. GRUPO EF: INGENIERÍAS -->
                    <div class="p-3 rounded-xl bg-slate-950/60 border border-cyan-500/20 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-cyan-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-compass-drafting"></i>
                                <span>3. Grupo E, F (Ciencias e Ingenierías)</span>
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-1">Claves Grupo EF (.xlsx)</label>
                                <input type="file" name="keys_file_ef" accept=".xlsx,.xls,.csv" class="w-full text-[11px] text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:bg-cyan-500/20 file:text-cyan-300 hover:file:bg-cyan-500/30">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-1">Respuestas Alumnos Grupo EF (.xlsx)</label>
                                <input type="file" name="responses_file_ef" accept=".xlsx,.xls,.csv" class="w-full text-[11px] text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:bg-emerald-500/20 file:text-emerald-300 hover:file:bg-emerald-500/30">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-800 flex-shrink-0">
                    <button type="button" onclick="document.getElementById('modal-new-exam').classList.add('hidden')" class="px-4 py-2 text-xs font-medium text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">Cancelar</button>
                    <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-lg shadow-indigo-500/20 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-bolt"></i>
                        <span>Crear y Procesar Todo</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @yield('scripts')
</body>
</html>
