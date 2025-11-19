<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('wallets')->insert([
            'user_id' => $userId,
            'currency' => 'NGN',
            'balance' => 100000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
