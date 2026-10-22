<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Panitia</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">
<form method="POST" action="{{ route('login') }}" class="bg-white p-6 rounded shadow w-full max-w-sm space-y-4">
  @csrf
  <h1 class="text-xl font-bold">Login Panitia</h1>
  @if($errors->any())<div class="p-2 bg-red-100 text-red-800 rounded text-sm">{{ $errors->first() }}</div>@endif
  <input name="email" type="email" value="{{ old('email') }}" placeholder="Email" class="w-full border rounded p-2" required autofocus>
  <input name="password" type="password" placeholder="Password" class="w-full border rounded p-2" required>
  <button class="w-full bg-slate-900 text-white rounded p-2">Masuk</button>
</form></body></html>
