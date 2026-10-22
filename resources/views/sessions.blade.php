@extends('layout')
@section('title', 'Sesi')
@section('content')
<div class="flex items-center mb-4"><h1 class="text-xl font-bold mr-auto">Pengaturan Sesi ({{ $sessions->count() }})</h1>
<form method="POST" action="{{ route('sessions.store') }}">@csrf<button class="bg-slate-900 text-white px-3 py-2 rounded">+ Tambah Sesi</button></form></div>
<p class="text-sm text-slate-600 mb-3">Mode <b>Auto</b> = terbuka otomatis di antara jam buka–tutup. <b>Buka</b>/<b>Tutup</b> = paksa manual.</p>
<div class="space-y-3">
@foreach($sessions as $s)
  <div class="bg-white p-4 rounded shadow">
    <form method="POST" action="{{ route('sessions.update', $s) }}" class="grid md:grid-cols-12 gap-2 items-end">
      @csrf @method('PUT')
      <div class="md:col-span-1 font-bold text-lg">#{{ $s->number }}</div>
      <label class="md:col-span-4 text-sm">Nama<input name="name" value="{{ $s->name }}" class="w-full border rounded p-2" required></label>
      <label class="md:col-span-3 text-sm">Buka<input type="datetime-local" name="opens_at" value="{{ $s->opens_at?->format('Y-m-d\TH:i') }}" class="w-full border rounded p-2"></label>
      <label class="md:col-span-3 text-sm">Tutup<input type="datetime-local" name="closes_at" value="{{ $s->closes_at?->format('Y-m-d\TH:i') }}" class="w-full border rounded p-2"></label>
      <button class="md:col-span-1 bg-slate-900 text-white rounded p-2">Simpan</button>
    </form>
    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
      <span class="px-2 py-1 rounded {{ $s->isOpen() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $s->isOpen() ? 'TERBUKA' : 'TERTUTUP' }}</span>
      <span>Hadir: {{ $s->attendances_count }}</span>
      <form method="POST" action="{{ route('sessions.destroy', $s) }}" onsubmit="return confirm('Hapus sesi {{ $s->number }}? Data absensinya ikut terhapus.')">@csrf @method('DELETE')<button class="px-3 py-1 rounded border border-red-300 text-red-600">Hapus</button></form>
      <span class="ml-auto">Mode:</span>
      @foreach(['auto'=>'Auto','open'=>'Buka','closed'=>'Tutup'] as $m => $label)
        <form method="POST" action="{{ route('sessions.mode', $s) }}">@csrf
          <input type="hidden" name="mode" value="{{ $m }}">
          <button class="px-3 py-1 rounded border {{ $s->mode === $m ? 'bg-slate-900 text-white' : 'bg-white' }}">{{ $label }}</button>
        </form>
      @endforeach
    </div>
  </div>
@endforeach
</div>
@endsection
