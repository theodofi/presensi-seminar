@extends('layout')
@section('title', 'Template Kartu')
@section('content')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap">
<style>
@font-face { font-family: 'GothamCard'; font-weight: bold; src: local('Gotham Bold'), local('Gotham-Bold'), url('{{ asset('fonts/Gotham-Bold.woff2') }}') format('woff2'), url('{{ asset('fonts/Gotham-Bold.otf') }}') format('opentype'), url('{{ asset('fonts/Gotham-Bold.ttf') }}') format('truetype'); }#preview div { font-family: 'GothamCard', 'Montserrat', Arial, sans-serif; }
</style>
<h1 class="text-xl font-bold mb-1">Template ID Card (80 × 110 mm)</h1>
<p class="text-sm text-slate-600 mb-3">Font teks di PDF: <b>Gotham Bold</b> bila file <code>public/fonts/Gotham-Bold.ttf</code> ada (format TrueType .ttf; .otf/.woff2 tidak didukung generator PDF). Jika tidak ada, dipakai Montserrat Bold yang sudah disertakan.</p>
<form method="POST" action="{{ route('card.upload') }}" enctype="multipart/form-data" class="bg-white p-4 rounded shadow mb-4 space-y-2">
  @csrf
  <p class="text-sm text-slate-600">Upload desain kartu (PNG/JPG, rasio 8:11) yang berisi teks hitam pekat
  <b>&lt;&lt;CODE&gt;&gt;</b>, <b>&lt;&lt;QRCODE&gt;&gt;</b>, <b>&lt;&lt;NAMA&gt;&gt;</b>, <b>&lt;&lt;NIP&gt;&gt;</b>, <b>&lt;&lt;KAB/KOTA&gt;&gt;</b>
  (urut dari atas ke bawah seperti pada desain). Sistem mendeteksi posisi &amp; ukuran font tiap anchor, lalu menghapus teks anchor dari gambar.</p>
  <input type="file" name="image" accept="image/png,image/jpeg" required>
  <button class="bg-slate-900 text-white px-3 py-1 rounded">Upload &amp; Deteksi</button>
</form>

@if($exists)
<form method="POST" action="{{ route('card.update') }}" class="grid lg:grid-cols-2 gap-4">
  @csrf @method('PUT')
  <div class="bg-white p-4 rounded shadow">
    <div id="preview" class="relative mx-auto border" style="width:360px;aspect-ratio:8/11;background:url('{{ asset('card-template.png') }}?v={{ $version }}') center/100% 100% no-repeat;overflow:hidden">
      @foreach($anchors as $k => $label)
        <div id="box-{{ $k }}" class="absolute outline outline-1 outline-red-500 flex items-center justify-center font-bold text-black whitespace-nowrap overflow-hidden" style="line-height:1">
          {{ $k === 'qrcode' ? 'QR' : $label }}
        </div>
      @endforeach
    </div>
  </div>
  <div class="bg-white p-4 rounded shadow">
    <p class="text-xs text-slate-500 mb-2">Semua nilai dalam % dari lebar/tinggi kartu. Font = % dari tinggi kartu (QR tidak memakai font).</p>
    <table class="w-full text-sm">
      <thead><tr class="text-left"><th>Anchor</th><th>X</th><th>Y</th><th>Lebar</th><th>Tinggi</th><th>Font</th></tr></thead>
      @foreach($anchors as $k => $label)
        <tr>
          <td class="py-1 font-mono text-xs">{{ $label }}</td>
          @foreach(['x','y','w','h','fs'] as $f)
            <td><input type="number" step="0.01" min="0" max="100" name="{{ $k }}[{{ $f }}]" value="{{ round(($layout[$k][$f] ?? 0) * 100, 2) }}" data-k="{{ $k }}" data-f="{{ $f }}" class="w-16 border rounded p-1"></td>
          @endforeach
        </tr>
      @endforeach
    </table>
    <button class="mt-3 bg-slate-900 text-white px-4 py-2 rounded">Simpan Posisi</button>
    <a href="{{ route('participants.cards', ['ids' => \App\Models\Participant::value('id')]) }}" class="ml-2 text-blue-600">Tes download PDF 1 kartu</a>
  </div>
</form>
<script>
const W = 360, H = 360 * 11 / 8;
function paint() {
  document.querySelectorAll('[id^=box-]').forEach(b => {
    const k = b.id.slice(4), v = f => parseFloat(document.querySelector(`[data-k=${k}][data-f=${f}]`).value) || 0;
    Object.assign(b.style, {left: v('x') + '%', top: v('y') + '%', width: v('w') + '%', height: v('h') + '%', fontSize: (v('fs') / 100 * H) + 'px'});
  });
}
document.querySelectorAll('[data-k]').forEach(i => i.addEventListener('input', paint));
paint();
</script>
@endif
@endsection
