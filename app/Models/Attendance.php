<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['participant_id', 'seminar_session_id', 'scanned_by', 'scanned_at'])]
class Attendance extends Model
{
    protected function casts(): array
    {
        return ['scanned_at' => 'datetime'];
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function session()
    {
        return $this->belongsTo(SeminarSession::class, 'seminar_session_id');
    }
}
