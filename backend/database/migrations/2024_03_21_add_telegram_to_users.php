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
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('telegram_user_id')->nullable()->unique()->after('id');
            $table->bigInteger('telegram_chat_id')->nullable()->after('telegram_user_id');
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_username');
            $table->boolean('telegram_notifications_enabled')->default(true)->after('telegram_linked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_user_id',
                'telegram_chat_id',
                'telegram_username',
                'telegram_linked_at',
                'telegram_notifications_enabled',
            ]);
        });
    }
};
