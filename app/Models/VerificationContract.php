<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'company_name',
        'note',
        'kp',
        'sales_name',
        'verification_status',
    ];
}
