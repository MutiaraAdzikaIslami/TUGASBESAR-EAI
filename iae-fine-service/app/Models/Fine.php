<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fine extends Model
{
    protected $table = 'fines';

    protected $fillable = [
        'loan_id',
        'user_id',
        'book_id',
        'amount',
        'days_late',
        'status',
    ];
}
