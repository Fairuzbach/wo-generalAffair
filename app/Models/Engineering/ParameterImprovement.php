<?php

namespace App\Models\Engineering;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParameterImprovement extends Model
{
    use HasFactory;

    protected $table = 'parameter_improvements';

    protected $fillable = [
        'code',
        'name'
    ];
}
