<?php

namespace Actengage\Roles;

use Illuminate\Database\Eloquent\Relations\MorphPivot as Pivot;

class MorphPivot extends Pivot
{
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function roleable()
    {
        return $this->morphTo();
    }
}