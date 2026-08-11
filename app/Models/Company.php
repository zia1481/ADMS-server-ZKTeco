<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
