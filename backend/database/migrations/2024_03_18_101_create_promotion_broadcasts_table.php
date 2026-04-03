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
        Schema::create('promotion_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('variant')->nullable(); // 'A' or 'B' for A/B testing
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('clicked_at')->nullable();
            $table->dateTime('booked_at')->nullable();
            $table->enum('status', [
                'pending',
                'sent',
                'clicked',
                'converted',
                'failed',
            ])->default('pending');
            $table->timestamps();

            $table->index('promotion_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('variant');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_broadcasts');
    }
};
