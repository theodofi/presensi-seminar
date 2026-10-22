@extends('layout')
@section('title', 'Sesi')
@section('content')
<div class="flex items-center mb-4"><h1 class="text-xl font-bold mr-auto">Pengaturan Sesi ({{ $sessions->count() }})</h1>
<form method="POST" action="{{ route('sessions.store') }}">@csrf<button class="bg-slate-900 text-white px-3 py-2 rounded">+ Tambah Sesi</button></form></div>
<p class="text-sm text-slate-600 mb-3">Mode <b>Auto</b> = terbuka otomatis di antara jam buka–tutup. <b>Buka</b>/<b>Tutup</b> = paksa manual.</p>
<div id="list" class="space-y-3">
@foreach($sessions as $s)
  <div class="bg-white p-4 rounded shadow" data-id="{{ $s->id }}" draggable="true">
    <form method="POST" action="{{ route('sessions.update', $s) }}" class="grid md:grid-cols-12 gap-2 items-end">
      @csrf @method('PUT')
      <div class="md:col-span-1 font-bold text-lg"><span class="cursor-move text-slate-400 select-none" title="Seret untuk mengubah urutan">⠿</span> #<span class="num">{{ $s->number }}</span></div>
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
<script>
const list = document.getElementById('list');
const csrf = document.querySelector('meta[name=csrf-token]').content;
let dragged = null;
list.addEventListener('dragstart', e => {
  if (e.target.closest('input, textarea')) { e.preventDefault(); return; }
  dragged = e.target.closest('[data-id]');
  if (dragged) { dragged.classList.add('opacity-50'); e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', dragged.dataset.id); }
});
list.addEventListener('dragover', e => {
  if (!dragged) return;
  e.preventDefault();
  const over = e.target.closest('[data-id]');
  if (!over || over === dragged) return;
  const r = over.getBoundingClientRect();
  list.insertBefore(dragged, e.clientY < r.top + r.height / 2 ? over : over.nextSibling);
});
list.addEventListener('dragend', async () => {
  if (!dragged) return;
  dragged.classList.remove('opacity-50'); dragged = null;
  const items = [...list.querySelectorAll('[data-id]')];
  items.forEach((el, i) => el.querySelector('.num').textContent = i + 1);
  try {
    const r = await fetch('{{ route('sessions.reorder', [], false) }}', {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
      body: JSON.stringify({ids: items.map(el => +el.dataset.id)})
    });
    if (!r.ok) throw new Error();
  } catch (e) { alert('Gagal menyimpan urutan. Halaman akan dimuat ulang.'); location.reload(); }
});
</script>
@endsection
