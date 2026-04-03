<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('review_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('average_rating', 3, 2);
            $table->integer('total_reviews');
            $table->integer('rating_1')->default(0);
            $table->integer('rating_2')->default(0);
            $table->integer('rating_3')->default(0);
            $table->integer('rating_4')->default(0);
            $table->integer('rating_5')->default(0);
            $table->integer('completion_rate')->default(0);
            $table->integer('response_time_hours')->nullable();
            $table->json('keywords')->nullable();
            $table->timestamps();
            $table->unique('date');
        });

        Schema::create('room_type_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->decimal('average_rating', 3, 2);
            $table->integer('total_reviews')->default(0);
            $table->integer('rating_1')->default(0);
            $table->integer('rating_2')->default(0);
            $table->integer('rating_3')->default(0);
            $table->integer('rating_4')->default(0);
            $table->integer('rating_5')->default(0);
            $table->timestamps();
            $table->unique('room_id');
        });

        Schema::create('negative_review_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->onDelete('cascade');
            $table->integer('rating');
            $table->text('comment');
            $table->string('status')->default('new'); // new, acknowledged, resolved
            $table->text('admin_notes')->nullable();
            $table->dateTime('acknowledged_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_analytics');
        Schema::dropIfExists('room_type_ratings');
        Schema::dropIfExists('negative_review_alerts');
    }
};
