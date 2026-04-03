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
        Schema::create('room_service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', [
                'pending',
                'preparing',
                'ready',
                'delivered',
                'cancelled',
            ])->default('pending');
            $table->text('special_requests')->nullable();
            $table->dateTime('ordered_at');
            $table->dateTime('estimated_delivery_time')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('user_id');
            $table->index('room_id');
            $table->index('status');
            $table->index('ordered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_service_orders');
    }
};
