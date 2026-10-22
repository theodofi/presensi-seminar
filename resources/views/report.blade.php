@extends('layout')
@section('title', 'Rekap')
@section('content')
<div class="flex items-center mb-4"><h1 class="text-xl font-bold mr-auto">Rekap Kehadiran</h1>
<a href="{{ route('report.export') }}" class="bg-green-600 text-white px-3 py-2 rounded">Export CSV</a></div>
<div class="bg-white rounded shadow overflow-x-auto">
<table class="w-full text-sm">
  <thead class="bg-slate-50"><tr><th class="p-2 text-left">Nama</th><th class="text-left">NIP</th><th class="text-left">Kab/Kota</th>
    @foreach($sessions as $s)<th class="p-2">S{{ $s->number }}</th>@endforeach<th class="p-2">Total</th></tr></thead>
  <tbody>
  @foreach($participants as $p)
    @php $n = 0; @endphp
    <tr class="border-t"><td class="p-2">{{ $p->name }}</td><td>{{ $p->nip }}</td><td>{{ $p->kab_kota }}</td>
    @foreach($sessions as $s)
      @php $a = $map[$p->id][$s->id] ?? null; $a && $n++; @endphp
      <td class="text-center {{ $a ? 'text-green-700' : 'text-slate-300' }}" title="{{ $a?->scanned_at }}">{{ $a ? '✔' : '–' }}</td>
    @endforeach
    <td class="text-center font-bold">{{ $n }}</td></tr>
  @endforeach
  </tbody>
</table></div>
@endsection
