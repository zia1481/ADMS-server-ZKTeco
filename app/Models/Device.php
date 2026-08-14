<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory, BelongsToCompany;

    public const STATUS_REGISTERED = 'registered';
    public const STATUS_PENDING = 'pending';
    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'company_id',
        'area_id',
        'nama',
        'no_sn',
        'lokasi',
        'online',
        'ip_address',
        'model',
        'fw_ver',
        'push_ver',
        'comm_key',
        'comm_key_enforce',
        'attlog_stamp',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
