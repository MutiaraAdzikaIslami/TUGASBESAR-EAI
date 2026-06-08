<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class RedisSubscriber extends Command
{
    // Nama command yang akan dipanggil
    protected $signature = 'redis:subscribe';
    protected $description = 'Mendengarkan event broadcast dari Redis Broker untuk memperbarui stok buku';

    public function handle()
    {
        $this->info('Worker Subscriber sedang berjalan... Menunggu event di channel [book-events]');

        // Subscribe ke channel 'book-events'
        Redis::subscribe(['book-events'], function ($message) {
            $this->info("Menerima pesan mentah: " . $message);

            // Decode payload JSON dari Loan Service
            $data = json_decode($message, true);

            if ($data && $data['event'] === 'BookBorrowed') {
                $bookId = $data['book_id'];
                $this->info("Memproses event BookBorrowed untuk Book ID: " . $bookId);

                // LOGIKA BISNIS: Cek stok di db_ketersediaan
                $stock = DB::table('book_stocks')->where('book_id', $bookId)->first();

                if ($stock) {
                    if ($stock->available_stock > 0) {
                        DB::table('book_stocks')
                            ->where('book_id', $bookId)
                            ->decrement('available_stock', 1);

                        $this->info("SUKSES: Stok Buku ID {$bookId} berhasil dikurangi 1.");
                    } else {
                        $this->error("GAGAL: Stok Buku ID {$bookId} sudah habis di rak!");
                    }
                } else {
                    // Jika data buku belum pernah di-set stoknya di MySQL, buat default baru
                    DB::table('book_stocks')->insert([
                        'book_id' => $bookId,
                        'total_stock' => 5,
                        'available_stock' => 4 // Langsung dikurangi 1 karena dipinjam
                    ]);
                    $this->info("SUKSES: Data stok baru diinisialisasi untuk Buku ID {$bookId}.");
                }
            }
        });
    }
}
