<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\Fine;
use App\Services\FineCalculationService;

class RedisSubscribe extends Command
{
    protected $signature = 'redis:subscribe';
    protected $description = 'Subscribe ke Redis channel book-events untuk mencatat denda keterlambatan';

    public function handle()
    {
        $this->info('=== Fine Service Redis Subscriber Aktif ===');
        $this->info('Menunggu event di channel [book-events]...');

        Redis::subscribe(['book-events'], function ($message) {
            $this->info("Menerima pesan mentah: " . $message);

            $data = json_decode($message, true);

            if (!$data || !isset($data['event'])) {
                return;
            }

            if ($data['event'] !== 'BookReturned') {
                return;
            }

            $loanId   = (int) $data['loan_id'];
            $userId   = (int) $data['user_id'];
            $bookId   = (int) $data['book_id'];
            $daysLate = (int) $data['days_late'];

            if ($daysLate <= 0) {
                $this->info("Buku dikembalikan tepat waktu. Tidak ada denda untuk Loan ID {$loanId}.");
                return;
            }

            $amount = (new FineCalculationService())->calculate($daysLate);

            Fine::create([
                'loan_id'   => $loanId,
                'user_id'   => $userId,
                'book_id'   => $bookId,
                'days_late' => $daysLate,
                'amount'    => $amount,
                'status'    => 'UNPAID',
            ]);

            $this->info("=== DENDA TERCATAT ===");
            $this->info("Loan ID    : {$loanId}");
            $this->info("User ID    : {$userId}");
            $this->info("Buku ID    : {$bookId}");
            $this->info("Telat      : {$daysLate} hari");
            $this->info("Denda      : Rp " . number_format($amount, 0, ',', '.'));
            $this->info("======================");
        });
    }
}
