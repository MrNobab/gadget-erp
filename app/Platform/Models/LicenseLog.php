<?php

namespace App\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseLog extends Model
{
    protected $fillable = [
        'license_id',
        'changed_by_super_admin_id',
        'from_status',
        'to_status',
        'reason',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'changed_by_super_admin_id');
    }
}
