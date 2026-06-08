<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Loan;
use Illuminate\Support\Facades\Redis;

class LoanController extends Controller
{
public function store(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer',
        'book_id' => 'required|integer',
    ]);

    // 1. Simpan ke Database
    $loan = Loan::create([
        'user_id'     => $request->user_id,
        'book_id'     => $request->book_id,
        'loan_date'   => now()->toDateString(),
        'due_date'    => now()->addDays(7)->toDateString(),
        'status'      => 'BORROWED'
    ]);

    // 2. Coba Publish ke Redis dengan Proteksi Error
    try {
        $payload = json_encode([
            'event'   => 'BookBorrowed',
            'book_id' => (int) $loan->book_id,
        ]);

        // Perintah publish mengembalikan jumlah subscriber yang menerima pesan (int)
        $receiverCount = Redis::publish('book-events', $payload);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Data tersimpan di DB, TAPI REDIS ERROR!',
            'error_system' => $e->getMessage()
        ], 500);
    }

    return response()->json([
        'message' => 'Peminjaman berhasil dicatat!',
        'jumlah_subscriber_terkoneksi' => $receiverCount,
        'data' => $loan
    ], 201);
}
}
