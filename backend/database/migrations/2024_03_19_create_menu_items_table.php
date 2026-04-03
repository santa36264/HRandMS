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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', [
                'breakfast',
                'main_course',
                'drinks',
                'dessert',
            ]);
            $table->decimal('price', 10, 2);
            $table->string('emoji')->default('🍽️');
            $table->boolean('is_available')->default(true);
            $table->integer('preparation_time')->default(30); // in minutes
            $table->timestamps();

            $table->index('category');
            $table->index('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
