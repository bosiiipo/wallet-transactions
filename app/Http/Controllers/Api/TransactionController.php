<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

use Illuminate\Support\Str;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\IdempotencyKey;

class TransactionController extends Controller
{
    public function store(TransactionRequest $request)
    {
        $data = $request->validated();

        $idempotencyKey = $data['idempotency_key'];

        // Check if idempotency key exists
        $existing = IdempotencyKey::where('key', $idempotencyKey)->first();
        if ($existing) {
            return response()->json(json_decode($existing->response), 200);
        }

        $result = DB::transaction(function () use ($data, $idempotencyKey) {

            $wallet = Wallet::findOrFail($data['wallet_id']);

            // Overdraft protection
            if ($data['type'] === 'debit' && $wallet->balance < $data['amount']) {
                abort(422, 'Insufficient balance.');
            }

            // Update wallet balance
            if ($data['type'] === 'credit') {
                $wallet->balance += $data['amount'];
            } else {
                $wallet->balance -= $data['amount'];
            }
            $wallet->save();

            // Create transaction
            $transaction = Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'reference' => $data['reference'],
            ]);

            $response = [
                'transaction' => $transaction,
                'wallet' => [
                    'id' => $wallet->id,
                    'balance' => $wallet->balance,
                    'currency' => $wallet->currency,
                ],
            ];

            // Save idempotency key
            IdempotencyKey::create([
                'key' => $idempotencyKey,
                'response' => json_encode($response),
            ]);

            return $response;
        });

        return response()->json($result, 201);
    }


    public function index(Request $request)
    {
        $baseQuery = Transaction::query();

        // Filtering
        if ($request->filled('q')) {
            $baseQuery->where('reference', 'like', "%{$request->q}%");
        }

        if ($request->filled('type')) {
            $baseQuery->where('type', $request->type);
        }

        if ($request->filled('from')) {
            $baseQuery->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $baseQuery->whereDate('created_at', '<=', $request->to);
        }

        // Clone the query BEFORE paginate mutates it
        $summaryQuery = clone $baseQuery;

        // Pagination
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        $paginated = $baseQuery
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Now compute totals from the full (filtered) result
        $totalIn = (clone $summaryQuery)->where('type', 'credit')->sum('amount');
        $totalOut = (clone $summaryQuery)->where('type', 'debit')->sum('amount');

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'total' => $paginated->total(),
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
            ],
            'summary' => [
                'total_in' => $totalIn,
                'total_out' => $totalOut,
            ],
        ]);
    }
}
