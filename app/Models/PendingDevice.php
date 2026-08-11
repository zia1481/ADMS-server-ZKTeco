<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingDevice extends Model
{
    use HasFactory;

    public const STATE_DETECTED = 'detected';
    public const STATE_ASSIGNED = 'assigned';
    public const STATE_BLOCKED = 'blocked';
    public const STATE_IGNORED = 'ignored';

    protected $fillable = [
        'sn',
        'ip_address',
        'model',
        'fw_ver',
        'push_ver',
        'options',
        'first_seen',
        'last_seen',
        'state',
    ];
}
