<?php

namespace Database\Seeders;

use App\Models\SeminarSession;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['email' => 'panitia@example.com'], ['name' => 'Panitia', 'password' => 'panitia123']);

        for ($i = 1; $i <= 7; $i++) {
            SeminarSession::firstOrCreate(['number' => $i], ['name' => "Sesi $i"]);
        }
    }
}
