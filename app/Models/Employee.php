<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    // Menghubungkan ke tabel 'employees'
    protected $table = 'employees';

    // Mengizinkan semua kolom diisi (mass assignment)
    protected $guarded = ['id'];

    protected $primaryKey = 'nik';
    public $incrementing = false; // Karena NIK bukan auto-increment integer
    protected $keyType = 'string';
}
