<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'quantity',
        'condition',
        'location',
        'description',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];
}
