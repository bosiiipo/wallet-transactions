<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TransactionController;

Route::get('/transactions', [TransactionController::class, 'index']);

Route::post('/transactions', [TransactionController::class, 'store']);
