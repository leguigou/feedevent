<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'icon'];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'slug' => 'string',
            'color' => 'string',
            'icon' => 'string',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
