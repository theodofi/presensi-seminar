<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Participant;
use App\Models\SeminarSession;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function index()
    {
        $sessions = SeminarSession::orderBy('number')->withCount('attendances')->get();
        $current = $sessions->first(fn ($s) => $s->isOpen());

        return view('scan', compact('sessions', 'current'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'session_id' => 'required|exists:seminar_sessions,id',
            'code' => 'required|string|max:64',
        ]);

        $session = SeminarSession::withCount('attendances')->findOrFail($data['session_id']);

        if (! $session->isOpen()) {
            return response()->json(['status' => 'closed', 'message' => "Sesi {$session->number} sedang TUTUP."], 422);
        }

        $participant = Participant::where('code', trim($data['code']))->first();
        if (! $participant) {
            return response()->json(['status' => 'invalid', 'message' => 'QR tidak dikenali.'], 404);
        }

        $attendance = Attendance::firstOrCreate(
            ['participant_id' => $participant->id, 'seminar_session_id' => $session->id],
            ['scanned_by' => $request->user()->id, 'scanned_at' => now()]
        );

        $total = $session->attendances()->count();
        $base = [
            'name' => $participant->name,
            'nip' => $participant->nip,
            'kab_kota' => $participant->kab_kota,
            'time' => $attendance->scanned_at->format('H:i:s'),
            'total' => $total,
        ];

        if (! $attendance->wasRecentlyCreated) {
            return response()->json($base + ['status' => 'duplicate', 'message' => 'Sudah absen di sesi ini.'], 409);
        }

        return response()->json($base + ['status' => 'ok', 'message' => 'Absen berhasil.']);
    }
}
