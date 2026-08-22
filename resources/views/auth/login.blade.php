<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso administrativo - Simulacro UNPRG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased flex items-center justify-center p-4">
    <main class="w-full max-w-md">
        <div class="glass-card rounded-2xl border border-slate-800 p-6 shadow-2xl">
            <div class="mb-6">
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 flex items-center justify-center mb-4">
                    <i class="fa-solid fa-graduation-cap text-xl"></i>
                </div>
                <h1 class="text-xl font-extrabold text-white">Acceso administrativo</h1>
                <p class="text-sm text-slate-400 mt-1">Ingresa para gestionar simulacros, importar respuestas y exportar resultados.</p>
            </div>

            @if($errors->any())
                <div class="mb-4 rounded-xl border border-rose-500/20 bg-rose-500/10 p-3 text-xs text-rose-200">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Correo</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Contraseña</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-indigo-500">
                    <span>Mantener sesión iniciada</span>
                </label>

                <button type="submit" class="w-full px-4 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 rounded-xl shadow-lg shadow-indigo-500/20 transition-colors">
                    Entrar
                </button>
            </form>
        </div>
    </main>
</body>
</html>
