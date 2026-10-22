<?php

namespace App\Http\Controllers;

use App\Models\SeminarSession;
use Illuminate\Http\Request;

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
        $session->delete();

        return back()->with('ok', "Sesi {$session->number} dihapus beserta data absensinya.");
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
