<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('channel_account_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->after('channel_account_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['platform', 'external_thread_key']);
        });

        $now = now();
        $waExternal = config('services.whatsapp.phone_number_id') ?: 'legacy-whatsapp';
        $msExternal = config('services.messenger.page_id') ?: 'legacy-messenger';

        $waToken = config('services.whatsapp.access_token');
        $msToken = config('services.messenger.page_access_token');

        $waAccountId = DB::table('channel_accounts')->insertGetId([
            'type' => 'whatsapp',
            'name' => 'Default WhatsApp',
            'is_active' => true,
            'external_id' => $waExternal,
            'access_token' => $waToken ? encrypt($waToken) : null,
            'waba_id' => null,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $msAccountId = DB::table('channel_accounts')->insertGetId([
            'type' => 'messenger',
            'name' => 'Default Messenger',
            'is_active' => true,
            'external_id' => $msExternal,
            'access_token' => $msToken ? encrypt($msToken) : null,
            'waba_id' => null,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('conversations')->where('platform', 'whatsapp')->update(['channel_account_id' => $waAccountId]);
        DB::table('conversations')->where('platform', 'messenger')->update(['channel_account_id' => $msAccountId]);

        Schema::table('conversations', function (Blueprint $table) {
            $table->unique(['channel_account_id', 'external_thread_key'], 'conversations_account_thread_unique');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique('conversations_account_thread_unique');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['channel_account_id']);
            $table->dropForeign(['assigned_to_user_id']);
            $table->dropColumn(['channel_account_id', 'assigned_to_user_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->unique(['platform', 'external_thread_key']);
        });
    }
};
