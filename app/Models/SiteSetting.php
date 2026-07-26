<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['key', 'value', 'updated_by'])]
class SiteSetting extends Model
{
    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : decrypt($value),
            set: fn (mixed $value) => $value === null ? null : encrypt((string) $value),
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
