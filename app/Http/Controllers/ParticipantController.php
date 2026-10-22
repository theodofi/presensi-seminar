<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function index(Request $request)
    {
        $participants = Participant::withCount('attendances')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")
                ->orWhere('nip', 'like', "%$s%")->orWhere('kab_kota', 'like', "%$s%")->orWhere('code', 'like', "%$s%")))
            ->orderBy('name')->paginate(50)->withQueryString();

        $sessionCount = \App\Models\SeminarSession::count();

        return view('participants.index', compact('participants', 'sessionCount'));
    }

    public function create()
    {
        return view('participants.form', ['participant' => new Participant]);
    }

    public function store(Request $request)
    {
        Participant::create($this->validated($request));

        return redirect()->route('participants.index')->with('ok', 'Peserta ditambahkan.');
    }

    public function edit(Participant $participant)
    {
        return view('participants.form', compact('participant'));
    }

    public function update(Request $request, Participant $participant)
    {
        $participant->update($this->validated($request));

        return redirect()->route('participants.index')->with('ok', 'Peserta diperbarui.');
    }

    public function destroy(Participant $participant)
    {
        $participant->delete();

        return back()->with('ok', 'Peserta dihapus.');
    }

    public function cards(Request $request)
    {
        if (! \App\Services\CardTemplate::exists()) {
            return redirect()->route('card.edit')->withErrors(['image' => 'Upload template ID card dulu.']);
        }

        $ids = $request->filled('ids') ? array_filter(array_map('intval', explode(',', $request->ids))) : null;
        $participants = Participant::when($ids, fn ($q) => $q->whereIn('id', $ids))->orderBy('name')->get();
        if ($participants->isEmpty()) {
            return back()->withErrors(['q' => 'Belum ada peserta.']);
        }

        $name = $participants->count() === 1 ? 'idcard-'.\Illuminate\Support\Str::slug($participants[0]->name) : 'idcard-peserta';

        return response(\App\Services\CardPdf::render($participants), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$name.'.pdf"',
        ]);
    }
    // CSV: nama,nip,kab/kota (header opsional; pemisah koma atau titik koma)
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:4096']);
        $fh = fopen($request->file('file')->getRealPath(), 'r');
        $first = fgets($fh);
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
        $delim = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        rewind($fh);

        $created = $updated = 0;
        $i = 0;
        while (($row = fgetcsv($fh, 0, $delim)) !== false) {
            $row = array_map(fn ($v) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $v)), $row);
            $name = $row[0] ?? '';
            if ($i++ === 0 && in_array(strtolower($name), ['nama', 'name'])) {
                continue;
            }
            if ($name === '') {
                continue;
            }
            $nip = preg_replace('/\s+/', '', $row[1] ?? '') ?: null;
            $attrs = ['name' => $name, 'nip' => $nip, 'kab_kota' => ($row[2] ?? '') ?: null];

            // NIP yang sama diperbarui, bukan diduplikasi (kode QR tetap)
            $existing = $nip ? Participant::where('nip', $nip)->first() : null;
            if ($existing) {
                $existing->update($attrs);
                $updated++;
            } else {
                Participant::create($attrs);
                $created++;
            }
        }
        fclose($fh);

        return back()->with('ok', "Import selesai: $created baru, $updated diperbarui.");
    }
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:32',
            'kab_kota' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);
    }
}
