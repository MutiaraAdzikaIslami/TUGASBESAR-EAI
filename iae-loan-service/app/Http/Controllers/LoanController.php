<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Loan;
use Illuminate\Support\Facades\Redis;

class LoanController extends Controller
{
public function returnBook($id)
{
    $loan = Loan::findOrFail($id);

    if ($loan->status === 'RETURNED') {
        return response()->json([
            'message' => 'Buku sudah dikembalikan sebelumnya.'
        ], 400);
    }

    $loan->return_date = now()->toDateString();
    $loan->status = 'RETURNED';
    $loan->save();

    $returnDate = \Carbon\Carbon::parse($loan->return_date);
    $dueDate = \Carbon\Carbon::parse($loan->due_date);
    $daysLate = $returnDate->diffInDays($dueDate, false);

    if ($daysLate > 0) {
        try {
            $payload = json_encode([
                'event'       => 'BookReturned',
                'loan_id'     => (int) $loan->id,
                'user_id'     => (int) $loan->user_id,
                'book_id'     => (int) $loan->book_id,
                'due_date'    => $loan->due_date,
                'return_date' => $loan->return_date,
                'days_late'   => $daysLate,
            ]);

            Redis::publish('book-events', $payload);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Data tersimpan di DB, TAPI REDIS ERROR!',
                'error_system' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Buku berhasil dikembalikan. Ada denda keterlambatan!',
            'days_late' => $daysLate,
            'data' => $loan
        ]);
    }

    return response()->json([
        'message' => 'Buku berhasil dikembalikan. Tepat waktu, tidak ada denda.',
        'data' => $loan
    ]);
}

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
