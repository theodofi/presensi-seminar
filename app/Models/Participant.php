<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['code', 'name', 'nip', 'kab_kota', 'email'])]
class Participant extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Participant $p) {
            $p->code = $p->code ?: self::generateCode();
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
