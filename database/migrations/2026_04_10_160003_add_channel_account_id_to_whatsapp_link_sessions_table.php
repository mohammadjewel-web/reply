<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_link_sessions', function (Blueprint $table) {
            $table->foreignId('channel_account_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_link_sessions', function (Blueprint $table) {
            $table->dropForeign(['channel_account_id']);
            $table->dropColumn('channel_account_id');
        });
    }
};
