<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number', 10)->unique();
            $table->string('name');
            $table->enum('type', ['single', 'double', 'suite', 'deluxe', 'penthouse']);
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');
            $table->unsignedTinyInteger('floor');
            $table->unsignedTinyInteger('capacity')->default(1);
            $table->decimal('price_per_night', 10, 2);
            $table->text('description')->nullable();
            $table->json('amenities')->nullable()->comment('e.g. ["wifi","tv","minibar"]');
            $table->json('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('type');
            $table->index('price_per_night');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
