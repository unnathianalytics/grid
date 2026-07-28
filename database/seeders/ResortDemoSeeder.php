<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Resort;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Backfills the demo-only columns on the 500-odd real resort rows.
 *
 * Why it exists: the grid demos paint every LaraGrid column type — decimals, dates,
 * checkboxes, Y/N, selects, computed badges — and a column of blanks demonstrates
 * nothing. Values are derived from the row id (no randomness), so re-running the
 * seeder is idempotent and screenshots stay stable between runs.
 *
 * Run: php artisan db:seed --class=ResortDemoSeeder
 */
class ResortDemoSeeder extends Seeder
{
    private const CITIES = [
        'Agra', 'Ahmedabad', 'Bengaluru', 'Chandigarh', 'Chennai', 'Coimbatore', 'Darjeeling',
        'Dehradun', 'Goa', 'Gokarna', 'Hyderabad', 'Jaipur', 'Kochi', 'Kolkata', 'Leh',
        'Lucknow', 'Madurai', 'Manali', 'Mumbai', 'Munnar', 'Mysuru', 'Nainital', 'Ooty',
        'Pondicherry', 'Pune', 'Rishikesh', 'Shimla', 'Udaipur', 'Varanasi', 'Wayanad',
    ];

    private const STARS = ['3', '4', '5', 'unrated'];

    private const MANAGERS = [
        'A. Menon', 'B. Iyer', 'C. Nair', 'D. Rao', 'E. Sharma', 'F. Kulkarni',
        'G. Banerjee', 'H. Pillai', 'I. Chauhan', 'J. Deshmukh', 'K. Varghese', 'L. Reddy',
    ];

    private const AMENITIES = ['pool', 'spa', 'gym', 'wifi', 'restaurant', 'bar', 'kids', 'beach'];

    public function run(): void
    {
        $updated = 0;

        Resort::query()->orderBy('id')->chunkById(200, function ($resorts) use (&$updated): void {
            foreach ($resorts as $resort) {
                $id = (int) $resort->id;
                $tariff = (int) ($resort->comparison_tariff ?: 0);

                // A stable pseudo-spread: two coprime multipliers keep neighbouring ids from
                // landing in the same bucket without any call to rand().
                $a = ($id * 7) % count(self::CITIES);
                $b = ($id * 13) % count(self::MANAGERS);

                $resort->forceFill([
                    'shortcode' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) $resort->name) ?: 'RS', 0, 3)).str_pad((string) ($id % 1000), 3, '0', STR_PAD_LEFT),
                    'city' => self::CITIES[$a],
                    'star_rating' => self::STARS[$id % 4],
                    'rooms' => 6 + (($id * 3) % 90),
                    'room_rate' => $tariff > 0 ? round($tariff * 1.18, 2) : round(1500 + (($id * 37) % 8500), 2),
                    'featured' => $id % 9 === 0,
                    'gst_applicable' => $id % 3 !== 0,
                    'manager' => self::MANAGERS[$b],
                    'contact_phone' => '+91 '.str_pad((string) (60000 + ($id * 17) % 39999), 5, '0', STR_PAD_LEFT).' '.str_pad((string) (($id * 91) % 99999), 5, '0', STR_PAD_LEFT),
                    'contact_email' => 'front.desk+'.$id.'@example.com',
                    'opened_on' => Carbon::create(1998 + ($id % 26), 1 + ($id % 12), 1 + ($id % 27))?->toDateString(),
                    'amenities' => array_values(array_filter(
                        self::AMENITIES,
                        fn (string $amenity, int $index): bool => ($id >> $index) % 2 === 1,
                        ARRAY_FILTER_USE_BOTH,
                    )),
                ])->save();

                $updated++;
            }
        });

        $this->command?->info("Backfilled demo columns on {$updated} resorts.");
    }
}
