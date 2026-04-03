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
        Schema::create('review_collection_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, rating_given, completed
            $table->integer('rating')->nullable(); // 1-5
            $table->text('what_liked')->nullable();
            $table->text('improvement_suggestions')->nullable();
            $table->boolean('would_recommend')->nullable();
            $table->boolean('permission_to_display')->default(false);
            $table->text('admin_reply')->nullable();
            $table->dateTime('rating_given_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_collection_sessions');
    }
};
