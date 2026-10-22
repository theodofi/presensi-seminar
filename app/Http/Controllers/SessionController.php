<?php

namespace App\Http\Controllers;

use App\Models\SeminarSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = SeminarSession::orderBy('number')->withCount('attendances')->get();

        return view('sessions', compact('sessions'));
    }

    public function store()
    {
        $n = (int) SeminarSession::max('number') + 1;
        SeminarSession::create(['number' => $n, 'name' => "Sesi $n"]);

        return back()->with('ok', "Sesi $n ditambahkan.");
    }

    public function destroy(SeminarSession $session)
    {
        $number = $session->number;
        $session->delete();
        $this->renumber();

        return back()->with('ok', "Sesi {$number} dihapus beserta data absensinya.");
    }

    public function reorder(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:seminar_sessions,id']);
        $ids = array_values(array_unique($data['ids']));
        DB::transaction(function () use ($ids) {
            foreach ($ids as $i => $id) {
                SeminarSession::whereKey($id)->update(['number' => $i + 1]);
            }
            $this->renumber();
        });

        return response()->json(['ok' => true]);
    }

    /** Rapikan nomor urut menjadi 1..N tanpa celah, mengikuti urutan saat ini. */
    private function renumber(): void
    {
        SeminarSession::orderBy('number')->orderBy('id')->get()->each(function ($s, $i) {
            if ($s->number !== $i + 1) {
                $s->update(['number' => $i + 1]);
            }
        });
    }

    public function update(Request $request, SeminarSession $session)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'opens_at' => 'nullable|date',
            'closes_at' => 'nullable|date|after:opens_at',
        ]);
        $session->update($data);

        return back()->with('ok', "Sesi {$session->number} disimpan.");
    }

    public function mode(Request $request, SeminarSession $session)
    {
        $data = $request->validate(['mode' => 'required|in:auto,open,closed']);
        $session->update($data);

        return back()->with('ok', "Sesi {$session->number}: mode {$data['mode']}.");
    }
}
