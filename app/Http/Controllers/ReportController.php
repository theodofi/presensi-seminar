<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Participant;
use App\Models\SeminarSession;

class ReportController extends Controller
{
    private function data(): array
    {
        $sessions = SeminarSession::orderBy('number')->get();
        $participants = Participant::orderBy('name')->get();
        $map = Attendance::all()->groupBy('participant_id')
            ->map(fn ($g) => $g->keyBy('seminar_session_id'));

        return compact('sessions', 'participants', 'map');
    }

    public function index()
    {
        return view('report', $this->data());
    }

    public function export()
    {
        ['sessions' => $sessions, 'participants' => $participants, 'map' => $map] = $this->data();

        return response()->streamDownload(function () use ($sessions, $participants, $map) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['Kode', 'Nama', 'NIP', 'Kab/Kota'], $sessions->map(fn ($s) => "Sesi {$s->number}")->all(), ['Total']));
            foreach ($participants as $p) {
                $row = [$p->code, $p->name, $p->nip, $p->kab_kota];
                $n = 0;
                foreach ($sessions as $s) {
                    $a = $map[$p->id][$s->id] ?? null;
                    $a && $n++;
                    $row[] = $a ? $a->scanned_at->format('H:i:s') : '';
                }
                fputcsv($out, array_merge($row, [$n]));
            }
            fclose($out);
        }, 'rekap-absensi.csv', ['Content-Type' => 'text/csv']);
    }
}
