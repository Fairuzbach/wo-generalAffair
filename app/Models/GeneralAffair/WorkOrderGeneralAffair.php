<?php

namespace App\Models\GeneralAffair;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\GeneralAffair\WorkOrderGaHistory;

class WorkOrderGeneralAffair extends Model
{
    use HasFactory;

    protected $table = 'work_order_general_affairs';

    protected $fillable = [
        'ticket_num',
        'requester_id',
        'requester_nik',
        'requester_name',
        'requester_department', // <--- Pastikan ini ada!
        'plant',
        'department',
        'category',
        'description',
        'parameter_permintaan',
        'status_permintaan',
        'target_completion_date',
        'status',
        'photo_path',
        'photo_completed_path',
        'processed_by',
        'processed_by_name',
        'processed_at',
        'completed_at',
        'rejected_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
    public function processor()
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    public function histories()
    {
        return $this->hasMany(WorkOrderGaHistory::class, 'work_order_id')->latest();
    }
}
