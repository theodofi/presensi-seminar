<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Presensi')</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">
<nav class="bg-slate-900 text-white">
  <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center gap-4">
    <span class="font-bold">Absensi Seminar</span>
    <a class="hover:underline" href="{{ route('scan') }}">Scan</a>
    <a class="hover:underline" href="{{ route('sessions.index') }}">Sesi</a>
    <a class="hover:underline" href="{{ route('participants.index') }}">Peserta</a>
    <a class="hover:underline" href="{{ route('report') }}">Rekap</a>
    <a class="hover:underline" href="{{ route('card.edit') }}">Template Kartu</a>
    <form method="POST" action="{{ route('logout') }}" class="ml-auto">@csrf
      <button class="text-sm bg-slate-700 px-3 py-1 rounded">Keluar ({{ auth()->user()->name }})</button>
    </form>
  </div>
</nav>
<main class="max-w-6xl mx-auto p-4">
  @if(session('ok'))<div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('ok') }}</div>@endif
  @if($errors->any())<div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{ $errors->first() }}</div>@endif
  @yield('content')
</main>
</body>
</html>
