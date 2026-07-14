<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-off demo utility: copy the `resorts` table (structure + all rows) from the
 * default MySQL connection into database/database.sqlite. Rerunnable — the sqlite
 * table is dropped and rebuilt each run.
 *
 * Usage: php artisan demo:copy-resorts
 */
class CopyResortsToSqlite extends Command
{
    protected $signature = 'demo:copy-resorts';

    protected $description = 'Copy the resorts table from MySQL into database/database.sqlite';

    public function handle(): int
    {
        $path = database_path('database.sqlite');

        if (! file_exists($path)) {
            touch($path);
            $this->info("Created {$path}");
        }

        // An explicit connection with a hardcoded file path — deliberately NOT the stock
        // 'sqlite' connection, whose database name would resolve from DB_DATABASE (which
        // currently points at the MySQL schema name).
        config(['database.connections.sqlite_demo' => [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        Schema::connection('sqlite_demo')->dropIfExists('resorts');
        Schema::connection('sqlite_demo')->create('resorts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('destination_id')->nullable();
            $table->string('name', 1000)->nullable();
            $table->string('slug', 120)->nullable();
            $table->string('shortcode', 100)->nullable();
            $table->string('randid', 32)->nullable();
            $table->string('type', 50)->nullable();          // enum in MySQL → string in sqlite
            $table->string('visibility', 10)->default('show');
            $table->string('meta_title', 200)->nullable();
            $table->string('meta_description', 1000)->nullable();
            $table->string('meta_keywords', 1000)->nullable();
            $table->longText('description')->nullable();
            $table->longText('tariff')->nullable();
            $table->longText('gallery')->nullable();
            $table->longText('facility')->nullable();
            $table->longText('more_info')->nullable();
            $table->string('rating', 10)->nullable();
            $table->string('address', 1000)->nullable();
            $table->unsignedInteger('comparison_tariff')->nullable();
            $table->string('url', 255)->nullable();
            $table->string('map', 55)->nullable();
            $table->string('featured_image', 255)->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->dateTime('last_viewed_at')->nullable();
            $table->integer('legacy_id')->nullable();
            $table->timestamps();
        });

        $copied = 0;
        DB::connection('mysql')->table('resorts')->orderBy('id')->chunk(100, function ($rows) use (&$copied) {
            DB::connection('sqlite_demo')->table('resorts')->insert(
                $rows->map(fn ($row): array => (array) $row)->all()
            );
            $copied += $rows->count();
            $this->output->write("\rCopied {$copied} rows…");
        });

        $this->newLine();
        $this->info("Done — {$copied} resorts now in {$path}");

        return self::SUCCESS;
    }
}
