<?php

declare(strict_types=1);

namespace App\Grid\Formatting;

use LaraGrid\Formatting\Formatter;

/**
 * An app-registered display format — Indian digit grouping (12,34,56,789) with a ₹ prefix.
 *
 * Formatting runs in BOTH runtimes: the client for instant paint, the server for authority
 * (tests, pre-computed footer totals, PDF exports). Every PHP formatter therefore needs a
 * behaviourally identical JS twin registered under the same name — ours lives in
 * resources/views/reports/index.blade.php. The package pins its own pairs with shared
 * vectors; an app should test its own the same way.
 *
 * Registered as 'inr' in App\Providers\AppServiceProvider; used as
 * ->format('inr', ['scale' => 2]) on any column or footer aggregate.
 */
final class InrFormatter implements Formatter
{
    /**
     * @param  array<string, scalar>  $args  Supports {scale: int (default 0), symbol: bool (default true)}.
     */
    public function format(mixed $value, array $args = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $scale = max(0, (int) ($args['scale'] ?? 0));
        $symbol = (bool) ($args['symbol'] ?? true);

        $number = (float) $value;
        $negative = $number < 0;

        // Ungrouped fixed-scale text first, then regroup — so rounding happens exactly once.
        [$whole, $fraction] = array_pad(explode('.', number_format(abs($number), $scale, '.', '')), 2, '');

        if (strlen($whole) > 3) {
            // Indian grouping: the last three digits, then pairs all the way up.
            $whole = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($whole, 0, -3))
                .','.substr($whole, -3);
        }

        return ($negative ? '-' : '')
            .($symbol ? '₹' : '')
            .$whole
            .($fraction !== '' ? '.'.$fraction : '');
    }
}
