@extends('layout')
@section('content')
<form method="POST" action="{{ $participant->exists ? route('participants.update', $participant) : route('participants.store') }}" class="bg-white p-4 rounded shadow max-w-lg space-y-3">
  @csrf @if($participant->exists) @method('PUT') @endif
  <h1 class="text-xl font-bold">{{ $participant->exists ? 'Edit' : 'Tambah' }} Peserta</h1>
  <input name="name" value="{{ old('name', $participant->name) }}" placeholder="Nama" class="w-full border rounded p-2" required>
  <input name="nip" value="{{ old('nip', $participant->nip) }}" placeholder="NIP" class="w-full border rounded p-2">
  <input name="kab_kota" value="{{ old('kab_kota', $participant->kab_kota) }}" placeholder="Kab/Kota" class="w-full border rounded p-2">
  <input name="email" type="email" value="{{ old('email', $participant->email) }}" placeholder="Email" class="w-full border rounded p-2">
  <button class="bg-slate-900 text-white px-4 py-2 rounded">Simpan</button>
  <a href="{{ route('participants.index') }}" class="px-3">Batal</a>
</form>
@endsection
