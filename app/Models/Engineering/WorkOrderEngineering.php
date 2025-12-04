<?php

namespace App\Models\Engineering;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class WorkOrderEngineering extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'ticket_num',
        'report_date',
        'report_time',
        'shift',
        'plant',
        'machine_name',
        'damaged_part',
        'production_status',
        'kerusakan',
        'kerusakan_detail',
        'priority',
        'work_status',
        'photo_path',

        //edited data
        'work_status',
        'finished_date',
        'start_time',
        'end_time',
        'technician',
        'maintenance_note',
        'repair_solution',
        'sparepart'
    ];

    protected $casts = [
        'report_date' => 'date',
        'finished_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->ticket_num)) {
                $model->ticket_num = 'WO-' . date('Ym') . '-' . strtoupper(Str::random(4));
            }
        });
    }
    public function getStatusColorAttribute()
    {
        return match ($this->work_status) {
            'pending' => 'yellow',
            'in_progress' => 'blue',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray'
        };
    }
    public function getPriorityColorAttribute()
    {
        return match ($this->priority) {
            'low' => 'green',
            'medium' => 'blue',
            'high' => 'yellow',
            'critical' => 'red',
            default => 'gray'
        };
    }
}
