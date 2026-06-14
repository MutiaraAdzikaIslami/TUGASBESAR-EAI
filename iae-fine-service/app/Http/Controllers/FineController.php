<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Services\FineCalculationService;

class FineController extends Controller
{
    protected $fineCalculationService;

    public function __construct(FineCalculationService $fineCalculationService)
    {
        $this->fineCalculationService = $fineCalculationService;
    }

    public function index()
    {
        return response()->json(Fine::all());
    }

    public function userFines($userId)
    {
        return response()->json(Fine::where('user_id', $userId)->get());
    }

    public function userUnpaidFines($userId)
    {
        return response()->json(Fine::where('user_id', $userId)->where('status', 'UNPAID')->get());
    }

    public function show($id)
    {
        $fine = Fine::find($id);
        if (!$fine) {
            return response()->json(['message' => 'Data denda tidak ditemukan.'], 404);
        }
        return response()->json($fine);
    }

    public function pay($id)
    {
        $fine = Fine::find($id);
        if (!$fine) {
            return response()->json(['message' => 'Data denda tidak ditemukan.'], 404);
        }

        if ($fine->status === 'PAID') {
            return response()->json(['message' => 'Denda ini sudah dibayar sebelumnya.'], 400);
        }

        $fine->status = 'PAID';
        $fine->save();

        return response()->json([
            'message' => 'Denda berhasil dibayar.',
            'data' => $fine
        ]);
    }

    public function info()
    {
        return response()->json([
            'message' => 'Fine Service Running',
            'fine_rate_per_day' => $this->fineCalculationService->getFineRate(),
            'formatted' => 'Rp ' . number_format($this->fineCalculationService->getFineRate(), 0, ',', '.') . ' per hari'
        ]);
    }
}
