<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resort extends Model
{
    protected $guarded = [];

    /**
     * Casts backing the LaraForm demo columns: array fields for the multi/checkbox-group
     * pickers, booleans for Y-N/toggle/checkbox, a date-only cast the DateField renders
     * through its display pattern.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'facilities' => 'array',
            'gst_applicable' => 'boolean',
            'featured' => 'boolean',
            'opened_on' => 'date:Y-m-d',
        ];
    }
}
