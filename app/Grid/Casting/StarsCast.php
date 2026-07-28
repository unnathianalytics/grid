<?php

declare(strict_types=1);

namespace App\Grid\Casting;

use LaraGrid\Casting\Cast;
use LaraGrid\Columns\Column;

/**
 * The parse "kind" behind App\Grid\Columns\RatingColumn — turns whatever the operator typed
 * into an integer 0–5.
 *
 * Casting, like formatting, runs in both runtimes: the client casts optimistically so the
 * cell paints instantly, the server casts authoritatively so the stored value is the truth.
 * The two must agree by construction, which is why every registered cast needs a JS twin
 * under the same kind name (ours is in resources/views/reports/index.blade.php).
 *
 * Registered as 'stars' in App\Providers\AppServiceProvider.
 */
final class StarsCast implements Cast
{
    /**
     * @param  array<string, mixed>  $spec  The column's full parseSpec.
     */
    public function cast(mixed $value, array $spec, Column $column): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, min(5, (int) round((float) $value)));
    }
}
