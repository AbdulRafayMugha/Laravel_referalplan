<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommissionLevelSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('commission_levels')->upsert([
            ['level' => 1, 'rate' => env('LEVEL_1_COMMISSION', 15), 'created_at' => now(), 'updated_at' => now()],
            ['level' => 2, 'rate' => env('LEVEL_2_COMMISSION', 5), 'created_at' => now(), 'updated_at' => now()],
            ['level' => 3, 'rate' => env('LEVEL_3_COMMISSION', 2.5), 'created_at' => now(), 'updated_at' => now()],
        ], ['level'], ['rate','updated_at']);
    }
}


