<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCompany
{
    public function scopeForCompany(Builder $query, ?int $companyId = null): Builder
    {
        $companyId = $companyId ?? current_company_id();

        if ($companyId) {
            return $query->where($this->getTable() . '.company_id', $companyId);
        }

        return $query;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
