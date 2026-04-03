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
        Schema::create('promotion_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->onDelete('cascade');
            $table->string('variant')->nullable(); // 'A', 'B', or 'overall'
            $table->integer('total_sent')->default(0);
            $table->integer('total_clicked')->default(0);
            $table->integer('total_converted')->default(0);
            $table->decimal('click_rate', 5, 2)->default(0); // percentage
            $table->decimal('conversion_rate', 5, 2)->default(0); // percentage
            $table->timestamps();

            $table->unique(['promotion_id', 'variant']);
            $table->index('promotion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_analytics');
    }
};
