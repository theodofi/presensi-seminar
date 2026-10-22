@extends('layout')
@section('title', 'Peserta')
@section('content')
<div class="flex flex-wrap gap-2 items-center mb-4">
  <h1 class="text-xl font-bold mr-auto">Peserta ({{ $participants->total() }})</h1>
  <form class="flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="Cari…" class="border rounded p-2"><button class="bg-slate-700 text-white px-3 rounded">Cari</button></form>
  <a href="{{ route('participants.create') }}" class="bg-slate-900 text-white px-3 py-2 rounded">+ Tambah</a>
  <a href="{{ route('participants.cards') }}" class="bg-blue-600 text-white px-3 py-2 rounded">Download PDF semua ID Card</a>
</div>
<form method="POST" action="{{ route('participants.import') }}" enctype="multipart/form-data" class="bg-white p-3 rounded shadow mb-4 flex flex-wrap gap-2 items-center text-sm">
  @csrf <span>Import CSV (kolom: nama, nip, kab/kota):</span>
  <input type="file" name="file" accept=".csv,.txt" required><button class="bg-slate-700 text-white px-3 py-1 rounded">Import</button>
</form>
<div class="bg-white rounded shadow overflow-x-auto">
<table class="w-full text-sm">
  <thead class="bg-slate-50 text-left"><tr><th class="p-2">Kode</th><th>Nama</th><th>NIP</th><th>Kab/Kota</th><th>Hadir</th><th></th></tr></thead>
  <tbody>
  @foreach($participants as $p)
    <tr class="border-t">
      <td class="p-2 font-mono">{{ $p->code }}</td><td>{{ $p->name }}</td><td>{{ $p->nip }}</td><td>{{ $p->kab_kota }}</td><td>{{ $p->attendances_count }}/{{ $sessionCount }}</td>
      <td class="text-right p-2 space-x-2">
        <a class="text-blue-600" href="{{ route('participants.cards', ['ids' => $p->id]) }}">PDF</a>
        <a class="text-blue-600" href="{{ route('participants.edit', $p) }}">Edit</a>
        <form method="POST" action="{{ route('participants.destroy', $p) }}" class="inline" onsubmit="return confirm('Hapus peserta?')">@csrf @method('DELETE')<button class="text-red-600">Hapus</button></form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table></div>
<div class="mt-4">{{ $participants->links() }}</div>
@endsection
