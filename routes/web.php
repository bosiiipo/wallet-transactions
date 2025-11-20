<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-seeder', function() {
    if (config('app.env') === 'production') {
        abort(403, 'Forbidden');
    }

    $userId = DB::table('users')->insertGetId([
        'name' => 'John Did',
        'email' => 'john@google.com',
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
    return 'Seeder executed!';
});
