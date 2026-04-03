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
        Schema::create('telegram_verifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_user_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('email');
            $table->string('code')->nullable();
            $table->dateTime('code_expires_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->enum('status', ['pending', 'verified', 'expired', 'blocked', 'new_account'])->default('pending');
            $table->timestamps();

            $table->index('telegram_user_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_verifications');
    }
};
