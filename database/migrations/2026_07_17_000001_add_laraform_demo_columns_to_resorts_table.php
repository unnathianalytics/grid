<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns backing the LaraForm demo (New Resort form). All nullable — the 501 existing
 * grid rows stay valid; the form maps active→visibility and room_rate→comparison_tariff
 * into the columns the resorts grid already paints.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resorts', function (Blueprint $table): void {
            $table->string('shortcode', 8)->nullable();
            $table->string('city')->nullable();
            $table->date('opened_on')->nullable();
            $table->decimal('room_rate', 10, 2)->nullable();
            $table->unsignedInteger('rooms')->nullable();
            $table->string('star_rating')->nullable();
            $table->json('amenities')->nullable();
            $table->json('facilities')->nullable();
            $table->boolean('gst_applicable')->nullable();
            $table->boolean('featured')->nullable();
            $table->text('description')->nullable();
            $table->string('manager')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('resorts', function (Blueprint $table): void {
            $table->dropColumn([
                'shortcode', 'city', 'opened_on', 'room_rate', 'rooms', 'star_rating',
                'amenities', 'facilities', 'gst_applicable', 'featured', 'description',
                'manager', 'contact_phone', 'contact_email',
            ]);
        });
    }
};
