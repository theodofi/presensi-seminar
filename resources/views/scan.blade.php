@extends('layout')
@section('title', 'Scan')
@section('content')
<div class="grid md:grid-cols-2 gap-6">
  <div class="bg-white p-4 rounded shadow space-y-3">
    <label class="block text-sm font-semibold">Sesi aktif untuk scan</label>
    <select id="session" class="w-full border rounded p-2">
      @foreach($sessions as $s)
        <option value="{{ $s->id }}" @selected($current?->id === $s->id)>
          Sesi {{ $s->number }} — {{ $s->name }} [{{ $s->isOpen() ? 'BUKA' : 'TUTUP' }}]
        </option>
      @endforeach
    </select>
    <div id="reader" class="w-full"></div>
    <div class="flex gap-2">
      <input id="manual" class="flex-1 border rounded p-2" placeholder="Input kode manual / scanner USB, lalu Enter" autocomplete="off">
      <button id="manualBtn" class="bg-slate-900 text-white px-4 rounded">OK</button>
    </div>
  </div>
  <div class="space-y-3">
    <div id="result" class="p-6 rounded shadow bg-white text-center text-slate-500">Menunggu scan…</div>
    <div class="bg-white p-4 rounded shadow">
      <div class="font-semibold mb-2">Riwayat scan</div>
      <ul id="history" class="text-sm space-y-1"></ul>
    </div>
  </div>
</div>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const token = document.querySelector('meta[name=csrf-token]').content;
const result = document.getElementById('result'), hist = document.getElementById('history');
let busy = false, lastCode = '', lastAt = 0;
let audioCtx = null;
function unlockAudio() {
  try { audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)(); audioCtx.resume(); } catch (e) {}
}
['pointerdown', 'keydown', 'touchstart'].forEach(ev => document.addEventListener(ev, unlockAudio, {once: true}));
function beep(freq, dur, delay = 0) {
  if (!audioCtx) return;
  const o = audioCtx.createOscillator(), g = audioCtx.createGain(), t = audioCtx.currentTime + delay;
  o.type = 'square'; o.frequency.value = freq;
  g.gain.setValueAtTime(1, t); g.gain.exponentialRampToValueAtTime(0.001, t + dur);
  o.connect(g); g.connect(audioCtx.destination); o.start(t); o.stop(t + dur);
}
function sound(st) {
  unlockAudio();
  if (st === 'ok') beep(1500, 0.3);
  else { beep(400, 0.25); beep(400, 0.25, 0.35); }
}
const colors = {ok:'bg-green-100 text-green-900', duplicate:'bg-yellow-100 text-yellow-900', closed:'bg-red-100 text-red-900', invalid:'bg-red-100 text-red-900'};

async function submit(code) {
  code = code.trim();
  if (!code || busy || (code === lastCode && Date.now() - lastAt < 3000)) return;
  busy = true; lastCode = code; lastAt = Date.now();
  try {
    const r = await fetch('{{ route('scan.store', [], false) }}', {
      method: 'POST',
      headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token},
      body: JSON.stringify({session_id: document.getElementById('session').value, code})
    });
    const d = await r.json();
    if (r.status === 419 || r.status === 401) { location.reload(); return; }
    const st = d.status || 'invalid';
    result.className = 'p-6 rounded shadow text-center ' + (colors[st] || colors.invalid);
    result.innerHTML = '';
    const t = document.createElement('div'); t.className = 'text-2xl font-bold';
    t.textContent = d.name || d.message || 'Error';
    const s = document.createElement('div'); s.textContent = (d.name ? d.message + ' ' : '') + [d.nip, d.kab_kota].filter(Boolean).join(' · ');
    result.append(t, s);
    if (d.total !== undefined) { const c = document.createElement('div'); c.className='text-sm mt-1'; c.textContent = 'Total hadir sesi ini: ' + d.total; result.append(c); }
    const li = document.createElement('li');
    li.textContent = new Date().toLocaleTimeString() + ' — ' + (d.name || code) + ' — ' + (d.message || '');
    hist.prepend(li);
    navigator.vibrate && navigator.vibrate(st === 'ok' ? 100 : [100,50,100]);
    sound(st);
  } catch (e) {
    result.className = 'p-6 rounded shadow text-center ' + colors.invalid;
    result.textContent = 'Gagal terhubung ke server.';
  } finally { busy = false; }
}
document.getElementById('manual').addEventListener('keydown', e => { if (e.key === 'Enter') { submit(e.target.value); e.target.value = ''; }});
document.getElementById('manualBtn').onclick = () => { const m = document.getElementById('manual'); submit(m.value); m.value=''; };
new Html5Qrcode('reader').start({facingMode:'environment'}, {fps:10, qrbox:250}, txt => submit(txt)).catch(() => {
  document.getElementById('reader').textContent = 'Kamera tidak tersedia (butuh HTTPS/localhost & izin kamera). Gunakan input manual.';
});
</script>
@endsection
