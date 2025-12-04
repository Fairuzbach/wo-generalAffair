<?php

namespace App\Models\Engineering;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImprovementStatus extends Model
{
    use HasFactory;

    protected $table = 'improvement_statuses';

    protected $fillable = [
        'name',   // Pending, Completed, dll
        'slug',   // pending, completed
        'color',  // bg-green-100, hex code, dll untuk styling di frontend
        'order',  // untuk urutan sorting
        'status'
    ];
}
