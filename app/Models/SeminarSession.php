<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['number', 'name', 'opens_at', 'closes_at', 'mode'])]
class SeminarSession extends Model
{
    protected function casts(): array
    {
        return ['opens_at' => 'datetime', 'closes_at' => 'datetime'];
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function isOpen(): bool
    {
        if ($this->mode === 'open') {
            return true;
        }
        if ($this->mode === 'closed') {
            return false;
        }
        $now = now();

        return $this->opens_at && $this->closes_at
            && $now->gte($this->opens_at) && $now->lte($this->closes_at);
    }
}
